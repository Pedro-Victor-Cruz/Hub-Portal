<?php

namespace App\Http\Requests\Portal;

use App\Http\Requests\ApiRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortalRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('portals', 'name')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at')
            ],
            'slug' => [
                'required',
                'string',
                Rule::unique('portals', 'slug')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at')
            ],
            'phone' => [
                'nullable',
                'string',
                'min:11',
                'max:11'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório',
            'name.string' => 'O campo nome deve ser uma string',
            'name.unique' => 'O nome do portal informado já está em uso',
            'slug.required' => 'O campo slug é obrigatório',
            'slug.string' => 'O campo slug deve ser uma string',
            'slug.unique' => 'O slug do portal informado já está em uso',
            'phone.string' => 'O campo telefone deve ser uma string',
            'phone.min' => 'O campo telefone deve ter 11 caracteres',
            'phone.max' => 'O campo telefone deve ter 11 caracteres',
        ];
    }
}
