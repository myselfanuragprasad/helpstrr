<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Customer;

class CustomerAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->input('token');
        $phone = $request->input('phone');

        if (!$token) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 401,
                'status_message' => 'Authentication token is required'
            ], 401);
        }

        // If phone is provided, verify both token and phone
        if ($phone) {
            $customer = Customer::where('phone', $phone)
                ->where('token', $token)
                ->where('is_active', true)
                ->first();
        } else {
            // Try to find customer by token only
            $customer = Customer::where('token', $token)
                ->where('is_active', true)
                ->first();
        }

        if (!$customer) {
            return response()->json([
                'status' => 'failure',
                'status_code' => 401,
                'status_message' => 'Invalid authentication credentials'
            ], 401);
        }

        // Add customer to request for use in controllers
        $request->merge(['authenticated_customer' => $customer]);
        
        return $next($request);
    }
}