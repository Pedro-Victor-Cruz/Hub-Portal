<?php

namespace App\Http\Requests\DynamicQuery;

use App\Http\Requests\ApiRequest;

class StoreDynamicQueryRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required|string|regex:/^[a-z0-9_]+$/',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'service_slug' => 'required|string',
            'service_params' => 'nullable|array',
            'query_config' => 'nullable|string',
            'fields_metadata' => 'nullable|array',
            'response_format' => 'nullable|array',
            'active' => 'boolean',
            'is_global' => 'boolean',
            'priority' => 'integer|min:0|max:999'
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'A chave da consulta é obrigatória.',
            'key.regex' => 'A chave deve conter apenas letras minúsculas, números e underlines (_).',
            'name.required' => 'O nome da consulta é obrigatório.',
            'name.max' => 'O nome não pode ter mais que 255 caracteres.',
            'service_slug.required' => 'O identificador slug de serviço é obrigatória.',
            'priority.integer' => 'A prioridade deve ser um número inteiro.',
            'priority.min' => 'A prioridade mínima é 0.',
            'priority.max' => 'A prioridade máxima é 999.'
        ];
    }
}