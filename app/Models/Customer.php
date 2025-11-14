<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Filament\Models\Contracts\HasName;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Customer extends Authenticatable implements HasAvatar, HasName
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_active',
        'token',
        'avatar_url',
        'last_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login' => 'datetime',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function getFilamentName(): string
    {
        return $this->name ?? $this->email;
    }

    /**
     * Scope to filter active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive customers
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to search customers by name, email, or phone
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    /**
     * Scope to filter customers with phone numbers
     */
    public function scopeWithPhone($query)
    {
        return $query->whereNotNull('phone');
    }

    /**
     * Scope to filter customers without phone numbers
     */
    public function scopeWithoutPhone($query)
    {
        return $query->whereNull('phone');
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin()
    {
        $this->update(['last_login' => now()]);
        return $this;
    }

    /**
     * Check if customer has been active recently
     */
    public function isRecentlyActive($days = 30)
    {
        return $this->last_login && $this->last_login->isAfter(now()->subDays($days));
    }

    /**
     * Get customer's registration age in days
     */
    public function getRegistrationAgeAttribute()
    {
        return $this->created_at->diffInDays(now());
    }

    /**
     * Check if customer has complete profile
     */
    public function hasCompleteProfile()
    {
        return !empty($this->name) && !empty($this->email) && !empty($this->phone);
    }

    /**
     * Generate a new API token for the customer
     */
    public function generateApiToken($tokenName = 'auth_token')
    {
        // Revoke existing tokens
        $this->tokens()->delete();
        
        // Create new token
        $token = $this->createToken($tokenName)->plainTextToken;
        
        // Update token field
        $this->update(['token' => $token]);
        
        return $token;
    }

    /**
     * Activate the customer
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
        return $this;
    }

    /**
     * Deactivate the customer
     */
    public function deactivate()
    {
        $this->update(['is_active' => false]);
        return $this;
    }

    /**
     * Toggle customer active status
     */
    public function toggleStatus()
    {
        $this->update(['is_active' => !$this->is_active]);
        return $this;
    }

    public static function handleSignup($data)
    {
        // === Validation ===
        $validator = Validator::make($data, [
            'phone' => [
                'required',
                'regex:/^[1-9][0-9]{9}$/'
            ],
        ], [
            'phone.regex' => 'Invalid phone number. Please enter a valid 10-digit number that does not start with 0.'
        ]);

        if ($validator->fails()) {
            return [
                'status' => 'failure',
                'status_code' => 422,
                'status_message' => $validator->errors()->first()
            ];
        }

        try {
            // === Check if user exists ===
            $user = self::where('phone', $data['phone'])->first();

            if ($user) {
                // Create token for existing user
                $token = $user->createToken('auth_token')->plainTextToken;

                // Save token
                $user->update(['token' => $token]);

                return [
                    'status' => 'success',
                    'status_code' => 200,
                    'status_message' => 'Phone number is already registered as a Customer.',
                    'token' => $token,
                    'user' => $user
                ];
            }

            // === Create new Customer user ===
            $user = self::create([
                'phone' => $data['phone']
            ]);

            // Create token for new user
            $token = $user->createToken('auth_token')->plainTextToken;

            // Save token
            $user->update(['token' => $token]);

            return [
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'New Customer. Proceed with sign-up.',
                'token' => $token,
                'user' => $user
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Something went wrong. Please try again later.',
                'error' => $e->getMessage() // optional for debugging
            ];
        }
    }

    public static function verifyAndGetDetails($phone, $token)
    {
        $user = self::where('phone', $phone)->first();

        if (! $user) {
            return [
                'status' => 'failure',
                'status_code' => 404,
                'status_message' => 'Phone number not found',
                'data' => null
            ];
        }

        if ($user->token !== $token) {
            return [
                'status' => 'failure',
                'status_code' => 401,
                'status_message' => 'Invalid token',
                'data' => null
            ];
        }

        // Mandatory fields
        $mandatoryFields = [
            'name',
            'email',
        ];

        $allNull = true;

        foreach ($mandatoryFields as $field) {
            if (empty($user->$field)) {
                $allNull = false;
                break;
            }
        }

        if (! $allNull) {
            return [
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'Profile details need to be filled',
                'data' => $user
            ];
        }

        return [
            'status' => 'success',
            'status_code' => 200,
            'status_message' => 'Profile details submitted',
            'data' => $user
        ];
    }

    public static function verifyAndSaveCustomerDetails($request)
    {
        // require these facades at top of file: Validator, DB
        // and ensure ZohoHelper is importable
        $mobile = $request->input('phone');
        $token  = $request->input('token');

        // 1) token + phone presence check
        if (empty($mobile) || empty($token)) {
            return [
                'status' => 'failure',
                'status_code' => 422,
                'status_message' => 'phone and token are required',
                'data' => null
            ];
        }

        // 2) verify user exists and token matches
        $user = DB::table('customers')->where('phone', $mobile)->first();
        if (! $user) {
            return [
                'status' => 'failure',
                'status_code' => 404,
                'status_message' => 'Phone number not found',
                'data' => null
            ];
        }


        if ($user->token !== $token) {
            return [
                'status' => 'failure',
                'status_code' => 401,
                'status_message' => 'Invalid token',
                'data' => null
            ];
        }

        // 3) validate required fields
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'phone'   => 'required|string|regex:/^[0-9]{10,15}$/',
            'email'            => 'required|email|max:255',

        ]);

        if ($validator->fails()) {
            return [
                'status' => 'failure',
                'status_code' => 422,
                'status_message' => 'Validation failed',
                'data' => $validator->errors()
            ];
        }

        // 4) prepare postData
        $postData = $request->only([
            'name',
            'email',
            'phone',
        ]);


        try {
            DB::beginTransaction();

            // if changing phone, ensure new phone isn't owned by another user
            if ($postData['phone'] !== $user->phone) {
                $phoneOwner = DB::table('customers')
                    ->where('phone', $postData['phone'])
                    ->where('id', '!=', $user->id)
                    ->first();

                if ($phoneOwner) {
                    DB::rollBack();
                    return [
                        'status' => 'failure',
                        'status_code' => 409,
                        'status_message' => 'Mobile number already exists for another user',
                        'data' => null
                    ];
                }
            }

            // ensure email is not used by another user
            $emailOwner = DB::table('customers')
                ->where('email', $postData['email'])
                ->where('id', '!=', $user->id)
                ->first();

            if ($emailOwner) {
                DB::rollBack();
                return [
                    'status' => 'failure',
                    'status_code' => 409,
                    'status_message' => 'Email already exists for another user',
                    'data' => null
                ];
            }

            // perform update (we require token verification, so user exists)
            DB::table('customers')->where('id', $user->id)->update($postData);
            $dbUserId = $user->id;

            DB::commit();

            return [
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'SP details updated successfully',
                'data' => array_merge(['id' => $dbUserId], $postData)
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            // do not expose $e->getMessage() in production unless you want debug info
            return [
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Something went wrong while updating SP details',
                'data' => null
            ];
        }
    }
}
