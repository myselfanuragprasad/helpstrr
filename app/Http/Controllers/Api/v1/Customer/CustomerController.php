<?php

namespace App\Http\Controllers\Api\v1\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Requests\Customer\BulkActionRequest;
use App\Http\Resources\Customer\CustomerResource;
use App\Http\Resources\Customer\CustomerCollection;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Customer::query();

            // Search functionality
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $customers = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'Customers retrieved successfully',
                'data' => new CustomerCollection($customers)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Failed to retrieve customers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created customer
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {

        try {
            DB::beginTransaction();

            $customerData = $request->only([
                'name', 'email', 'phone', 'avatar_url', 'is_active'
            ]);

            // Hash password if provided
            if ($request->filled('password')) {
                $customerData['password'] = Hash::make($request->password);
            }

            $customer = Customer::create($customerData);

            // Generate API token
            $token = $customer->createToken('auth_token')->plainTextToken;
            $customer->update(['token' => $token]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'status_code' => 201,
                'status_message' => 'Customer created successfully',
                'data' => [
                    'customer' => new CustomerResource($customer->fresh()),
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Failed to create customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified customer
     */
    public function show($id): JsonResponse
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'status_message' => 'Customer not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'Customer retrieved successfully',
                'data' => new CustomerResource($customer)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Failed to retrieve customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified customer
     */
    public function update(UpdateCustomerRequest $request, $id): JsonResponse
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'status_message' => 'Customer not found'
                ], 404);
            }

            DB::beginTransaction();

            $updateData = $request->only([
                'name', 'email', 'phone', 'avatar_url', 'is_active'
            ]);

            // Hash password if provided
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $customer->update($updateData);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'Customer updated successfully',
                'data' => new CustomerResource($customer->fresh())
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Failed to update customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified customer
     */
    public function destroy($id): JsonResponse
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'status_message' => 'Customer not found'
                ], 404);
            }

            DB::beginTransaction();

            // Revoke all tokens
            $customer->tokens()->delete();
            
            // Delete the customer
            $customer->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'Customer deleted successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Failed to delete customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Activate/Deactivate customer
     */
    public function toggleStatus($id): JsonResponse
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return response()->json([
                    'status' => 'failure',
                    'status_code' => 404,
                    'status_message' => 'Customer not found'
                ], 404);
            }

            $customer->update(['is_active' => !$customer->is_active]);

            $status = $customer->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'status_message' => "Customer {$status} successfully",
                'data' => new CustomerResource($customer->fresh())
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Failed to toggle customer status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get customer statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total_customers' => Customer::count(),
                'active_customers' => Customer::where('is_active', true)->count(),
                'inactive_customers' => Customer::where('is_active', false)->count(),
                'customers_with_phone' => Customer::whereNotNull('phone')->count(),
                'customers_today' => Customer::whereDate('created_at', today())->count(),
                'customers_this_week' => Customer::whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'customers_this_month' => Customer::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
            ];

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'status_message' => 'Customer statistics retrieved successfully',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Failed to retrieve customer statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk operations on customers
     */
    public function bulkAction(BulkActionRequest $request): JsonResponse
    {

        try {
            DB::beginTransaction();

            $customerIds = $request->customer_ids;
            $action = $request->action;
            $affectedCount = 0;

            switch ($action) {
                case 'activate':
                    $affectedCount = Customer::whereIn('id', $customerIds)
                        ->update(['is_active' => true]);
                    break;

                case 'deactivate':
                    $affectedCount = Customer::whereIn('id', $customerIds)
                        ->update(['is_active' => false]);
                    break;

                case 'delete':
                    // Revoke tokens first
                    Customer::whereIn('id', $customerIds)->each(function ($customer) {
                        $customer->tokens()->delete();
                    });
                    $affectedCount = Customer::whereIn('id', $customerIds)->delete();
                    break;
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'status_code' => 200,
                'status_message' => "Bulk {$action} completed successfully",
                'data' => [
                    'affected_count' => $affectedCount,
                    'action' => $action
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'failure',
                'status_code' => 500,
                'status_message' => 'Failed to perform bulk action',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}