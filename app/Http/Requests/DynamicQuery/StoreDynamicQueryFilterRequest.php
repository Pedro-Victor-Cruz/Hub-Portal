<?php

namespace App\Http\Requests\DynamicQuery;

use App\Enums\FilterType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request para criação de filtros dinâmicos
 */
class StoreDynamicQueryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'var_name' => 'required|string|max:50|regex:/^[A-Z][A-Z0-9_]*$/',
            'type' => ['required', Rule::enum(FilterType::class)],
            'default_value' => 'nullable',
            'required' => 'boolean',
            'order' => 'integer|min:0|max:999',
            'validation_rules' => 'nullable|array',
            'visible' => 'boolean',
            'active' => 'boolean',
            'options' => 'nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'var_name.regex' => 'O nome da variável deve começar com letra maiúscula e conter apenas letras maiúsculas, números e underscores.',
            'var_name.required' => 'O nome da variável é obrigatório.',
            'name.required' => 'O nome do filtro é obrigatório.',
            'type.required' => 'O tipo do filtro é obrigatório.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Converte var_name para maiúsculo se não estiver
        if ($this->has('var_name')) {
            $this->merge([
                'var_name' => strtoupper($this->var_name)
            ]);
        }

        // Define valores padrão
        $this->merge([
            'required' => $this->boolean('required', false),
            'visible' => $this->boolean('visible', true),
            'active' => $this->boolean('active', true),
            'order' => $this->integer('order', 0),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $type = FilterType::tryFrom($this->input('type'));

            if ($type && $type->requiresOptions() && empty($this->input('options'))) {
                $validator->errors()->add('options', 'Opções são obrigatórias para o tipo ' . $type->getDescription());
            }
        });
    }
}