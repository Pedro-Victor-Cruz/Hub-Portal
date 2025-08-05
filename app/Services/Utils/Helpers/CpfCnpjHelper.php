<?php

namespace App\Services\Utils\Helpers;

class CpfCnpjHelper
{

    public static function format(string $document, string $type = 'auto'): string
    {
        $clean = CpfCnpjHelper::unformat($document);

        return match($type) {
            'cpf' => CpfCnpjHelper::formatCpf($clean),
            'cnpj' => CpfCnpjHelper::formatCnpj($clean),
            'auto' => strlen($clean) === 11 ?
                CpfCnpjHelper::formatCpf($clean) :
                CpfCnpjHelper::formatCnpj($clean),
            default => $document
        };
    }

    public static function formatCpf(string $cpf): string
    {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    public static function formatCnpj(string $cnpj): string
    {
        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }

    public static function unformat(string $document): string
    {
        return preg_replace('/[^0-9]/', '', $document);
    }

    public static function isValid(string $document): bool
    {
        $document = self::unformat($document);
        return strlen($document) === 11 ? self::validateCpf($document) : self::validateCnpj($document);
    }

    private static function validateCpf(string $cpf): bool
    {
        // Elimina possíveis mascaras
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se o número de dígitos informados é igual a 11
        if (strlen($cpf) != 11) return false;
        return true;
    }

    private static function validateCnpj(string $cnpj): bool
    {
        // Elimina possíveis mascaras
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Verifica se o número de dígitos informados é igual a 14
        if (strlen($cnpj) != 14) return false;
        return true;
    }

    /**
     * Gera um CPF válido (para testes)
     */
    public static function generateCpf(bool $formatted = false): string
    {
        $n1 = rand(0, 9);
        $n2 = rand(0, 9);
        $n3 = rand(0, 9);
        $n4 = rand(0, 9);
        $n5 = rand(0, 9);
        $n6 = rand(0, 9);
        $n7 = rand(0, 9);
        $n8 = rand(0, 9);
        $n9 = rand(0, 9);

        $d1 = $n9 * 2 + $n8 * 3 + $n7 * 4 + $n6 * 5 + $n5 * 6 + $n4 * 7 + $n3 * 8 + $n2 * 9 + $n1 * 10;
        $d1 = 11 - ($d1 % 11);
        if ($d1 >= 10) $d1 = 0;

        $d2 = $d1 * 2 + $n9 * 3 + $n8 * 4 + $n7 * 5 + $n6 * 6 + $n5 * 7 + $n4 * 8 + $n3 * 9 + $n2 * 10 + $n1 * 11;
        $d2 = 11 - ($d2 % 11);
        if ($d2 >= 10) $d2 = 0;

        $cpf = "{$n1}{$n2}{$n3}{$n4}{$n5}{$n6}{$n7}{$n8}{$n9}{$d1}{$d2}";

        return $formatted ? self::formatCpf($cpf) : $cpf;
    }

    /**
     * Gera um CNPJ válido (para testes)
     */
    public static function generateCnpj(bool $formatted = false): string
    {
        $n1 = rand(0, 9);
        $n2 = rand(0, 9);
        $n3 = rand(0, 9);
        $n4 = rand(0, 9);
        $n5 = rand(0, 9);
        $n6 = rand(0, 9);
        $n7 = rand(0, 9);
        $n8 = rand(0, 9);
        $n9 = 0;
        $n10 = 0;
        $n11 = 0;
        $n12 = 1;

        $d1 = $n12 * 2 + $n11 * 3 + $n10 * 4 + $n9 * 5 + $n8 * 6 + $n7 * 7 + $n6 * 8 + $n5 * 9 + $n4 * 2 + $n3 * 3 + $n2 * 4 + $n1 * 5;
        $d1 = 11 - ($d1 % 11);
        if ($d1 >= 10) $d1 = 0;

        $d2 = $d1 * 2 + $n12 * 3 + $n11 * 4 + $n10 * 5 + $n9 * 6 + $n8 * 7 + $n7 * 8 + $n6 * 9 + $n5 * 2 + $n4 * 3 + $n3 * 4 + $n2 * 5 + $n1 * 6;
        $d2 = 11 - ($d2 % 11);
        if ($d2 >= 10) $d2 = 0;

        $cnpj = "{$n1}{$n2}{$n3}{$n4}{$n5}{$n6}{$n7}{$n8}{$n9}{$n10}{$n11}{$n12}{$d1}{$d2}";

        return $formatted ? self::formatCnpj($cnpj) : $cnpj;
    }
}