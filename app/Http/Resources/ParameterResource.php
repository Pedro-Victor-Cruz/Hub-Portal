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
        // Pega o companyId da request (pode vir da query string)
        $companyId = $request->query('companyId');

        // Se houver, tenta buscar a empresa
        $company = $companyId ? \App\Models\Company::find($companyId) : null;

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
            'value' => $this->getValueForCompany($company),
            'formatted_value' => $this->getFormattedValue($company),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
