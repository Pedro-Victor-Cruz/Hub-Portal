<?php

namespace App\Services\Integrations\Protheus;

use App\Contracts\Auth\AuthHandlerInterface;
use App\Services\Core\Integration\BaseIntegration;
use App\Services\Core\Traits\HasAuthentication;
use App\Services\Parameter\ServiceParameter;
use Exception;

class ProtheusIntegration extends BaseIntegration
{
    use HasAuthentication;

    protected string $name = 'TOTVS Protheus';
    protected string $description = 'Integração com o sistema ERP TOTVS Protheus via REST API';
    protected string $version = '1.0.0';
    protected string $image = 'protheus.png';

    public function configureParameters(): void
    {
        $this->parameterManager->addMany([
            ServiceParameter::select(
                name: 'auth_type',
                options: [
                    'oauth' => 'OAuth 2.0',
                    'basic' => 'Basic Auth (Usuário/Senha)',
                ],
                required: true,
                defaultValue: 'oauth'
            )->withLabel('Tipo de Autenticação')
                ->withGroup('Autenticação'),

            ServiceParameter::url(
                name: 'base_url',
                required: true,
                description: 'URL base da API REST do Protheus (ex: http://servidor:porta/rest)'
            )->withLabel('URL Base da API')
                ->withGroup('Conexão')
                ->withPlaceholder('http://servidor:porta/rest'),

            ServiceParameter::text(
                name: 'company',
                required: true,
                description: 'Código da empresa no Protheus (ex: 01)'
            )->withLabel('Empresa')
                ->withGroup('Conexão')
                ->withPlaceholder('01'),

            ServiceParameter::text(
                name: 'branch',
                required: true,
                description: 'Código da filial no Protheus (ex: 0101)'
            )->withLabel('Filial')
                ->withGroup('Conexão')
                ->withPlaceholder('0101'),

            // Parâmetros OAuth
            ServiceParameter::text(
                name: 'client_id',
                description: 'Client ID para autenticação OAuth (obrigatório para OAuth)'
            )->withLabel('Client ID')
                ->withGroup('Autenticação')
                ->withSensitive(),

            ServiceParameter::text(
                name: 'client_secret',
                description: 'Client Secret para autenticação OAuth (obrigatório para OAuth)'
            )->withLabel('Client Secret')
                ->withGroup('Autenticação')
                ->withSensitive(),

            // Parâmetros Basic Auth
            ServiceParameter::text(
                name: 'username',
                description: 'Nome de usuário (obrigatório para Basic Auth)'
            )->withLabel('Usuário')
                ->withGroup('Autenticação'),

            ServiceParameter::text(
                name: 'password',
                description: 'Senha (obrigatório para Basic Auth)'
            )->withLabel('Senha')
                ->withGroup('Autenticação')
                ->withSensitive(),

            // Configurações adicionais
            ServiceParameter::number(
                name: 'timeout',
                required: false,
                defaultValue: 30,
                description: 'Tempo máximo de espera em segundos'
            )->withLabel('Timeout (segundos)')
                ->withGroup('Avançado'),

            ServiceParameter::boolean(
                name: 'use_ssl_verification',
                defaultValue: true,
                description: 'Verificar certificado SSL (recomendado em produção)'
            )->withLabel('Verificar SSL')
                ->withGroup('Avançado'),
        ]);
    }

    /**
     * Implementa teste de conexão usando autenticação
     */
    public function performConnectionTest(): array
    {
        try {
            $authResult = $this->authenticate();

            // Tenta buscar informações básicas para validar conexão
            $httpRequest = new ProtheusHttpRequest($this);
            $testResponse = $httpRequest
                ->setMethod('GET')
                ->setEndpoint('api/framework/v1/health')
                ->execute();

            return [
                'success' => $authResult && $testResponse->isSuccess(),
                'details' => [
                    'auth_type' => $this->getConfig('auth_type'),
                    'base_url' => $this->getConfig('base_url'),
                    'company' => $this->getConfig('company'),
                    'branch' => $this->getConfig('branch'),
                    'authenticated' => $this->isAuthenticated(),
                    'has_token' => !empty($this->getAuthToken()),
                    'api_health' => $testResponse->isSuccess() ? 'OK' : 'FAILED',
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'details' => [
                    'error' => $e->getMessage(),
                    'auth_type' => $this->getConfig('auth_type'),
                    'base_url' => $this->getConfig('base_url')
                ]
            ];
        }
    }

    /**
     * Cria o handler de autenticação apropriado
     */
    protected function createAuthHandler(): AuthHandlerInterface
    {
        $authType = $this->getConfig('auth_type', 'oauth');

        return match ($authType) {
            'oauth' => new ProtheusOAuthHandler($this),
            'basic' => new ProtheusBasicAuthHandler($this),
            default => throw new Exception("Tipo de autenticação não suportado: {$authType}")
        };
    }

    // Métodos de conveniência
    public function getAuthType(): string
    {
        return $this->getConfig('auth_type', 'oauth');
    }

    public function getBaseUrl(): string
    {
        return rtrim($this->getConfig('base_url', ''), '/');
    }

    public function getCompany(): string
    {
        return $this->getConfig('company', '01');
    }

    public function getBranch(): string
    {
        return $this->getConfig('branch', '0101');
    }

    public function getTimeout(): int
    {
        return (int) $this->getConfig('timeout', 30);
    }

    public function shouldVerifySSL(): bool
    {
        return (bool) $this->getConfig('use_ssl_verification', true);
    }

    /**
     * Formata empresa e filial no formato esperado pelo Protheus
     */
    public function getCompanyBranchFormatted(): string
    {
        return $this->getCompany() . '/' . $this->getBranch();
    }
}