<?php

namespace App\Http\Requests\System;

use App\Http\Requests\ApiRequest;
use App\Models\Permission;
use App\Models\User;
use App\Utils\PermissionStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ParameterRequest extends ApiRequest
{
    /**
     * @throws ValidationException
     */
    public function rules(): array
    {

        /** @var User $user */
        $user = Auth::guard('auth')->user();

        return [
            'key' => [
                'required',
                'string',
                'max:255',
                Rule::unique('parameters')->whereNot('key', $this->key),
            ],
            'description' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'type' => [
                'required',
                Rule::in(['boolean', 'integer', 'decimal', 'date', 'text', 'list']),
            ],
            'default_value' => 'nullable',
            'options' => 'nullable|array',
            'is_system' => 'boolean',
            'access_level' => [
                'required',
                'integer',
                'in:' . implode(',', array_keys(PermissionStatus::getManageableLevelsArray($user->accessLevel()))),
            ],
            'value' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'key.required' => 'O campo chave é obrigatório.',
            'key.string' => 'O campo chave deve ser uma string.',
            'key.max' => 'O campo chave não pode ter mais de 255 caracteres.',
            'key.unique' => 'Já existe um parâmetro com esta chave.',
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string' => 'O campo descrição deve ser uma string.',
            'description.max' => 'O campo descrição não pode ter mais de 255 caracteres.',
            'category.required' => 'O campo categoria é obrigatório.',
            'category.string' => 'O campo categoria deve ser uma string.',
            'category.max' => 'O campo categoria não pode ter mais de 100 caracteres.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O campo tipo deve ser um dos seguintes valores: boolean, integer, decimal, date, text, list.',
            'default_value' => 'O campo valor padrão deve ser válido para o tipo selecionado.',
            'options.array' => 'O campo opções deve ser um array.',
            'is_system.boolean' => 'O campo is_system deve ser verdadeiro ou falso.',
            'access_level.required' => 'O campo nível de acesso é obrigatório.',
            'access_level.integer' => 'O campo nível de acesso deve ser um número inteiro.',
            'access_level.in' => 'Precisa ser um nível de acesso válido.',
        ];
    }
}
