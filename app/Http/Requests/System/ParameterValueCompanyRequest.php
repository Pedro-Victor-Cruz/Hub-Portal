<?php

namespace App\Http\Requests\System;

use App\Http\Requests\ApiRequest;
use App\Models\Permission;
use App\Models\User;
use App\Utils\PermissionStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParameterValueCompanyRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'key' => [
                'required',
                'string',
                'max:255',
                'exists:parameters,key',
            ],
            'value' => 'nullable',
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'O campo chave é obrigatório.',
            'key.string' => 'O campo chave deve ser uma string.',
            'key.max' => 'O campo chave não pode ter mais de 255 caracteres.',
            'key.exists' => 'A chave informada não existe.',
            'company_id.required' => 'O campo empresa é obrigatório.',
            'company_id.integer' => 'O campo empresa deve ser um número inteiro.',
            'company_id.exists' => 'A empresa selecionada não existe.',
        ];
    }
}
