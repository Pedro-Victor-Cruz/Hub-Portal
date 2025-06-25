<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserCreateOrUpdateRequest extends ApiRequest
{
    /**
     * @throws ValidationException
     */
    public function rules(): array
    {
        $auth = Auth::guard('auth')->user();

        // Se company_id foi enviado, valida se o usuário tem permissão
        if ($this->filled('company_id') && $auth && !$auth->hasPermissionTo('company.assign_user')) {
            throw ValidationException::withMessages([
                'company_id' => 'Você não tem permissão para atribuir uma empresa.',
            ]);
        }

        return [
            'email' => [
                //ignora caso coluna deleted_at esteja preenchida
                Rule::unique('users')->ignore($this->route('id'))->whereNull('deleted_at'),
                'required',
                'email',
            ],
            'password' => [
                Rule::requiredIf($this->isMethod('post')),
                'min:8',
                'max:20',
            ],
            'confirm_password' => [
                Rule::requiredIf($this->filled('password')),
                'same:password',
            ],
            'name' => 'required|min:3|max:50',
            'company_id' => 'nullable|exists:companies,id',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('company_id')) {
            $auth = Auth::guard('auth')->user();
            if ($auth && $auth->company_id && !$auth->hasPermissionTo('company.assign_user')) {
                $this->merge([
                    'company_id' => $auth->company_id,
                ]);
            }
        }
    }

    public function messages(): array
    {
        return [

            'email.unique' => 'Este email já está em uso.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'O email deve ser um email válido.',
            'password.required' => 'A senha é obrigatória.',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres.',
            'password.max' => 'A senha deve ter no máximo 20 caracteres.',
            'password.regex' => 'A senha deve conter letras maiúsculas, minúsculas, números e caracteres especiais.',
            'confirm_password.required' => 'A confirmação de senha é obrigatória.',
            'confirm_password.same' => 'As senhas não coincidem.',
            'name.required' => 'O nome é obrigatório.',
            'name.min' => 'O nome deve ter no mínimo 3 caracteres.',
            'name.max' => 'O nome deve ter no máximo 50 caracteres.',
            'company_id.exists' => 'A empresa selecionada não existe.',
        ];
    }
}
