<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CompanyErpSettingRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'company_id' => [
                'required',
                'exists:companies,id'
            ],
            'erp_name' => [
                'required',
                'string',
                'max:255',
                Rule::in(['sankhya']) // Adicione outros ERPs conforme necessário
            ],
            'username' => [
                'nullable',
                'string'
            ],
            'secret_key' => [
                'nullable',
                'string'
            ],
            'token' => [
                'nullable',
                'string'
            ],
            'base_url' => [
                'nullable',
                'string',
                'url'
            ],
            'auth_type' => [
                'required',
                'string',
                Rule::in(['token', 'session', 'oauth'])
            ],
            'extra_config' => [
                'nullable',
                'array'
            ],
            'active' => [
                'boolean'
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'company_id.required' => 'O ID da empresa é obrigatório.',
            'company_id.exists' => 'A empresa informada não existe.',
            'erp_name.required' => 'O nome do ERP é obrigatório.',
            'erp_name.in' => 'O ERP informado não é suportado.',
            'username.string' => 'O nome de usuário deve ser uma string.',
            'secret_key.string' => 'A chave secreta deve ser uma string.',
            'token.string' => 'O token deve ser uma string.',
            'base_url.url' => 'A URL base deve ser um endereço válido.',
            'auth_type.required' => 'O tipo de autenticação é obrigatório.',
            'auth_type.in' => 'O tipo de autenticação informado não é suportado.',
            'extra_config.array' => 'As configurações extras devem ser um array.',
            'active.boolean' => 'O campo ativo deve ser verdadeiro ou falso.'
        ];
    }
}
