<?php

namespace App\Services\Integrations\Nuvemshop\Auth;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Core\BaseAuthHandler;
use Illuminate\Support\Facades\Http;

/**
 * Handler de autenticação OAuth2 para Nuvemshop
 *
 * A Nuvemshop usa OAuth2 com access tokens que não expiram automaticamente.
 * Tokens só são invalidados se:
 * - O app for desinstalado da loja
 * - As permissões forem revogadas
 * - Um novo token for gerado
 *
 * @see https://tiendanube.github.io/api-documentation/authentication
 */
class NuvemshopOAuthHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'oauth2';

    // Tokens da Nuvemshop não expiram, mas mantemos cache por 30 dias
    protected int $defaultTokenTtl = 2592000; // 30 dias

    public function getAuthType(): string
    {
        return self::AUTH_TYPE;
    }

    /**
     * Valida se o access token está configurado e funcionando
     */
    protected function performAuthentication(): bool
    {
        $this->validateSettings(['access_token', 'store_id']);

        $accessToken = $this->integration->getConfig('access_token');
        $storeId = $this->integration->getConfig('store_id');
        $domain = $this->integration->getConfig('domain', 'nuvemshop');

        // Monta URL base da API
        $baseUrl = $domain === 'tiendanube'
            ? 'https://api.tiendanube.com'
            : 'https://api.nuvemshop.com.br';

        $apiVersion = '2025-03';
        $url = "{$baseUrl}/{$apiVersion}/{$storeId}/store";

        // Testa o token fazendo uma requisição à API
        $response = Http::timeout(30)
            ->withHeaders([
                'Authentication' => "bearer {$accessToken}",
                'User-Agent' => $this->integration->getUserAgent(),
                'Accept' => 'application/json',
            ])
            ->get($url);

        if (!$response->successful()) {
            throw new ErpAuthenticationException(
                "Falha na autenticação OAuth2. Status: {$response->status()}"
            );
        }

        // Valida se o store_id corresponde ao token
        $data = $response->json();
        if (!isset($data['id']) || $data['id'] != $storeId) {
            throw new ErpAuthenticationException(
                "Store ID não corresponde ao access token fornecido"
            );
        }

        // Salva o token no cache (mesmo que não expire, mantemos em cache)
        $this->saveTokenToCache($accessToken, $this->defaultTokenTtl);

        return true;
    }

    /**
     * Renova o access token usando refresh token (se disponível)
     *
     * Nota: A Nuvemshop não usa refresh tokens por padrão.
     * Tokens só são renovados se o app implementar o fluxo completo de OAuth.
     */
    protected function renewToken(): bool
    {
        // Verifica se temos as credenciais OAuth para renovação
        $clientId = $this->integration->getConfig('client_id');
        $clientSecret = $this->integration->getConfig('client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            // Sem credenciais OAuth, não podemos renovar
            // O usuário precisa reautorizar o app manualmente
            throw new ErpAuthenticationException(
                'Token expirado e sem credenciais OAuth para renovação. ' .
                'Reautorize o aplicativo na Nuvemshop.'
            );
        }

        // Se implementarmos renovação via OAuth, seria aqui
        // Por enquanto, apenas relançamos a exceção
        throw new ErpAuthenticationException(
            'Renovação automática de token não implementada. ' .
            'Reautorize o aplicativo na Nuvemshop.'
        );
    }



}