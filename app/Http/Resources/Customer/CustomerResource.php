<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_url,
            'is_active' => $this->is_active,
            'last_login' => $this->last_login?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            
            // Computed fields
            'display_name' => $this->getFilamentName(),
            'avatar_display_url' => $this->getFilamentAvatarUrl(),
            'status_text' => $this->is_active ? 'Active' : 'Inactive',
            'registration_days_ago' => $this->created_at->diffInDays(now()),
            'has_phone' => !empty($this->phone),
            'has_avatar' => !empty($this->avatar_url),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'resource_type' => 'customer',
                'version' => 'v1'
            ]
        ];
    }
}