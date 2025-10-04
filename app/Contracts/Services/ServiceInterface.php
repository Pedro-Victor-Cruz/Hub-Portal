<?php

namespace App\Contracts\Services;

use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceValidationException;
use App\Services\Core\ApiResponse;
use App\Services\Parameter\ServiceParameterManager;

/**
 * Interface base para todos os serviços da aplicação
 */
interface ServiceInterface
{
    /**
     * Executa o serviço com os parâmetros fornecidos
     *
     * @param array $params Parâmetros para execução do serviço
     * @return ApiResponse Resposta padronizada do serviço
     */
    public function execute(array $params = []): ApiResponse;

    /**
     * Valida os parâmetros necessários para o serviço
     *
     * @param array $params Parâmetros a serem validados
     * @return bool True se válidos, false caso contrário
     * @throws ServiceValidationException
     */
    public function validateParams(array $params): bool;

    /**
     * Retorna os parâmetros obrigatórios do serviço
     *
     * @return array Lista de parâmetros obrigatórios
     */
    public function getRequiredParams(): array;

    /**
     * Retorna a configuração completa dos parâmetros do serviço
     *
     * @return array Configuração dos parâmetros
     */
    public function getParametersConfig(): array;

    /**
     * Retorna os parâmetros agrupados (útil para UI)
     *
     * @return array Parâmetros agrupados
     */
    public function getGroupedParameters(): array;

    /**
     * Retorna o gerenciador de parâmetros (para casos avançados)
     *
     * @return ServiceParameterManager Instância do gerenciador de parâmetros
     */
    public function getParameterManager(): ServiceParameterManager;

    /**
     * Retorna o tipo do serviço
     *
     * @return string Tipo do serviço
     */
    public function getServiceType(): ?ServiceType;

    /**
     * Retorna o nome identificador do serviço
     *
     * @return string Nome do serviço
     */
    public function getServiceName(): string;

    /**
     * Retorna a descrição do serviço
     *
     * @return string Descrição do serviço
     */
    public function getDescription(): string;
}