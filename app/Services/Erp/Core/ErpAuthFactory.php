<?php

namespace App\Services\Erp\Core;

use App\Contracts\Erp\ErpAuthInterface;
use App\Exceptions\Erp\ErpAuthenticationException;
use App\Models\CompanyErpSetting;
use App\Services\Erp\Drivers\Sankhya\Auth\SankhyaJsonAuthHandler;
use App\Services\Erp\Drivers\Sankhya\Auth\SankhyaMobileLoginHandler;

/**
 * Factory responsável por criar instâncias dos manipuladores de autenticação
 * baseado no tipo de ERP e método de autenticação configurado.
 */
class ErpAuthFactory
{
    /**
     * Mapeamento de ERPs e seus tipos de autenticação suportados.
     * Cada ERP pode ter múltiplos tipos de autenticação disponíveis.
     */
    private const AUTH_HANDLERS = [
        'SANKHYA' => [
            'session' => SankhyaMobileLoginHandler::class,
            'token' => SankhyaJsonAuthHandler::class,
        ],
    ];

    /**
     * Cria uma instância do manipulador de autenticação apropriado
     * baseado nas configurações da empresa.
     *
     * @param CompanyErpSetting $settings Configurações do ERP da empresa
     * @return ErpAuthInterface Instância do manipulador de autenticação
     * @throws ErpAuthenticationException Se o tipo de autenticação não for suportado
     */
    public static function create(CompanyErpSetting $settings): ErpAuthInterface
    {
        $erpName = $settings->erp_name;
        $authType = $settings->auth_type;

        if (!isset(self::AUTH_HANDLERS[$erpName])) {
            throw new ErpAuthenticationException(
                "ERP '{$erpName}' não é suportado para autenticação"
            );
        }

        if (!isset(self::AUTH_HANDLERS[$erpName][$authType])) {
            $supportedTypes = implode(', ', array_keys(self::AUTH_HANDLERS[$erpName]));
            throw new ErpAuthenticationException(
                "Tipo de autenticação '{$authType}' não é suportado para o ERP '{$erpName}'. " .
                "Tipos suportados: {$supportedTypes}"
            );
        }

        $handlerClass = self::AUTH_HANDLERS[$erpName][$authType];

        return new $handlerClass($settings);
    }

    /**
     * Retorna todos os tipos de autenticação suportados para um ERP específico.
     *
     * @param string $erpName Nome do ERP
     * @return array Lista de tipos de autenticação suportados
     */
    public static function getSupportedAuthTypes(string $erpName): array
    {
        return array_keys(self::AUTH_HANDLERS[$erpName] ?? []);
    }

    /**
     * Verifica se um ERP e tipo de autenticação são suportados.
     *
     * @param string $erpName Nome do ERP
     * @param string $authType Tipo de autenticação
     * @return bool True se é suportado, false caso contrário
     */
    public static function isAuthTypeSupported(string $erpName, string $authType): bool
    {
        return isset(self::AUTH_HANDLERS[$erpName][$authType]);
    }
}