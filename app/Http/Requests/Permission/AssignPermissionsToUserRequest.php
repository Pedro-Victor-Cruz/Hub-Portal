<?php
namespace App\Http\Requests\Permission;

use App\Http\Requests\ApiRequest;

class AssignPermissionsToUserRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string|exists:permissions,name',
            'permissions.*.value' => 'required|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'permissions.required' => 'A lista de permissões é obrigatória.',
            'permissions.array' => 'As permissões devem ser fornecidas como um array.',
            'permissions.*.name.required' => 'O nome da permissão é obrigatório.',
            'permissions.*.name.string' => 'O nome da permissão deve ser uma string.',
            'permissions.*.name.exists' => 'A permissão especificada não existe.',
            'permissions.*.value.required' => 'O valor da permissão é obrigatório.',
            'permissions.*.value.boolean' => 'O valor da permissão deve ser verdadeiro ou falso.',
        ];
    }
}
