<?php
namespace App\Http\Requests\Permission;

use App\Http\Requests\ApiRequest;

class AssignPermissionsToUserRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'O campo permissões é obrigatório.',
            'permissions.array' => 'O campo permissões deve ser um array.',
            'permissions.*.string' => 'Cada permissão deve ser uma string.',
            'permissions.*.exists' => 'Uma ou mais permissões selecionadas não existem.',
        ];
    }
}
