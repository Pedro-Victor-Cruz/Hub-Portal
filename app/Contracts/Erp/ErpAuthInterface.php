<?php

namespace App\Contracts\Erp;

use App\Exceptions\Erp\ErpAuthenticationException;

/**
 * Interface para autenticação em sistemas ERP.
 * Define os métodos básicos que todos os manipuladores de autenticação devem implementar.
 */
interface ErpAuthInterface
{
    /**
     * Realiza a autenticação no sistema ERP.
     *
     * @return bool True se a autenticação foi bem-sucedida, false caso contrário
     * @throws ErpAuthenticationException
     */
    public function authenticate(): bool;

    /**
     * Renova o token de autenticação.
     * Remove o token atual do cache e realiza uma nova autenticação.
     *
     * @return bool True se a renovação foi bem-sucedida, false caso contrário
     * @throws ErpAuthenticationException
     */
    public function refreshToken(): bool;

    /**
     * Retorna o token de autenticação atual.
     * Se não houver token válido, tentará realizar a autenticação automaticamente.
     *
     * @return string Token de autenticação
     * @throws ErpAuthenticationException
     */
    public function getToken(): string;

    /**
     * Verifica se o token atual ainda é válido.
     *
     * @return bool True se o token é válido, false caso contrário
     */
    public function isTokenValid(): bool;

    /**
     * Retorna a chave de cache única para este manipulador de autenticação.
     *
     * @return string Chave do cache
     */
    public function getCacheKey(): string;

    /**
     * Retorna o tipo de autenticação suportado por este manipulador.
     *
     * @return string Tipo de autenticação (ex: 'mobile_login', 'json_auth', 'oauth')
     */
    public function getAuthType(): string;

}