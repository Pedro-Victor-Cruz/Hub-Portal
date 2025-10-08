<?php

namespace App\Services\Integrations\Vtex\Auth;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Core\BaseAuthHandler;

/**
 * Manipulador de autenticação para VTEX usando App Key e App Token.
 *
 * Este é o método recomendado pela VTEX para integrações.
 * As credenciais são enviadas como headers em cada requisição.
 */
class VtexAppKeyAuthHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'app_key';
    protected int $defaultTokenTtl = 86400; // 24 horas (credenciais não expiram, mas cache sim)

    public function getAuthType(): string
    {
        return self::AUTH_TYPE;
    }

    /**
     * Para App Key/Token, não há processo de autenticação separado.
     * As credenciais são enviadas diretamente nos headers de cada requisição.
     */
    protected function performAuthentication(): bool
    {
        $this->validateSettings(['app_key', 'app_token']);

        $appKey = $this->integration->getConfig('app_key');
        $appToken = $this->integration->getConfig('app_token');

        if (empty($appKey) || empty($appToken)) {
            throw new ErpAuthenticationException(
                'App Key e App Token são obrigatórios para autenticação VTEX'
            );
        }

        // Valida o formato básico das credenciais
        if (strlen($appKey) < 10 || strlen($appToken) < 10) {
            throw new ErpAuthenticationException(
                'App Key ou App Token com formato inválido'
            );
        }

        // Para este tipo de autenticação, salvamos as credenciais no cache
        // Elas serão usadas diretamente nos headers de cada requisição
        $credentials = [
            'app_key' => $appKey,
            'app_token' => $appToken,
        ];

        $this->saveTokenToCache($credentials, $this->defaultTokenTtl);

        return true;
    }

    /**
     * Retorna as credenciais do cache
     * @throws \Exception
     */
    public function getAuthToken(): mixed
    {
        $credentials = parent::getAuthToken();

        // Se não há credenciais em cache, autentica novamente
        if (!$credentials) {
            $this->authenticate();
            $credentials = parent::getAuthToken();
        }

        return $credentials;
    }

    /**
     * Valida se as credenciais estão corretas fazendo uma requisição de teste
     */
    public function validateCredentials(): bool
    {
        try {
            $this->validateSettings(['app_key', 'app_token', 'account_name']);

            $appKey = $this->integration->getConfig('app_key');
            $appToken = $this->integration->getConfig('app_token');
            $accountName = $this->integration->getConfig('account_name');

            // Faz uma requisição simples para validar as credenciais
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'X-VTEX-API-AppKey' => $appKey,
                    'X-VTEX-API-AppToken' => $appToken,
                    'Accept' => 'application/json',
                ])
                ->get("https://{$accountName}.vtexcommercestable.com.br/api/license-manager/pvt/accounts");

            if (!$response->successful()) {
                $statusCode = $response->status();

                if ($statusCode === 401 || $statusCode === 403) {
                    throw new ErpAuthenticationException(
                        'Credenciais VTEX inválidas ou sem permissão'
                    );
                }

                throw new ErpAuthenticationException(
                    "Falha ao validar credenciais VTEX. Status: {$statusCode}"
                );
            }

            return true;
        } catch (ErpAuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ErpAuthenticationException(
                "Erro ao validar credenciais VTEX: {$e->getMessage()}"
            );
        }
    }

    /**
     * Gera o cache key específico para este tipo de autenticação
     */
    protected function generateCacheKey(): string
    {
        $accountName = $this->integration->getConfig('account_name', 'default');
        $appKey = $this->integration->getConfig('app_key', 'unknown');

        // Usa apenas os primeiros 8 caracteres do app_key para o cache key
        $appKeyPrefix = substr($appKey, 0, 8);

        return "vtex_auth_app_key_{$accountName}_{$appKeyPrefix}";
    }
}