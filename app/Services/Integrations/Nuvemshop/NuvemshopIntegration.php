<?php

namespace App\Services\Integrations\Nuvemshop;

use App\Contracts\Auth\AuthHandlerInterface;
use App\Services\Core\Integration\BaseIntegration;
use App\Services\Core\Traits\HasAuthentication;
use App\Services\Integrations\Nuvemshop\Auth\NuvemshopOAuthHandler;
use App\Services\Parameter\ServiceParameter;
use Exception;

/**
 * Integração com a API da Nuvemshop
 *
 * @see https://tiendanube.github.io/api-documentation/intro
 */
class NuvemshopIntegration extends BaseIntegration
{
    use HasAuthentication;

    protected string $name = 'Nuvemshop';
    protected string $description = 'Integração com a plataforma de e-commerce Nuvemshop';
    protected string $version = '1.0.0';
    protected string $image = 'nuvemshop.png';

    /**
     * Versão da API da Nuvemshop
     */
    private const API_VERSION = '2025-03';

    public function configureParameters(): void
    {
        $this->parameterManager->addMany([
            ServiceParameter::text(
                name: 'store_id',
                required: true,
                description: 'ID da loja na Nuvemshop (user_id retornado na autenticação)'
            )->withLabel('ID da Loja')
                ->withGroup('Conexão')
                ->withPlaceholder('123456'),

            ServiceParameter::text(
                name: 'access_token',
                required: true,
                description: 'Token de acesso OAuth2 obtido após autenticação'
            )->withLabel('Access Token')
                ->withGroup('Autenticação')
                ->withSensitive(),

            ServiceParameter::select(
                name: 'domain',
                options: [
                    'nuvemshop' => 'Brasil (nuvemshop.com.br)',
                    'tiendanube' => 'Internacional (tiendanube.com)',
                ],
                required: true,
                defaultValue: 'nuvemshop',
                description: 'Selecione o domínio baseado na região da loja'
            )->withLabel('Domínio da API')
                ->withGroup('Conexão'),

            ServiceParameter::text(
                name: 'user_agent',
                required: true,
                defaultValue: config('app.name') . ' (' . config('mail.from.address') . ')',
                description: 'Identificação da aplicação (nome e contato)'
            )->withLabel('User Agent')
                ->withGroup('Configuração')
                ->withPlaceholder('MinhaApp (contato@exemplo.com)'),

            // Parâmetros OAuth (opcionais, para renovação de token)
            ServiceParameter::text(
                name: 'client_id',
                description: 'Client ID da aplicação (app_id)'
            )->withLabel('Client ID')
                ->withGroup('OAuth')
                ->withSensitive(),

            ServiceParameter::text(
                name: 'client_secret',
                description: 'Client Secret da aplicação'
            )->withLabel('Client Secret')
                ->withGroup('OAuth')
                ->withSensitive(),

            ServiceParameter::number(
                name: 'rate_limit_requests',
                defaultValue: 40,
                description: 'Limite de requisições por bucket (padrão: 40)'
            )->withLabel('Limite de Requisições')
                ->withGroup('Rate Limiting'),

            ServiceParameter::number(
                name: 'rate_limit_seconds',
                defaultValue: 20,
                description: 'Tempo para esvaziar o bucket em segundos (padrão: 20s para 40 req = 2 req/s)'
            )->withLabel('Tempo do Bucket (segundos)')
                ->withGroup('Rate Limiting'),
        ]);
    }

    /**
     * Implementa teste de conexão
     */
    public function performConnectionTest(): array
    {
        try {
            // Verifica se a autenticação está funcionando
            $authResult = $this->authenticate();

            if (!$authResult) {
                return [
                    'success' => false,
                    'details' => [
                        'error' => 'Falha na autenticação',
                        'store_id' => $this->getConfig('store_id'),
                        'domain' => $this->getConfig('domain')
                    ]
                ];
            }

            // Tenta buscar informações da loja
            $request = new NuvemshopHttpRequest($this);
            $response = $request->setEndpoint('store')
                ->setMethod('GET')
                ->execute();

            if (!$response->isSuccess()) {
                return [
                    'success' => false,
                    'details' => [
                        'error' => 'Não foi possível acessar os dados da loja',
                        'errors' => $response->getErrors(),
                        'metadata' => $response->getMetadata()
                    ]
                ];
            }

            $storeData = $response->getData();

            return [
                'success' => true,
                'details' => [
                    'store_id' => $this->getConfig('store_id'),
                    'store_name' => $storeData['name'] ?? 'N/A',
                    'domain' => $storeData['main_domain'] ?? 'N/A',
                    'email' => $storeData['email'] ?? 'N/A',
                    'authenticated' => $this->isAuthenticated(),
                    'has_token' => !empty($this->getAuthToken()),
                    'api_version' => self::API_VERSION,
                    'timestamp' => now()->toISOString()
                ]
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'details' => [
                    'error' => $e->getMessage(),
                    'store_id' => $this->getConfig('store_id'),
                    'domain' => $this->getConfig('domain')
                ]
            ];
        }
    }

    /**
     * Retorna a URL base da API
     */
    public function getBaseUrl(): string
    {
        $domain = $this->getConfig('domain', 'nuvemshop');
        $storeId = $this->getConfig('store_id');

        $baseUrl = $domain === 'tiendanube'
            ? 'https://api.tiendanube.com'
            : 'https://api.nuvemshop.com.br';

        return "{$baseUrl}/" . self::API_VERSION . "/{$storeId}";
    }

    /**
     * Retorna o User-Agent configurado
     */
    public function getUserAgent(): string
    {
        return $this->getConfig('user_agent', config('app.name') . ' (' . config('mail.from.address') . ')');
    }

    /**
     * Retorna o domínio configurado
     */
    public function getDomain(): string
    {
        return $this->getConfig('domain', 'nuvemshop');
    }

    /**
     * Retorna o ID da loja
     */
    public function getStoreId(): string
    {
        return $this->getConfig('store_id');
    }

    /**
     * Retorna configurações de rate limiting
     */
    public function getRateLimitConfig(): array
    {
        return [
            'requests' => $this->getConfig('rate_limit_requests', 40),
            'seconds' => $this->getConfig('rate_limit_seconds', 20),
        ];
    }

    /**
     * Cria o handler de autenticação
     */
    protected function createAuthHandler(): NuvemshopOAuthHandler
    {
        return new NuvemshopOAuthHandler($this);
    }

    /**
     * Valida se a integração está configurada corretamente
     */
    public function validate(): bool
    {
        $required = ['store_id', 'access_token', 'user_agent'];

        foreach ($required as $field) {
            if (empty($this->getConfig($field))) {
                throw new Exception("Campo obrigatório não configurado: {$field}");
            }
        }

        // Valida formato do User-Agent (deve conter nome e contato)
        $userAgent = $this->getUserAgent();
        if (!preg_match('/^.+\s*\(.+\)$/', $userAgent)) {
            throw new Exception(
                'User-Agent inválido. Formato esperado: "NomeApp (contato@email.com)"'
            );
        }

        return true;
    }
}