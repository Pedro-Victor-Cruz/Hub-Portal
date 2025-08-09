<?php

namespace App\Services\Core;

use App\Contracts\Services\ServiceInterface;
use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceValidationException;
use App\Facades\ErpManager;
use App\Models\Company;
use Exception;

/**
 * Classe base abstrata para todos os serviços
 */
abstract class BaseService implements ServiceInterface
{
    protected array $requiredParams = [];
    protected ServiceType $serviceType;
    protected string $serviceName;
    protected string $description;
    protected Company | null $company = null;

    /**
     * @throws Exception
     */
    public function __construct(?Company $company = null)
    {
        $this->company = $company ?? $this->resolveCompanyFromRequest();
    }

    protected function resolveCompanyFromRequest(): Company
    {
        $company = request()->get('company');

        if (!$company instanceof Company) {
            throw new Exception('Empresa não encontrada na requisição.');
        }

        return $company;
    }

    public function validateParams(array $params): bool
    {
        $errors = [];

        foreach ($this->getRequiredParams() as $required) {
            if (empty($params[$required])) {
                $errors[] = "Parâmetro obrigatório '{$required}' não foi fornecido";
            }
        }

        if (!empty($errors)) {
            throw new ServiceValidationException(
                'Parâmetros inválidos fornecidos',
                $errors
            );
        }

        return true;
    }

    public function getRequiredParams(): array
    {
        return $this->requiredParams;
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