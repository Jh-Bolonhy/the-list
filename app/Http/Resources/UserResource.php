<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'headline' => $this->headline,
            'locale' => $this->locale ?? 'en',
            'show_mode' => $this->show_mode ?? 'active',
            'locked_element_id' => $this->locked_element_id,
            'email' => $this->email,
        ];
    }
}


