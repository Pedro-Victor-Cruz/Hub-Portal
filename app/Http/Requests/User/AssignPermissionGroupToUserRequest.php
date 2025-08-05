<?php
namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;

class AssignPermissionGroupToUserRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'group_id' => 'nullable|integer|exists:permission_groups,id',
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.integer'  => 'O campo grupo de permissões deve ser um número inteiro.',
            'group_id.exists'   => 'O grupo de permissões selecionado não existe.',
        ];
    }
}
