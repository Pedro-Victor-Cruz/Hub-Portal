<?php

namespace App\Enums;

/**
 * Tipos de filtros suportados para consultas dinâmicas
 */
enum FilterType: string
{
    case TEXT = 'text';
    case NUMBER = 'number';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case SELECT = 'select';
    case MULTISELECT = 'multiselect';
    case ARRAY = 'array';

    /**
     * Retorna a descrição amigável do tipo
     */
    public function getDescription(): string
    {
        return match($this) {
            self::TEXT => 'Texto',
            self::NUMBER => 'Número',
            self::BOOLEAN => 'Verdadeiro/Falso',
            self::DATE => 'Data',
            self::SELECT => 'Seleção única',
            self::MULTISELECT => 'Seleção múltipla',
            self::ARRAY => 'Lista/Array',
        };
    }

    /**
     * Retorna o tipo HTML apropriado para o campo
     */
    public function getHtmlInputType(): string
    {
        return match($this) {
            self::TEXT => 'text',
            self::NUMBER => 'number',
            self::BOOLEAN => 'checkbox',
            self::DATE => 'date',
            self::SELECT, self::MULTISELECT => 'select',
            self::ARRAY => 'textarea',
        };
    }

    /**
     * Verifica se o tipo suporta múltiplos valores
     */
    public function isMultiple(): bool
    {
        return in_array($this, [self::MULTISELECT, self::ARRAY]);
    }

    /**
     * Verifica se o tipo precisa de opções
     */
    public function requiresOptions(): bool
    {
        return in_array($this, [self::SELECT, self::MULTISELECT]);
    }

    /**
     * Retorna o valor padrão apropriado para o tipo
     */
    public function getDefaultValue(): mixed
    {
        return match($this) {
            self::TEXT => '',
            self::NUMBER => 0,
            self::BOOLEAN => false,
            self::DATE, self::SELECT => null,
            self::MULTISELECT, self::ARRAY => [],
        };
    }

    /**
     * Retorna todos os tipos disponíveis para seleção
     */
    public static function getOptions(): array
    {
        return array_map(fn($type) => [
            'value' => $type->value,
            'label' => $type->getDescription(),
            'description' => $type->getDescription(),
            'requiresOptions' => $type->requiresOptions(),
            'defaultValue' => $type->getDefaultValue(),
        ], self::cases());
    }
}