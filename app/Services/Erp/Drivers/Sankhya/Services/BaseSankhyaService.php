<?php

namespace App\Services\Erp\Drivers\Sankhya\Services;

use App\Exceptions\Erp\ErpAuthenticationException;
use App\Exceptions\Erp\ErpServiceException;
use App\Services\Erp\Core\BaseErpService;
use App\Services\Erp\Core\ErpServiceResponse;

/**
 * Classe base para todos os serviços do Sankhya
 */
abstract class BaseSankhyaService extends BaseErpService
{
    protected function initializeService(): void
    {
        // Configurações específicas do Sankhya
        $this->setTimeout(45);
        $this->setRetryCount(2);

        // Headers padrão do Sankhya
        $this->setDefaultHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ]);
    }

    /**
     * @throws ErpAuthenticationException
     * @throws ErpServiceException
     */
    protected function performService(array $params): ErpServiceResponse
    {
        // Constrói a requisição usando o builder
        $requestConfig = $this->requestBuilder
            ->reset()
            ->setService($this->getSankhyaServiceName())
            ->addHeaders($this->getHeadersAuth())
            ->setBody($this->buildRequestBody($params))
            ->setTimeout($this->timeout)
            ->build();

        // Executa a requisição
        $response = $this->executeRequest($requestConfig);

        // Processa a resposta
        return $this->responseProcessor->processResponse($response);
    }

    /**
     * Gera os headers de autenticação para a requisição
     * @throws ErpAuthenticationException
     */
    private function getHeadersAuth(): array
    {
        $typeAuth = $this->authHandler->getAuthType();
        if ($typeAuth === 'token') {
            return [
                'Token' => 'Bearer ' . $this->authHandler->getToken() . '.master',
            ];
        } else if ($typeAuth === 'session') {
            return [
                'JSESSIONID' => $this->authHandler->getToken() . '.master',
                'Cookie' => 'JSESSIONID=' . $this->authHandler->getToken() . '.master',
            ];
        }

        return [];
    }

    /**
     * Constrói o corpo da requisição específico para o serviço
     */
    abstract protected function buildRequestBody(array $params): array;

    /**
     * Retorna o nome do serviço no formato do Sankhya
     */
    abstract protected function getSankhyaServiceName(): string;
}