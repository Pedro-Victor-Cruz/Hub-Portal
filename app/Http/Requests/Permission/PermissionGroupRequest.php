<?php

namespace App\Http\Requests\Permission;

use App\Http\Requests\ApiRequest;
use App\Models\User;
use App\Utils\PermissionStatus;
use Illuminate\Support\Facades\Auth;

class PermissionGroupRequest extends ApiRequest
{

    public function rules(): array
    {
        /** @var User $user */
        $user = Auth::guard('auth')->user();

        return [
            'description'   => 'required|string|max:255',
            'name'          => 'required|string|max:255',
            'company_id'    => 'sometimes|integer|exists:companies,id',
            'access_level'  => 'sometimes|in:' . implode(',', array_keys(PermissionStatus::getManageableLevelsArray($user->accessLevel()))),
            'permissions'   => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'O campo descrição é obrigatório.',
            'description.string'   => 'O campo descrição deve ser uma string.',
            'description.max'      => 'O campo descrição não pode ter mais de 255 caracteres.',
            'name.required'        => 'O campo nome é obrigatório.',
            'name.string'          => 'O campo nome deve ser uma string.',
            'name.max'             => 'O campo nome não pode ter mais de 255 caracteres.',
            'company_id.integer'   => 'O campo empresa deve ser um número inteiro.',
            'company_id.exists'    => 'A empresa selecionada não existe.',
            'access_level.in'      => 'O nível de acesso selecionado é inválido.',
            'permissions.required' => 'O campo permissões é obrigatório.',
            'permissions.array'    => 'O campo permissões deve ser um array.',
            'permissions.*.string' => 'Cada permissão deve ser uma string.',
            'permissions.*.exists' => 'Uma ou mais permissões selecionadas não existem.',
        ];
    }
}
