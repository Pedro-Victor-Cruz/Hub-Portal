<?php

namespace App\Services\Utils\ResponseFormatters;

/**
 * Classe para formatar respostas específicas do Sankhya
 */
class ResponseFormatter
{
    /**
     * Formata a resposta do DbExplorerSP.executeQuery
     */
    public static function formatExecuteQueryResponse(array $response): array
    {
        // Verifica se existem metadados e linhas na resposta
        if (!isset($response['fieldsMetadata']) || !isset($response['rows'])) {
            return $response;
        }

        $formattedData = [];

        // Processa cada linha de dados
        foreach ($response['rows'] as $row) {
            $item = [];

            // Combina os nomes dos campos com os valores da linha
            foreach ($row as $index => $value) {
                // Verifica se o campo existe nos metadados
                if (isset($response['fieldsMetadata'][$index])) {
                    $fieldMeta = $response['fieldsMetadata'][$index];
                    $fieldName = $fieldMeta['name'];

                    // Converte o valor para o formato esperado
                    $value = self::parseValue($value);

                    // Formatação especial para campos do tipo H (data/hora)
                    if (isset($fieldMeta['userType']) && $fieldMeta['userType'] === 'H') {
                        $value = self::formatDateTime($value);
                    }

                    $item[$fieldName] = $value;
                }
            }

            $formattedData[] = $item;
        }

        return $formattedData;
    }

    /**
     * Formata a resposta do CRUDServiceProvider.loadView
     */
    public static function formatLoadViewResponse(array $response): array
    {
        if (empty($response['records'])) return [];

        $records = $response['records']['record'];

        if (!isset($records[0])) $records = [$records];

        return array_map(function ($record) {
            return self::formatRecordDynamically($record);
        }, $records);
    }

    /**
     * Formatar cada registro individualmente de forma dinâmica.
     *
     * @param array $record
     * @return array
     */
    public static function formatRecordDynamically(array $record): array
    {
        $formattedRecord = [];

        foreach ($record as $key => $value) {
            if (is_array($value)) {
                // Caso o array contenha o campo '$', processa o valor real
                if (isset($value['$'])) {
                    $formattedRecord[$key] = self::parseValue($value['$']);
                } elseif (empty($value)) {
                    $formattedRecord[$key] = null;  // Array vazio tratado como null
                } else {
                    $formattedRecord[$key] = $value;  // Mantém array não vazio
                }
            } else {
                // Se não for array, processa diretamente
                $formattedRecord[$key] = self::parseValue($value);
            }
        }

        return $formattedRecord;
    }

    /**
     * Converte valores conforme necessário
     */
    public static function parseValue(mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (is_string($value)) {
            // Remove espaços em branco extras
            $value = trim($value);

            // Tenta converter para número se aplicável
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float)$value : (int)$value;
            }
        }

        return $value;
    }

    /**
     * Formata data/hora do Sankhya
     * Converte de "02062025 11:47:58" para "02/06/2025 11:47:58"
     */
    public static function formatDateTime(?string $value): ?string
    {
        if (!$value || !is_string($value) || strlen($value) < 17) {
            return $value;
        }

        $datePart = substr($value, 0, 8);
        $timePart = substr($value, 9, 8);

        if (strlen($datePart) !== 8 || strlen($timePart) !== 8) {
            return $value;
        }

        return sprintf(
            '%s/%s/%s %s',
            substr($datePart, 0, 2),
            substr($datePart, 2, 2),
            substr($datePart, 4, 4),
            $timePart
        );
    }

    /**
     * Formata apenas data do Sankhya
     * Converte de "02062025" para "02/06/2025"
     */
    public static function formatDate(?string $value): ?string
    {
        if (!$value || !is_string($value) || strlen($value) !== 8) {
            return $value;
        }

        return sprintf(
            '%s/%s/%s',
            substr($value, 0, 2),
            substr($value, 2, 2),
            substr($value, 4, 4)
        );
    }

    /**
     * Formata valores monetários
     */
    public static function formatCurrency(?float $value, string $currency = 'BRL'): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return match ($currency) {
            'BRL' => 'R$ ' . number_format($value, 2, ',', '.'),
            'USD' => '$ ' . number_format($value, 2, '.', ','),
            'EUR' => '€ ' . number_format($value, 2, ',', '.'),
            default => number_format($value, 2, '.', ','),
        };
    }

    /**
     * Formata percentual
     */
    public static function formatPercentage(?float $value, int $decimals = 2): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return number_format($value, $decimals, ',', '.') . '%';
    }

    /**
     * Aplica formatação customizada baseada no tipo
     */
    public static function applyCustomFormat(mixed $value, string $format): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return match ($format) {
            'date' => self::formatDate($value),
            'datetime' => self::formatDateTime($value),
            'currency' => self::formatCurrency($value),
            'currency_usd' => self::formatCurrency($value, 'USD'),
            'currency_eur' => self::formatCurrency($value, 'EUR'),
            'percentage' => self::formatPercentage($value),
            'upper' => strtoupper($value),
            'lower' => strtolower($value),
            'capitalize' => ucwords(strtolower($value)),
            'trim' => trim($value),
            default => $value,
        };
    }
}