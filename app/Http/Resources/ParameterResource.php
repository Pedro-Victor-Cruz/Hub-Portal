<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParameterResource extends JsonResource
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
            'key' => $this->key,
            'description' => $this->description,
            'category' => $this->category,
            'type' => $this->type,
            'is_system' => $this->is_system,
            'access_level' => $this->access_level,
            'default_value' => $this->default_value,
            'options' => $this->options,
            'value' => $this->value,
            'formatted_value' => $this->getFormattedValue(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
