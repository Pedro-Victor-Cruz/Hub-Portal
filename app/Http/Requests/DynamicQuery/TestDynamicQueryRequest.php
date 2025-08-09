<?php

namespace App\Http\Requests\DynamicQuery;

use App\Http\Requests\ApiRequest;

class TestDynamicQueryRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'key' => 'required|string|regex:/^[a-z0-9_]+$/',
            'name' => 'required|string|max:255',
            'service_slug' => 'required|string',
            'service_params' => 'nullable|array',
            'query_config' => 'nullable|string',
            'fields_metadata' => 'nullable|array',
            'test_params' => 'nullable|array'
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'A chave da consulta é obrigatória.',
            'key.regex' => 'A chave deve conter apenas letras minúsculas, números e underlines (_).',
            'name.required' => 'O nome da consulta é obrigatório.',
            'name.max' => 'O nome não pode ter mais que 255 caracteres.',
            'service_slug.required' => 'O identificador (slug) do serviço é obrigatória.'
        ];
    }
}