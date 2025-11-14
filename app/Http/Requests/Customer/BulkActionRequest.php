<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class BulkActionRequest extends FormRequest
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
            'action' => 'required|in:activate,deactivate,delete',
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id'
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be one of: activate, deactivate, delete',
            'customer_ids.required' => 'Customer IDs are required',
            'customer_ids.array' => 'Customer IDs must be an array',
            'customer_ids.min' => 'At least one customer ID is required',
            'customer_ids.*.exists' => 'One or more customer IDs are invalid'
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_ids' => 'customer IDs'
        ];
    }
}