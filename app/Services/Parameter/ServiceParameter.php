<?php

namespace App\Services\Parameter;

use App\Enums\ParameterType;

class ServiceParameter
{
    public function __construct(
        public readonly string $name,
        public readonly ParameterType $type,
        public readonly bool $required = false,
        public readonly mixed $defaultValue = null,
        public readonly ?string $description = null,
        public readonly ?string $label = null,
        public readonly array $options = [],
        public readonly array $validation = [],
        public readonly ?string $placeholder = null,
        public readonly ?string $group = null,
        public readonly int $order = 0,
        public readonly bool $sensitive = false,
        public readonly array $dependsOn = [],
        public readonly ?array $arrayItemType = null
    ) {}

    /**
     * Converte o parâmetro para array (útil para APIs)
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type->value,
            'required' => $this->required,
            'default_value' => $this->defaultValue,
            'description' => $this->description,
            'label' => $this->label ?: ucfirst(str_replace('_', ' ', $this->name)),
            'options' => $this->options,
            'validation' => $this->validation,
            'placeholder' => $this->placeholder,
            'group' => $this->group,
            'order' => $this->order,
            'sensitive' => $this->sensitive,
            'depends_on' => $this->dependsOn,
            'array_item_type' => $this->arrayItemType,
        ];
    }

    /**
     * Métodos estáticos para facilitar a criação de parâmetros
     */
    public static function text(
        string $name,
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null,
        array $validation = []
    ): self {
        return new self(
            name: $name,
            type: ParameterType::TEXT,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: $validation
        );
    }

    public static function number(
        string $name,
        bool $required = false,
        int|float|null $defaultValue = null,
        ?string $description = null,
        ?int $min = null,
        ?int $max = null
    ): self {
        $validation = [];
        if ($min !== null) $validation['min'] = $min;
        if ($max !== null) $validation['max'] = $max;

        return new self(
            name: $name,
            type: ParameterType::NUMBER,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: $validation
        );
    }

    public static function boolean(
        string $name,
        bool $required = false,
        ?bool $defaultValue = null,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            type: ParameterType::BOOLEAN,
            required: $required,
            defaultValue: $defaultValue,
            description: $description
        );
    }

    public static function select(
        string $name,
        array $options,
        bool $required = false,
        mixed $defaultValue = null,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            type: ParameterType::SELECT,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            options: $options
        );
    }

    public static function multiselect(
        string $name,
        array $options,
        bool $required = false,
        array $defaultValue = [],
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            type: ParameterType::MULTISELECT,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            options: $options
        );
    }

    public static function date(
        string $name,
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            type: ParameterType::DATE,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: ['date_format' => 'Y-m-d']
        );
    }

    public static function array(
        string $name,
        bool $required = false,
        array $defaultValue = [],
        ?string $description = null,
        ?array $arrayItemType = null
    ): self {
        return new self(
            name: $name,
            type: ParameterType::ARRAY,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            arrayItemType: $arrayItemType
        );
    }

    public static function object(
        string $name,
        bool $required = false,
        ?array $defaultValue = null,
        ?string $description = null,
        array $properties = []
    ): self {
        return new self(
            name: $name,
            type: ParameterType::OBJECT,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: ['properties' => $properties]
        );
    }

    public static function url(
        string $name,
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            type: ParameterType::URL,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: ['url' => true]
        );
    }

    public static function email(
        string $name,
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null
    ): self {
        return new self(
            name: $name,
            type: ParameterType::EMAIL,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: ['email' => true]
        );
    }

    public static function sql(
        string $name,
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null,
        array $validation = []
    ): self {
        return new self(
            name: $name,
            type: ParameterType::SQL,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: $validation
        );
    }

    public static function javascript(
        string $name,
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null,
        array $validation = []
    ): self {
        return new self(
            name: $name,
            type: ParameterType::JAVASCRIPT,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: $validation
        );
    }

    /**
     * Parâmetro para cor (hex, rgb, rgba)
     */
    public static function color(
        string $name,
        bool $required = false,
        ?string $defaultValue = null,
        ?string $description = null
    ): ServiceParameter {
        return new ServiceParameter(
            name: $name,
            type: ParameterType::COLOR,
            required: $required,
            defaultValue: $defaultValue,
            description: $description,
            validation: ['pattern' => '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/']
        );
    }

    /**
     * Parâmetro para mapeamento de colunas
     */
    public static function columnMapping(
        string $name,
        bool $required = false,
        ?array $defaultValue = null,
        ?string $description = null
    ): ServiceParameter {
        return new ServiceParameter(
            name: $name,
            type: ParameterType::COLUMN_MAPPING,
            required: $required,
            defaultValue: $defaultValue,
            description: $description
        );
    }

    /**
     * Parâmetro para configuração de séries
     */
    public static function seriesConfig(
        string $name,
        bool $required = false,
        ?array $defaultValue = null,
        ?string $description = null
    ): ServiceParameter {
        return new ServiceParameter(
            name: $name,
            type: ParameterType::SERIES_CONFIG,
            required: $required,
            defaultValue: $defaultValue,
            description: $description
        );
    }

    public function withLabel(string $string): ServiceParameter
    {
        return new self(
            name: $this->name,
            type: $this->type,
            required: $this->required,
            defaultValue: $this->defaultValue,
            description: $this->description,
            label: $string,
            options: $this->options,
            validation: $this->validation,
            placeholder: $this->placeholder,
            group: $this->group,
            order: $this->order,
            sensitive: $this->sensitive,
            dependsOn: $this->dependsOn,
            arrayItemType: $this->arrayItemType
        );
    }

    public function withSensitive(bool $sensitive = true): ServiceParameter
    {
        return new self(
            name: $this->name,
            type: $this->type,
            required: $this->required,
            defaultValue: $this->defaultValue,
            description: $this->description,
            label: $this->label,
            options: $this->options,
            validation: $this->validation,
            placeholder: $this->placeholder,
            group: $this->group,
            order: $this->order,
            sensitive: $sensitive,
            dependsOn: $this->dependsOn,
            arrayItemType: $this->arrayItemType
        );
    }

    public function withDependencies(array $dependsOn): ServiceParameter
    {
        return new self(
            name: $this->name,
            type: $this->type,
            required: $this->required,
            defaultValue: $this->defaultValue,
            description: $this->description,
            label: $this->label,
            options: $this->options,
            validation: $this->validation,
            placeholder: $this->placeholder,
            group: $this->group,
            order: $this->order,
            sensitive: $this->sensitive,
            dependsOn: $dependsOn,
            arrayItemType: $this->arrayItemType
        );
    }

    public function withPlaceholder(string $string): ServiceParameter
    {
        return new self(
            name: $this->name,
            type: $this->type,
            required: $this->required,
            defaultValue: $this->defaultValue,
            description: $this->description,
            label: $this->label,
            options: $this->options,
            validation: $this->validation,
            placeholder: $string,
            group: $this->group,
            order: $this->order,
            sensitive: $this->sensitive,
            dependsOn: $this->dependsOn,
            arrayItemType: $this->arrayItemType
        );
    }

    public function withGroup(string $string): ServiceParameter
    {
        return new self(
            name: $this->name,
            type: $this->type,
            required: $this->required,
            defaultValue: $this->defaultValue,
            description: $this->description,
            label: $this->label,
            options: $this->options,
            validation: $this->validation,
            placeholder: $this->placeholder,
            group: $string,
            order: $this->order,
            sensitive: $this->sensitive,
            dependsOn: $this->dependsOn,
            arrayItemType: $this->arrayItemType
        );
    }

    public function getLabel(): string
    {
        return $this->label ?: ucfirst(str_replace('_', ' ', $this->name));
    }
}