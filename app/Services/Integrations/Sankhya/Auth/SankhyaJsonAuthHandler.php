<?php

namespace App\Services\Integrations\Sankhya\Auth;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Core\BaseAuthHandler;
use Illuminate\Support\Facades\Http;

/**
 * Manipulador de autenticação para Sankhya usando o método JSON Auth.
 * Este método usa headers para autenticação (token, appkey, username, password).
 */
class SankhyaJsonAuthHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'json_auth';
    protected int $defaultTokenTtl = 1800; // 30 minutos

    public function getAuthType(): string
    {
        return self::AUTH_TYPE;
    }

    protected function performAuthentication(): bool
    {
        $this->validateSettings(['token']);

        $response = Http::timeout(30)
            ->withHeaders([
                'token' => $this->integration->getConfig('token'),
                'appkey' => config('erp.settings.SANKHYA.appkey'),
                'username' => config('erp.settings.SANKHYA.username'),
                'password' => config('erp.settings.SANKHYA.password'),
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.sankhya.com.br/login', []);

        if (!$response->successful()) {
            throw new ErpAuthenticationException(
                "Falha na autenticação JSON. Status: {$response->status()}"
            );
        }

        $data = $response->json();

        if (isset($data['error']) && $data['error']) {
            throw new ErpAuthenticationException(
                "Erro do Sankhya: " . ($data['message'] ?? 'Erro desconhecido')
            );
        }

        $sessionToken = $data['bearerToken'] ?? null;
        if (empty($sessionToken)) {
            throw new ErpAuthenticationException('Bearer token não retornado pelo servidor');
        }

        $this->saveTokenToCache($sessionToken, $this->defaultTokenTtl);
        return true;
    }
}