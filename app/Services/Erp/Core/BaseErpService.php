<?php

namespace App\Services\Erp\Core;

use App\Contracts\Erp\ErpServiceInterface;
use App\Models\CompanyErpSetting;
use Exception;

/**
 * Classe base simplificada para serviços ERP
 */
abstract class BaseErpService implements ErpServiceInterface
{
    protected CompanyErpSetting $settings;
    protected int $timeout = 30;
    protected int $retryCount = 3;

    public function __construct(CompanyErpSetting $settings)
    {
        $this->settings = $settings;
        $this->initialize();
    }

    /**
     * Executa o serviço
     * @param array $params
     */
    public function execute(array $params = []): ErpServiceResponse
    {

        try {
            // Validação
            $this->validate($params);

            // Preparação
            $params = $this->prepare($params);

            // Execução
            return $this->perform($params);
        } catch (Exception $e) {
            return ErpServiceResponse::error($e->getMessage());
        }
    }

    /**
     * Valida os parâmetros de entrada
     * @param array $params
     * @throws Exception
     */
    protected function validate(array $params): void
    {
        $required = $this->getRequiredParams();
        $missing = [];

        foreach ($required as $param) {
            if (!isset($params[$param]) || $params[$param] === '') {
                $missing[] = $param;
            }
        }

        if (!empty($missing)) {
            throw new Exception("Parâmetros obrigatórios ausentes: " . implode(', ', $missing));
        }
    }

    /**
     * Prepara os parâmetros para execução
     */
    protected function prepare(array $params): array
    {
        return array_merge($this->getDefaultParams(), $params);
    }

    /**
     * Executa a operação principal do serviço
     */
    abstract protected function perform(array $params): ErpServiceResponse;

    /**
     * Inicialização específica do serviço
     */
    protected function initialize(): void
    {
        // Override se necessário
    }

    /**
     * Retorna parâmetros padrão
     */
    protected function getDefaultParams(): array
    {
        return [];
    }

    // Métodos abstratos
    abstract public function getServiceName(): string;
    abstract public function getRequiredParams(): array;

    // Getters e Setters
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function setRetryCount(int $retryCount): self
    {
        $this->retryCount = $retryCount;
        return $this;
    }
}