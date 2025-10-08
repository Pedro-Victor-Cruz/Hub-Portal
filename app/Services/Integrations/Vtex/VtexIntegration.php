<?php

namespace App\Services\Integrations\Vtex;

use App\Contracts\Auth\AuthHandlerInterface;
use App\Services\Core\Integration\BaseIntegration;
use App\Services\Core\Traits\HasAuthentication;
use App\Services\Integrations\Vtex\Auth\VtexAppKeyAuthHandler;
use App\Services\Integrations\Vtex\Auth\VtexUserTokenAuthHandler;
use App\Services\Parameter\ServiceParameter;
use Exception;

class VtexIntegration extends BaseIntegration
{
    use HasAuthentication;

    protected string $name = 'VTEX';
    protected string $description = 'Integração com a plataforma de e-commerce VTEX';
    protected string $version = '1.0.0';
    protected string $image = 'vtex.png';

    public function configureParameters(): void
    {
        $this->parameterManager->addMany([
            // Configurações básicas
            ServiceParameter::text(
                name: 'account_name',
                required: true,
                description: 'Nome da conta VTEX (ex: minhaloja)'
            )->withLabel('Nome da Conta')
                ->withGroup('Conexão')
                ->withPlaceholder('minhaloja'),

            ServiceParameter::select(
                name: 'environment',
                options: [
                    'stable' => 'Stable (Produção)',
                    'beta' => 'Beta (Testes)',
                ],
                required: true,
                defaultValue: 'stable'
            )->withLabel('Ambiente')
                ->withGroup('Conexão'),

            // Autenticação
            ServiceParameter::select(
                name: 'auth_type',
                options: [
                    'app_key' => 'App Key + App Token',
                    'user_token' => 'User Token (VtexIdclientAutCookie)',
                ],
                required: true,
                defaultValue: 'app_key'
            )->withLabel('Tipo de Autenticação')
                ->withGroup('Autenticação'),

            ServiceParameter::text(
                name: 'app_key',
                description: 'App Key gerada no painel VTEX (obrigatório para app_key)'
            )->withLabel('App Key')
                ->withGroup('Autenticação')
                ->withSensitive(),

            ServiceParameter::text(
                name: 'app_token',
                description: 'App Token gerada no painel VTEX (obrigatório para app_key)'
            )->withLabel('App Token')
                ->withGroup('Autenticação')
                ->withSensitive(),

            ServiceParameter::text(
                name: 'user_token',
                description: 'Token de usuário VtexIdclientAutCookie (obrigatório para user_token)'
            )->withLabel('User Token')
                ->withGroup('Autenticação')
                ->withSensitive(),

            // Configurações adicionais
            ServiceParameter::boolean(
                name: 'use_custom_domain',
                defaultValue: false
            )->withLabel('Usar Domínio Customizado')
                ->withGroup('Avançado'),

            ServiceParameter::url(
                name: 'custom_domain',
                description: 'Domínio customizado (ex: api.minhaloja.com.br)'
            )->withLabel('Domínio Customizado')
                ->withGroup('Avançado'),

            ServiceParameter::number(
                name: 'timeout',
                defaultValue: 30,
                description: 'Timeout em segundos para requisições'
            )->withLabel('Timeout (segundos)')
                ->withGroup('Avançado'),

            ServiceParameter::number(
                name: 'retry_attempts',
                defaultValue: 3,
                description: 'Número de tentativas em caso de falha'
            )->withLabel('Tentativas de Retry')
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

            // Tenta fazer uma requisição simples para validar a autenticação
            $testRequest = $this->createHttpRequest()
                ->setMethod('GET')
                ->setEndpoint('api/catalog/pvt/product/1')
                ->execute();

            return [
                'success' => $authResult && ($testRequest->isSuccess() || $testRequest->getStatusCode() === 404),
                'details' => [
                    'account_name' => $this->getConfig('account_name'),
                    'environment' => $this->getConfig('environment'),
                    'auth_type' => $this->getConfig('auth_type'),
                    'authenticated' => $this->isAuthenticated(),
                    'has_token' => !empty($this->getAuthToken()),
                    'base_url' => $this->getBaseUrl(),
                    'test_request_status' => $testRequest->getStatusCode(),
                    'timestamp' => now()->toISOString()
                ]
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'details' => [
                    'error' => $e->getMessage(),
                    'account_name' => $this->getConfig('account_name'),
                    'auth_type' => $this->getConfig('auth_type')
                ]
            ];
        }
    }

    /**
     * Retorna a URL base da API VTEX
     */
    public function getBaseUrl(): string
    {
        // Se usar domínio customizado
        if ($this->getConfig('use_custom_domain', false)) {
            $customDomain = $this->getConfig('custom_domain');
            if ($customDomain) {
                return rtrim($customDomain, '/');
            }
        }

        // URL padrão VTEX
        $accountName = $this->getConfig('account_name');
        $environment = $this->getConfig('environment', 'stable');

        return "https://{$accountName}.vtexcommercestable.com.br";
    }

    /**
     * Retorna o tipo de autenticação configurado
     */
    public function getAuthType(): string
    {
        return $this->getConfig('auth_type', 'app_key');
    }

    /**
     * Retorna o timeout configurado
     */
    public function getTimeout(): int
    {
        return (int) $this->getConfig('timeout', 30);
    }

    /**
     * Retorna o número de tentativas de retry
     */
    public function getRetryAttempts(): int
    {
        return (int) $this->getConfig('retry_attempts', 3);
    }

    /**
     * Cria o handler de autenticação apropriado
     *
     * @throws Exception
     */
    protected function createAuthHandler(): AuthHandlerInterface
    {
        $authType = $this->getAuthType();

        return match ($authType) {
            'app_key' => new VtexAppKeyAuthHandler($this),
            'user_token' => new VtexUserTokenAuthHandler($this),
            default => throw new Exception("Tipo de autenticação não suportado: {$authType}")
        };
    }

    /**
     * Cria uma instância de VtexHttpRequest
     */
    public function createHttpRequest(): VtexHttpRequest
    {
        return new VtexHttpRequest($this);
    }

    /**
     * Métodos de conveniência para APIs específicas da VTEX
     */

    /**
     * Acessa a API de Catálogo
     */
    public function catalog(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('catalog');
    }

    /**
     * Acessa a API de Pedidos (OMS)
     */
    public function orders(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('oms');
    }

    /**
     * Acessa a API de Pricing
     */
    public function pricing(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('pricing');
    }

    /**
     * Acessa a API de Logistics
     */
    public function logistics(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('logistics');
    }

    /**
     * Acessa a API de Checkout
     */
    public function checkout(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('checkout');
    }

    /**
     * Acessa a API de Master Data
     */
    public function masterData(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('masterdata');
    }

    /**
     * Acessa a API de Payments
     */
    public function payments(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('payments');
    }

    /**
     * Acessa a API de Customer Credit
     */
    public function customerCredit(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('customer-credit');
    }

    /**
     * Acessa a API de GiftCard
     */
    public function giftCard(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('giftcard');
    }

    /**
     * Acessa a API de Promotions & Taxes
     */
    public function promotions(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('promotions');
    }

    /**
     * Acessa a API de Search
     */
    public function search(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('search');
    }

    /**
     * Acessa a API de Session Manager
     */
    public function session(): VtexHttpRequest
    {
        return $this->createHttpRequest()->setApiContext('session');
    }
}