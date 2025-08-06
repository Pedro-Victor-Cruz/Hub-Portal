<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CompanyRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'key' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('companies')->ignore($this->route('id')),
            ],
            'cnpj' => [
                'required',
                'string',
                'size:14',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'responsible_user_id' => [
                'nullable',
                'exists:users,id'
            ],
        ];
    }


    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O campo nome deve ser uma string.',
            'name.max' => 'O campo nome não pode ter mais que 255 caracteres.',
            'name.unique' => 'Já existe uma empresa com o nome informado.',

            'cnpj.required' => 'O campo CNPJ é obrigatório.',
            'cnpj.string' => 'O campo CNPJ deve ser uma string.',
            'cnpj.size' => 'O CNPJ deve ter exatamente 14 caracteres.',

            'responsible_user_id.exists' => 'O usuário responsável informado não existe.',
            'responsible_user_id.in' => 'O usuário responsável deve ser o usuário autenticado.',
        ];
    }
}
