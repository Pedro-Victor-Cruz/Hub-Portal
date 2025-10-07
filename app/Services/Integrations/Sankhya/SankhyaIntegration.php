<?php

namespace App\Services\Integrations\Sankhya;

use App\Contracts\Auth\AuthHandlerInterface;
use App\Services\Core\Integration\BaseIntegration;
use App\Services\Core\Traits\HasAuthentication;
use App\Services\Integrations\Sankhya\Auth\SankhyaJsonAuthHandler;
use App\Services\Integrations\Sankhya\Auth\SankhyaMobileLoginHandler;
use App\Services\Parameter\ServiceParameter;
use Exception;


class SankhyaIntegration extends BaseIntegration
{
    use HasAuthentication;

    protected string $name = 'Sankhya ERP';
    protected string $description = 'Integração com o sistema ERP Sankhya';
    protected string $version = '1.0.0';
    protected string $image = 'sankhya.png';

    public function configureParameters(): void
    {
        $this->parameterManager->addMany([
            ServiceParameter::select(
                name: 'auth_type',
                options: [
                    'mobile_login' => 'Login Móvel (Usuário/Senha)',
                    'json_auth'   => 'Autenticação por Token',
                ],
                required: true,
                defaultValue: 'mobile_login'
            )->withLabel('Tipo de Autenticação')
                ->withGroup('Autenticação'),

            ServiceParameter::text(
                name: 'username',
                description: 'Nome de usuário (obrigatório para mobile_login)'
            )->withLabel('Usuário')
                ->withGroup('Autenticação'),

            ServiceParameter::text(
                name: 'password',
                description: 'Senha (obrigatório para json_auth)'
            )->withLabel('Senha')
                ->withGroup('Autenticação')
                ->withSensitive(),

            ServiceParameter::url(
                name: 'base_url',
                required: true
            )->withLabel('URL Base')
                ->withGroup('Conexão'),

            ServiceParameter::text(
                name: 'token',
                description: 'Token de autenticação (obrigatório para json_auth)'
            )->withLabel('Token')
                ->withGroup('Autenticação')
                ->withSensitive(),
        ]);
    }

    /**
     * Implementa teste de conexão usando autenticação
     */
    public function performConnectionTest(): array
    {
        try {
            $authResult = $this->authenticate();

            return [
                'success' => $authResult,
                'details' => [
                    'auth_type'     => $this->getConfig('auth_type'),
                    'base_url'      => $this->getConfig('base_url'),
                    'authenticated' => $this->isAuthenticated(),
                    'has_token'     => !empty($this->getAuthToken()),
                    'timestamp'     => now()->toISOString()
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'details' => [
                    'error'     => $e->getMessage(),
                    'auth_type' => $this->getConfig('auth_type')
                ]
            ];
        }
    }

    // Métodos de conveniência
    public function getAuthType(): string
    {
        return $this->getConfig('auth_type', 'mobile_login');
    }

    public function getBaseUrl(): string
    {
        return $this->getConfig('base_url', 'https://api.sankhya.com.br/gateway/v1');
    }

    /**
     * @throws Exception
     */
    protected function createAuthHandler(): AuthHandlerInterface
    {
        $authType = $this->getConfig('auth_type', 'mobile_login');

        return match ($authType) {
            'mobile_login' => new SankhyaMobileLoginHandler($this),
            'json_auth' => new SankhyaJsonAuthHandler($this),
            default => throw new Exception("Tipo de autenticação não suportado: {$authType}")
        };
    }
}