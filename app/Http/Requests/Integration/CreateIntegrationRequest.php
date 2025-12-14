<?php

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ajuste conforme sua lógica de autorização
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $availableIntegrations = array_keys(config('integration.drivers', []));

        return [
            'integration_name' => [
                'required',
                'string',
                Rule::in($availableIntegrations),
                // Valida que não existe outra integração do mesmo tipo para esta empresa
                Rule::unique('integrations')
                    ->where('integration_name', $this->integration_name)
            ],
            'configuration' => [
                'required',
                'array'
            ],
            'active' => [
                'sometimes',
                'boolean'
            ]
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'integration_name.required' => 'Nome da integração é obrigatório',
            'integration_name.in' => 'Tipo de integração inválido',
            'integration_name.unique' => 'Esta empresa já possui uma integração deste tipo',
            'configuration.required' => 'Configuração é obrigatória',
            'configuration.array' => 'Configuração deve ser um objeto/array',
        ];
    }
}