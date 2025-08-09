<?php

namespace App\Http\Requests\DynamicQuery;

use App\Http\Requests\ApiRequest;

class UpdateDynamicQueryRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'service_slug' => 'sometimes|required|string',
            'service_params' => 'nullable|array',
            'query_config' => 'nullable|string',
            'fields_metadata' => 'nullable|array',
            'response_format' => 'nullable|array',
            'active' => 'boolean',
            'priority' => 'integer|min:0|max:999'
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da consulta é obrigatório.',
            'name.max' => 'O nome não pode ter mais que 255 caracteres.',
            'service_slug.required' => 'O identificador slug do serviço é obrigatória.',
            'priority.integer' => 'A prioridade deve ser um número inteiro.',
            'priority.min' => 'A prioridade mínima é 0.',
            'priority.max' => 'A prioridade máxima é 999.'
        ];
    }
}