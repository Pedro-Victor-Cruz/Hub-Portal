<?php

namespace App\Http\Requests\DynamicQuery;

use App\Http\Requests\ApiRequest;

class DuplicateDynamicQueryRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|integer|min:0|max:999',
            'active' => 'nullable|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'O nome não pode ter mais que 255 caracteres.',
            'priority.integer' => 'A prioridade deve ser um número inteiro.',
            'priority.min' => 'A prioridade mínima é 0.',
            'priority.max' => 'A prioridade máxima é 999.'
        ];
    }
}