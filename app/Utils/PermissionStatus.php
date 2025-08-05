<?php

namespace App\Utils;

use InvalidArgumentException;

enum PermissionStatus: int
{
    case USER = 1;
    case ADMINISTRATOR = 2;
    case SUPER_ADMINISTRATOR = 3;

    /**
     * Obtém o nome legível do nível de acesso
     */
    public function label(): string
    {
        return match($this) {
            self::USER => 'Usuário',
            self::ADMINISTRATOR => 'Administrador',
            self::SUPER_ADMINISTRATOR => 'Super Administrador',
        };
    }

    /**
     * Obtém a descrição detalhada do nível de acesso
     */
    public function description(): string
    {
        return match($this) {
            self::USER => 'Usuário comum do sistema',
            self::ADMINISTRATOR => 'Administrador do sistema',
            self::SUPER_ADMINISTRATOR => 'Acesso completo ao sistema',
        };
    }

    /**
     * Verifica se este nível pode gerenciar outro nível
     */
    public function canManage(self $targetLevel): bool
    {
        return $this->value >= $targetLevel->value;
    }

    /**
     * Obtém todos os níveis que este nível pode gerenciar
     *
     * @return array<self>
     */
    public function manageableLevels(): array
    {
        return array_filter(
            self::cases(),
            fn($level) => $this->canManage($level)
        );
    }

    /**
     * Obtém os níveis que o usuário pode gerenciar
     *
     * @param self $userLevel Nível do usuário atual
     * @return array<self> Níveis que o usuário pode gerenciar
     */
    public static function getManageableLevels(self $userLevel): array
    {
        return array_filter(
            self::cases(),
            fn(self $level) => $userLevel->canManage($level)
        );
    }

    /**
     * Obtém os níveis que o usuário pode gerenciar como array associativo
     *
     * @param self $userLevel Nível do usuário atual
     * @return array<array{value: int, name: string, label: string, description: string}>
     */
    public static function getManageableLevelsArray(self $userLevel): array
    {
        return array_map(
            fn(self $level) => [
                'value' => $level->value,
                'name' => $level->name,
                'label' => $level->label(),
                'description' => $level->description(),
            ],
            self::getManageableLevels($userLevel)
        );
    }

    /**
     * Obtém o Enum a partir de um valor inteiro
     *
     * @throws InvalidArgumentException Se o valor for inválido
     */
    public static function fromValue(int $value): self
    {
        return match($value) {
            self::USER->value => self::USER,
            self::ADMINISTRATOR->value => self::ADMINISTRATOR,
            self::SUPER_ADMINISTRATOR->value => self::SUPER_ADMINISTRATOR,
            default => throw new InvalidArgumentException("Nível de acesso inválido: {$value}"),
        };
    }

    /**
     * Obtém o Enum a partir de um nome (case-insensitive)
     *
     * @throws InvalidArgumentException Se o nome for inválido
     */
    public static function fromName(string $name): self
    {
        return match(strtoupper($name)) {
            'USER' => self::USER,
            'ADMINISTRATOR' => self::ADMINISTRATOR,
            'SUPER_ADMINISTRATOR' => self::SUPER_ADMINISTRATOR,
            default => throw new InvalidArgumentException("Nome de nível de acesso inválido: {$name}"),
        };
    }

    /**
     * Obtém todos os casos como array associativo [value => label]
     */
    public static function toArray(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn($case) => $case->label(), self::cases())
        );
    }

    /**
     * Obtém todos os casos com todos os dados
     */
    public static function toFullArray(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'name' => $case->name,
                'label' => $case->label(),
                'description' => $case->description(),
            ],
            self::cases()
        );
    }
}