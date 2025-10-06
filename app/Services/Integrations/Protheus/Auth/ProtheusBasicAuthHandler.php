<?php

namespace App\Services\Integrations\Protheus\Auth;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Core\BaseAuthHandler;
use Illuminate\Support\Facades\Http;

/**
 * Manipulador de autenticação Basic Auth para TOTVS Protheus
 */
class ProtheusBasicAuthHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'basic';
    protected int $defaultTokenTtl = 86400; // 24 horas (Basic Auth não expira)

    public function getAuthType(): string
    {
        return self::AUTH_TYPE;
    }

    /**
     * @throws ErpAuthenticationException
     */
    protected function performAuthentication(): bool
    {
        $this->validateSettings(['username', 'password', 'base_url']);

        $username = $this->integration->getConfig('username');
        $password = $this->integration->getConfig('password');
        $baseUrl = rtrim($this->integration->getConfig('base_url'), '/');

        // Gera o token Basic Auth
        $basicToken = base64_encode("{$username}:{$password}");

        // Testa a autenticação fazendo uma requisição simples
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Basic {$basicToken}",
                    'Accept' => 'application/json',
                ])
                ->get("{$baseUrl}/api/framework/v1/health");

            // Se não retornar 401/403, considera autenticado
            if ($response->status() === 401 || $response->status() === 403) {
                throw new ErpAuthenticationException(
                    "Credenciais inválidas. Status: {$response->status()}"
                );
            }

            // Salva o token no cache
            $this->saveTokenToCache($basicToken, $this->defaultTokenTtl);
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