<?php

namespace App\Services\Core;

use App\Contracts\Auth\AuthHandlerInterface;
use App\Services\Core\Integration\BaseIntegration;
use Exception;
use Illuminate\Support\Facades\Cache;


/**
 * Classe base abstrata para manipuladores de autenticação ERP.
 * Fornece funcionalidades comuns como cache de token, retry e logging.
 */
abstract class BaseAuthHandler implements AuthHandlerInterface
{
    protected BaseIntegration $integration;
    protected string $cachePrefix;
    protected int $defaultTokenTtl = 300; // 5 minutos

    public function __construct(BaseIntegration $integration)
    {
        $this->integration = $integration;
        $this->cachePrefix = 'service_auth_' . strtolower($this->integration->getName()) . '_';
    }

    /**
     * Método principal de autenticação
     */
    public function authenticate(): bool
    {
        try {
            // Verifica se já está autenticado
            if ($this->isAuthenticated()) {
                return true;
            }

            // Executa autenticação específica
            return $this->performAuthentication();

        } catch (Exception $e) {
            $this->clearAuthCache();
            throw $e;
        }
    }

    /**
     * Verifica se está autenticado (tem token válido)
     */
    public function isAuthenticated(): bool
    {
        $token = $this->getAuthToken();
        return !empty($token);
    }

    /**
     * Obtém o token de autenticação do cache
     */
    public function getAuthToken(): mixed
    {
        return Cache::get($this->getCacheKey());
    }

    /**
     * Logout - limpa autenticação
     */
    public function logout(): bool
    {
        $this->clearAuthCache();
        return true;
    }

    /**
     * Salva token no cache
     */
    protected function saveTokenToCache(mixed $token, int $ttl = null): void
    {
        $ttl = $ttl ?? $this->defaultTokenTtl;
        Cache::put($this->getCacheKey(), $token, $ttl);
    }

    /**
     * Limpa cache de autenticação
     */
    protected function clearAuthCache(): void
    {
        Cache::forget($this->getCacheKey());
    }

    /**
     * Chave do cache
     */
    protected function getCacheKey(): string
    {
        return $this->cachePrefix . $this->getAuthType();
    }

    /**
     * Valida configurações obrigatórias
     * @throws Exception
     */
    protected function validateSettings(array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            $value = $this->integration->getConfig($key);
            if (empty($value)) {
                // pegar nome descriptivo do parâmetro, se disponível
                $param = $this->integration->getParameterManager()->get($key);
                $paramName = $param ? $param->getLabel() : $key;
                throw new Exception("Configuração obrigatória não encontrada: {$paramName}");
            }
        }
    }

    /**
     * Método abstrato para implementação específica
     */
    abstract protected function performAuthentication(): bool;
    abstract public function getAuthType(): string;
}