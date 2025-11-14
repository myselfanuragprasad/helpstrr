<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SPUser extends Authenticatable implements HasAvatar, HasName
{
    protected $primaryKey = 'id'; // 👈 important since your PK is sp_id

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'mobile1_number',
        'whatsapp',
        'dob',
        'gender',
        'profile_status',
        'avatar_url',
        'intrested_role',
        'prior_experience',
        'password',
        'confirm_password_hash',
        'address',
        'city',
        'country',
        'state',
        'pincode',
        'experience_years',
        'bio',
        'profile_picture',
        'id_proof',
        'background_check_status',
        'avg_rating',
        'total_ratings',
        'is_verified',
        'is_active',
        'last_login',
    ];

    protected $guarded = ['sp_id'];

    protected $hidden = [
        'password_hash',
        'confirm_password_hash',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'last_login' => 'datetime',
        'avg_rating' => 'decimal:2',
    ];

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function getFilamentName(): string
    {
        return $this->first_name
            ? $this->first_name . ' ' . $this->last_name
            : $this->email;
    }
}
