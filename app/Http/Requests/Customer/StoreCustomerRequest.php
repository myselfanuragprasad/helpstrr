<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:customers,email|max:255',
            'phone' => 'nullable|string|max:20|unique:customers,phone|regex:/^[1-9][0-9]{9,14}$/',
            'password' => 'nullable|string|min:6|max:255',
            'avatar_url' => 'nullable|string|max:255|url',
            'is_active' => 'boolean'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required',
            'name.max' => 'Customer name cannot exceed 100 characters',
            'email.required' => 'Email address is required',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email address is already registered',
            'phone.unique' => 'This phone number is already registered',
            'phone.regex' => 'Please provide a valid phone number (10-15 digits, not starting with 0)',
            'password.min' => 'Password must be at least 6 characters long',
            'avatar_url.url' => 'Avatar URL must be a valid URL'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'name' => 'customer name',
            'email' => 'email address',
            'phone' => 'phone number',
            'avatar_url' => 'avatar URL',
            'is_active' => 'active status'
        ];
    }
}