<?php

namespace App\Services\Erp\Core;

use App\Contracts\Erp\ErpAuthInterface;
use App\Exceptions\Erp\ErpAuthenticationException;
use App\Models\CompanyErpSetting;
use App\Services\Erp\Cache\ErpTokenCache;
use Illuminate\Support\Facades\Log;

/**
 * Classe base abstrata para manipuladores de autenticação ERP.
 * Fornece funcionalidades comuns como cache de token, retry e logging.
 */
abstract class BaseAuthHandler implements ErpAuthInterface
{
    protected CompanyErpSetting $settings;
    protected ?string $token = null;
    protected int $maxRetries = 3;
    protected int $retryDelay = 1; // segundos

    /**
     * Construtor base para manipuladores de autenticação.
     *
     * @param CompanyErpSetting $settings Configurações do ERP
     */
    public function __construct(CompanyErpSetting $settings)
    {
        $this->settings = $settings;
        $this->loadTokenFromCache();
    }

    /**
     * Realiza a autenticação com retry automático em caso de falha.
     *
     * @return bool True se a autenticação foi bem-sucedida
     * @throws ErpAuthenticationException
     */
    public function authenticate(): bool
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            $attempts++;

            try {
                Log::info("Tentativa de autenticação ERP", [
                    'erp' => $this->settings->erp_name,
                    'auth_type' => $this->settings->auth_type,
                    'company_id' => $this->settings->company_id,
                    'attempt' => $attempts
                ]);

                if ($this->performAuthentication()) {
                    Log::info("Autenticação ERP bem-sucedida", [
                        'erp' => $this->settings->erp_name,
                        'auth_type' => $this->settings->auth_type,
                        'company_id' => $this->settings->company_id,
                        'attempts_used' => $attempts
                    ]);
                    return true;
                }

            } catch (\Exception $e) {
                $lastException = $e;

                Log::warning("Falha na autenticação ERP", [
                    'erp' => $this->settings->erp_name,
                    'auth_type' => $this->settings->auth_type,
                    'company_id' => $this->settings->company_id,
                    'attempt' => $attempts,
                    'error' => $e->getMessage()
                ]);
            }

            // Aguarda antes da próxima tentativa (exceto na última)
            if ($attempts < $this->maxRetries) {
                sleep($this->retryDelay);
                $this->retryDelay *= 2; // Backoff exponencial
            }
        }

        Log::error("Todas as tentativas de autenticação ERP falharam", [
            'erp' => $this->settings->erp_name,
            'auth_type' => $this->settings->auth_type,
            'company_id' => $this->settings->company_id,
            'max_attempts' => $this->maxRetries
        ]);

        if ($lastException) {
            throw new ErpAuthenticationException(
                "Falha na autenticação após {$this->maxRetries} tentativas: " . $lastException->getMessage(),
                0,
                $lastException
            );
        }

        throw new ErpAuthenticationException(
            "Falha na autenticação após {$this->maxRetries} tentativas"
        );
    }

    /**
     * Renova o token removendo-o do cache e realizando nova autenticação.
     *
     * @return bool True se a renovação foi bem-sucedida
     * @throws ErpAuthenticationException
     */
    public function refreshToken(): bool
    {
        Log::info("Renovando token ERP", [
            'erp' => $this->settings->erp_name,
            'auth_type' => $this->settings->auth_type,
            'company_id' => $this->settings->company_id
        ]);

        ErpTokenCache::forget($this->getCacheKey());
        $this->token = null;

        return $this->authenticate();
    }

    /**
     * Retorna o token atual, realizando autenticação se necessário.
     *
     * @return string Token de autenticação
     * @throws ErpAuthenticationException
     */
    public function getToken(): string
    {
        if (!$this->isTokenValid()) {
            if (!$this->authenticate()) {
                throw new ErpAuthenticationException('Não foi possível obter token de autenticação válido');
            }
        }

        return $this->token;
    }

    /**
     * Verifica se o token atual é válido.
     *
     * @return bool True se o token é válido
     */
    public function isTokenValid(): bool
    {
        return ErpTokenCache::has($this->getCacheKey()) && !empty($this->token);
    }

    /**
     * Gera a chave de cache única baseada nas configurações.
     *
     * @return string Chave do cache
     */
    public function getCacheKey(): string
    {
        return "{$this->settings->erp_name}:{$this->getAuthType()}:{$this->settings->company_id}:{$this->settings->id}";
    }

    /**
     * Carrega o token do cache se existir.
     *
     * @return void
     */
    protected function loadTokenFromCache(): void
    {
        $this->token = ErpTokenCache::get($this->getCacheKey());
    }

    /**
     * Salva o token no cache com tempo de expiração.
     *
     * @param string $token Token a ser salvo
     * @param int $ttlSeconds Tempo de vida em segundos
     * @return void
     */
    protected function saveTokenToCache(string $token, int $ttlSeconds): void
    {
        $this->token = $token;
        ErpTokenCache::put($this->getCacheKey(), $token, $ttlSeconds);
    }

    /**
     * Valida se as configurações necessárias estão presentes.
     *
     * @param array $requiredFields Campos obrigatórios
     * @throws ErpAuthenticationException
     */
    protected function validateSettings(array $requiredFields): void
    {
        foreach ($requiredFields as $field) {
            if (empty($this->settings->$field)) {
                throw new ErpAuthenticationException(
                    "Campo obrigatório '{$field}' não configurado para autenticação {$this->getAuthType()}"
                );
            }
        }
    }

    /**
     * Método abstrato que deve ser implementado pelas classes filhas
     * para realizar a autenticação específica do ERP.
     *
     * @return bool True se a autenticação foi bem-sucedida
     * @throws \Exception
     */
    abstract protected function performAuthentication(): bool;
}