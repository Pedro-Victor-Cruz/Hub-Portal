<?php

namespace App\Services\Erp\Drivers\Handlers\Auth\Sankhya;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Erp\Drivers\Handlers\Auth\BaseAuthHandler;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Manipulador de autenticação para Sankhya usando o método JSON Auth.
 * Este método usa headers para autenticação (token, appkey, username, password).
 */
class SankhyaJsonAuthHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'token_auth';
    private const TOKEN_TTL_SECONDS = 300; // 5 minutos

    /**
     * Retorna o tipo de autenticação.
     *
     * @return string
     */
    public function getAuthType(): string
    {
        return self::AUTH_TYPE;
    }

    /**
     * Realiza a autenticação usando headers JSON no Sankhya.
     *
     * @return bool True se a autenticação foi bem-sucedida
     * @throws ErpAuthenticationException
     */
    protected function performAuthentication(): bool
    {
        // Valida configurações obrigatórias
        $this->validateSettings(['token']);

        // Validando configurações de autenticação
        $appKey = $this->settings->getErpConfig('appkey');
        if (empty($appKey)) {
            throw new ErpAuthenticationException('Chave do aplicativo (appkey) não configurada.');
        }

        $username = $this->settings->getErpConfig('username');
        if (empty($username)) {
            throw new ErpAuthenticationException('Nome de usuário (username) não configurado.');
        }

        $password = $this->settings->getErpConfig('password');
        if (empty($password)) {
            throw new ErpAuthenticationException('Senha (password) não configurada.');
        }

        $response = Http::timeout(30)
            ->withHeaders([
                'token' => $this->settings->token,
                'appkey' => $appKey,
                'username' => $username,
                'password' => $password,
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.sankhya.com.br/login', []);

        if (!$response->successful()) {
            throw new ErpAuthenticationException(
                "Falha na requisição de autenticação JSON. Status: {$response->status()}"
            );
        }

        $responseData = $response->json();

        // Verifica se a resposta contém erro
        if (isset($responseData['error']) && $responseData['error']) {
            $errorMessage = $responseData['message'] ?? 'Erro desconhecido na autenticação JSON';
            throw new ErpAuthenticationException("Erro do Sankhya: {$errorMessage}");
        }

        // Para JSON auth, geralmente o token retornado é um session token
        $sessionToken = $responseData['bearerToken'] ?? null;

        if (empty($sessionToken)) {
            throw new ErpAuthenticationException('Token de sessão não retornado pelo servidor Sankhya');
        }

        // Salva o token de sessão no cache
        $this->saveTokenToCache($sessionToken, self::TOKEN_TTL_SECONDS);

        return true;
    }

}