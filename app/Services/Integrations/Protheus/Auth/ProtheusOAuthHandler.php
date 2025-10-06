<?php

namespace App\Services\Integrations\Protheus\Auth;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Core\BaseAuthHandler;
use Illuminate\Support\Facades\Http;

/**
 * Manipulador de autenticação OAuth 2.0 para TOTVS Protheus
 */
class ProtheusOAuthHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'oauth';
    protected int $defaultTokenTtl = 3600; // 1 hora

    public function getAuthType(): string
    {
        return self::AUTH_TYPE;
    }

    protected function performAuthentication(): bool
    {
        $this->validateSettings(['client_id', 'client_secret', 'base_url']);

        $baseUrl = rtrim($this->integration->getConfig('base_url'), '/');
        $clientId = $this->integration->getConfig('client_id');
        $clientSecret = $this->integration->getConfig('client_secret');

        // Endpoint padrão de autenticação OAuth do Protheus
        $authUrl = "{$baseUrl}/api/oauth2/v1/token";

        try {
            $response = Http::timeout(30)
                ->asForm()
                ->post($authUrl, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);

            if (!$response->successful()) {
                $error = $response->json();
                throw new ErpAuthenticationException(
                    "Falha na autenticação OAuth. Status: {$response->status()}. " .
                    "Erro: " . ($error['error_description'] ?? $error['error'] ?? 'Erro desconhecido')
                );
            }

            $data = $response->json();

            if (isset($data['error'])) {
                throw new ErpAuthenticationException(
                    "Erro do Protheus: " . ($data['error_description'] ?? $data['error'])
                );
            }

            $accessToken = $data['access_token'] ?? null;
            if (empty($accessToken)) {
                throw new ErpAuthenticationException('Access token não retornado pelo servidor');
            }

            // TTL do token (se fornecido, caso contrário usa o padrão)
            $expiresIn = $data['expires_in'] ?? $this->defaultTokenTtl;

            $this->saveTokenToCache($accessToken, $expiresIn);
            return true;

        } catch (ErpAuthenticationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ErpAuthenticationException(
                "Erro ao autenticar com Protheus: " . $e->getMessage()
            );
        }
    }

    /**
     * Valida se os parâmetros necessários estão configurados
     */
    protected function validateSettings(array $required): void
    {
        $missing = [];

        foreach ($required as $param) {
            if (empty($this->integration->getConfig($param))) {
                $missing[] = $param;
            }
        }

        if (!empty($missing)) {
            throw new ErpAuthenticationException(
                'Parâmetros obrigatórios não configurados: ' . implode(', ', $missing)
            );
        }
    }
}