<?php

namespace App\Services\Integrations\Sankhya\Auth;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Services\Core\BaseAuthHandler;
use Illuminate\Support\Facades\Http;


class SankhyaMobileLoginHandler extends BaseAuthHandler
{
    private const AUTH_TYPE = 'mobile_login';
    protected int $defaultTokenTtl = 1800; // 30 minutos

    public function getAuthType(): string
    {
        return self::AUTH_TYPE;
    }

    protected function performAuthentication(): bool
    {
        $this->validateSettings(['base_url', 'username', 'password']);

        $url = $this->integration->getConfig('base_url') . '/mge/service.sbr?serviceName=MobileLoginSP.login&outputType=json';
        $response = Http::timeout(30)
            ->withHeaders(['token' => $this->integration->getConfig('token', '')])
            ->acceptJson()
            ->contentType('application/json')
            ->asJson()
            ->post($url, [
                'requestBody' => [
                    'NOMUSU' => ['$' => $this->integration->getConfig('username')],
                    'INTERNO' => ['$' => $this->integration->getConfig('password')],
                    'KEEPCONNECTED' => ['$' => 'S'],
                ],
            ]);



        if (!$response->successful()) {
            throw new ErpAuthenticationException(
                "Falha na autenticação mobile login. Status: {$response->status()}"
            );
        }

        $data = $response->json();

        if (isset($data['status']) && $data['status'] !== '1') {
            throw new ErpAuthenticationException(
                "Erro do Sankhya: " . ($data['statusMessage'] ?? 'Erro desconhecido')
            );
        }


        $token = $data['responseBody']['jsessionid']['$'] ?? null;
        if (empty($token)) {
            throw new ErpAuthenticationException('Token não retornado pelo servidor');
        }

        $this->saveTokenToCache($token, $this->defaultTokenTtl);
        return true;
    }
}