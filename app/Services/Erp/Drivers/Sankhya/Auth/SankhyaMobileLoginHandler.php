<?php

namespace App\Services\Erp\Drivers\Sankhya\Auth;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Erp\Core\BaseAuthHandler;
use Illuminate\Support\Facades\Http;

/**
 * Manipulador de autenticação para Sankhya usando o método Mobile Login.
 * Este método usa o serviço MobileLoginSP.login do Sankhya.
 */
class SankhyaMobileLoginHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'mobile_login';
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
     * Realiza a autenticação usando o serviço Mobile Login do Sankhya.
     *
     * @return bool True se a autenticação foi bem-sucedida
     * @throws ErpAuthenticationException
     */
    protected function performAuthentication(): bool
    {
        // Valida configurações obrigatórias
        $this->validateSettings(['base_url', 'username', 'secret_key']);

        $response = Http::timeout(30)->post($this->settings->base_url . '/mge/service.sbr?serviceName=MobileLoginSP.login&outputType=json', [
            'serviceName' => 'MobileLoginSP.login',
            'requestBody' => [
                'NOMUSU' => ['$' => $this->settings->username],
                'INTERNO' => ['$' => $this->settings->secret_key],
                'KEEPCONNECTED' => ['$' => 'S'],
            ],
        ]);

        if (!$response->successful()) {
            throw new ErpAuthenticationException(
                "Falha na requisição de autenticação. Status: {$response->status()}"
            );
        }

        $responseData = $response->json();

        // Verifica se houve erro na resposta
        if (isset($responseData['status']) && $responseData['status'] !== '1') {
            $errorMessage = $responseData['statusMessage'] ?? 'Erro desconhecido na autenticação';
            throw new ErpAuthenticationException("Erro do Sankhya: {$errorMessage}");
        }

        $token = $responseData['responseBody']['jsessionid']['$'] ?? null;



        if (empty($token)) {
            throw new ErpAuthenticationException('Token não retornado pelo servidor Sankhya. Erro json: ' . json_encode($responseData));
        }

        // Salva o token no cache
        $this->saveTokenToCache($token, self::TOKEN_TTL_SECONDS);

        return true;
    }

}