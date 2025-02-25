<?php

namespace App\Http\Requests\Department;

use App\Http\Requests\ApiRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                Rule::unique('departments', 'name')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at')
            ],
            'slug' => [
                'required',
                'string',
                Rule::unique('departments', 'slug')
                    ->ignore($this->route('id'))
                    ->whereNull('deleted_at')
            ],
            'is_default' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O campo nome é obrigatório',
            'name.string' => 'O campo nome deve ser uma string',
            'name.unique' => 'O nome do departamento informado já está em uso',
            'slug.required' => 'O campo slug é obrigatório',
            'slug.string' => 'O campo slug deve ser uma string',
            'slug.unique' => 'O slug do departamento informado já está em uso',
            'is_default.boolean' => 'O campo is_default deve ser um booleano',
        ];
    }
}
