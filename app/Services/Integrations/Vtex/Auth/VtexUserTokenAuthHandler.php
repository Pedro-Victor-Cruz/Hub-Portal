<?php

namespace App\Services\Integrations\Vtex\Auth;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Core\BaseAuthHandler;

/**
 * Manipulador de autenticação para VTEX usando User Token (VtexIdclientAutCookie).
 *
 * Este método usa o cookie de autenticação do usuário.
 * É útil para testes ou quando você já tem o token de sessão de um usuário.
 *
 * ATENÇÃO: User tokens têm tempo de expiração e devem ser renovados periodicamente.
 */
class VtexUserTokenAuthHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'user_token';
    protected int $defaultTokenTtl = 1800; // 30 minutos

    public function getAuthType(): string
    {
        return self::AUTH_TYPE;
    }

    /**
     * Para User Token, o token já foi fornecido pelo usuário.
     * Apenas validamos e salvamos no cache.
     */
    protected function performAuthentication(): bool
    {
        $this->validateSettings(['user_token', 'account_name']);

        $userToken = $this->integration->getConfig('user_token');

        if (empty($userToken)) {
            throw new ErpAuthenticationException(
                'User Token é obrigatório para este tipo de autenticação'
            );
        }

        // Valida o formato básico do token
        if (strlen($userToken) < 20) {
            throw new ErpAuthenticationException(
                'User Token com formato inválido'
            );
        }

        // Valida se o token está ativo fazendo uma requisição de teste
        if (!$this->validateToken($userToken)) {
            throw new ErpAuthenticationException(
                'User Token inválido ou expirado'
            );
        }

        // Salva o token no cache
        $this->saveTokenToCache($userToken, $this->defaultTokenTtl);

        return true;
    }

    /**
     * Valida se o token está ativo e válido
     */
    protected function validateToken(string $token): bool
    {
        try {
            $accountName = $this->integration->getConfig('account_name');

            // Faz uma requisição simples para validar o token
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'VtexIdclientAutCookie' => $token,
                    'Accept' => 'application/json',
                ])
                ->get("https://{$accountName}.vtexcommercestable.com.br/api/sessions");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Retorna o token do cache
     */
    public function getAuthToken(): mixed
    {
        $token = parent::getAuthToken();

        // Se não há token em cache ou está expirado, tenta autenticar novamente
        if (!$token) {
            $this->authenticate();
            $token = parent::getAuthToken();
        }

        return $token;
    }

    /**
     * Verifica se o token ainda é válido
     */
    public function isTokenValid(): bool
    {
        $token = parent::getAuthToken();

        if (!$token) {
            return false;
        }

        return $this->validateToken($token);
    }

    /**
     * Gera o cache key específico para este tipo de autenticação
     */
    protected function generateCacheKey(): string
    {
        $accountName = $this->integration->getConfig('account_name', 'default');
        $tokenHash = substr(md5($this->integration->getConfig('user_token', '')), 0, 8);

        return "vtex_auth_user_token_{$accountName}_{$tokenHash}";
    }
}