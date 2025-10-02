<?php

namespace App\Services\Core;

use App\Contracts\Services\ServiceInterface;
use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceValidationException;
use App\Models\Company;
use App\Services\Parameter\ServiceParameterManager;

abstract class BaseService implements ServiceInterface
{
    protected ServiceParameterManager $parameterManager;
    protected ServiceType $serviceType;
    protected string $serviceName;
    protected string $description;
    protected ?Company $company = null;

    public function __construct(?Company $company = null)
    {
        $this->company = $company ?? request()->get('company') ?? null;
        $this->parameterManager = new ServiceParameterManager();
        $this->configureParameters();
    }

    abstract protected function configureParameters(): void;
    abstract public function execute(array $params = []): ApiResponse;

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

    // Getters
    public function getRequiredParams(): array
    {
        return $this->parameterManager->getRequiredNames();
    }

    public function getParametersConfig(): array
    {
        return $this->parameterManager->toArray();
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

    public function getGroupedParameters(): array
    {
        return $this->parameterManager->getGrouped();
    }

    public function getParameterManager(): ServiceParameterManager
    {
        return $this->parameterManager;
    }

    // Helpers
    protected function success(mixed $data = null, ?string $message = null, array $metadata = []): ApiResponse
    {
        return ApiResponse::success($data, $message, $metadata);
    }

    protected function error(string $message, array $errors = [], array $metadata = []): ApiResponse
    {
        return ApiResponse::error($message, $errors, $metadata);
    }
}