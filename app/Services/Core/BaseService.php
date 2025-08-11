<?php

namespace App\Services\Core;

use App\Contracts\Services\ServiceInterface;
use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceValidationException;
use App\Models\Company;
use App\Services\Parameter\ServiceParameterManager;
use Exception;

/**
 * Classe base abstrata para todos os serviços
 */
abstract class BaseService implements ServiceInterface
{
    protected ServiceParameterManager $parameterManager;
    protected ServiceType $serviceType;
    protected string $serviceName;
    protected string $description;
    protected Company|null $company = null;

    /**
     * @throws Exception
     */
    public function __construct(?Company $company = null)
    {
        $this->company = $company ?? request()->get('company') ?? null;
        $this->parameterManager = new ServiceParameterManager();

        // Configura os parâmetros do serviço
        $this->configureParameters();
    }

    /**
     * Método abstrato para configurar os parâmetros do serviço
     * Deve ser implementado por cada serviço específico
     */
    abstract protected function configureParameters(): void;

    public function validateParams(array $params): bool
    {
        $validation = $this->parameterManager->validate($params);

        if (!$validation['valid']) {
            throw new ServiceValidationException(
                'Parâmetros inválidos fornecidos',
                $validation['errors']
            );
        }

        return true;
    }

    /**
     * Valida e sanitiza os parâmetros
     */
    public function validateAndSanitizeParams(array $params): array
    {
        $validation = $this->parameterManager->validate($params);

        if (!$validation['valid']) {
            throw new ServiceValidationException(
                'Parâmetros inválidos fornecidos',
                $validation['errors']
            );
        }

        return $validation['sanitized'];
    }

    /**
     * Obtém os nomes dos parâmetros obrigatórios (para manter compatibilidade)
     */
    public function getRequiredParams(): array
    {
        return $this->parameterManager->getRequiredNames();
    }

    /**
     * Obtém a configuração completa dos parâmetros
     */
    public function getParametersConfig(): array
    {
        return $this->parameterManager->toArray();
    }

    /**
     * Obtém parâmetros agrupados (útil para UI)
     */
    public function getGroupedParameters(): array
    {
        return $this->parameterManager->getGrouped();
    }

    /**
     * Obtém o gerenciador de parâmetros (para casos avançados)
     */
    public function getParameterManager(): ServiceParameterManager
    {
        return $this->parameterManager;
    }

    public function getServiceType(): string
    {
        return $this->serviceType->value;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Método auxiliar para criar resposta de sucesso
     */
    protected function success(mixed $data = null, ?string $message = null, array $metadata = []): ApiResponse
    {
        return ApiResponse::success($data, $message, $metadata);
    }

    /**
     * Método auxiliar para criar resposta de erro
     */
    protected function error(string $message, array $errors = [], array $metadata = []): ApiResponse
    {
        return ApiResponse::error($message, $errors, $metadata);
    }
}