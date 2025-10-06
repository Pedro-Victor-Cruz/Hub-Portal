<?php

namespace App\Services\Erp\Sankhya\Services;

use App\Enums\IntegrationType;
use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceValidationException;
use App\Services\Core\ApiResponse;
use App\Services\Core\Integration\IntegrationService;
use App\Services\Erp\Sankhya\SankhyaHttpRequest;
use App\Services\Parameter\ServiceParameter;
use App\Services\Utils\ResponseFormatters\SankhyaResponseFormatter;
use Exception;

/**
 * Serviço para executar consultas no Sankhya usando CRUDServiceProvider.loadView
 */
class SankhyaLoadViewService extends IntegrationService
{
    protected string $serviceName = 'Sankhya | Carregar View';
    protected string $description = 'Consulta dados de uma View no Sankhya';
    protected ServiceType $serviceType = ServiceType::QUERY;
    protected SankhyaHttpRequest $sankhyaRequest;
    protected IntegrationType $requiredIntegrationType = IntegrationType::SANKHYA;


    /**
     * Executa uma consulta SQL no Sankhya
     */
    public function performService(array $params): ApiResponse
    {
        try {
            $validatedParams = $this->validateAndSanitizeParams($params);

            $viewName = $validatedParams['ViewName'];
            $where = $validatedParams['WHERE'] ?? '';
            $fields = $validatedParams['Fields'] ?? '*';

            // Executa a consulta usando o método específico do SankhyaHttpRequest
            $response = $this->sankhyaRequest->callService('CRUDServiceProvider.loadView', [
                'query' => [
                    'viewName' => $viewName,
                    'fields' => [
                        'field' => [
                            '$' => $fields
                        ]
                    ],
                    'where' => [
                        '$' => $where ?? ''
                    ]
                ]
            ]);

            if (!$response->isSuccess()) {
                return $this->error(
                    'Erro na execução da consulta no Sankhya',
                    $response->getErrors(),
                    $response->getMetadata()
                );
            }

            // Formata a resposta usando o formatter
            $rawData = $response->getData();
            $formattedData = $this->formatQueryResponse($rawData);

            return $this->success(
                $formattedData,
                'Consulta executada com sucesso',
                [
                    'total_records' => is_array($formattedData) ? count($formattedData) : 0,
                    'where_executed' => $where,
                    'view_executed' => $viewName,
                    'fields_executed' => $fields,
                    'original_metadata' => $response->getMetadata()
                ]
            );

        } catch (ServiceValidationException $e) {
            return $this->error(
                'Parâmetros inválidos',
                $e->getValidationErrors()
            );

        } catch (Exception $e) {
            return $this->error(
                'Erro interno na execução da consulta',
                [$e->getMessage()],
                [
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile()),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ]
            );
        }
    }

    /**
     * Formata a resposta da consulta
     */
    protected function formatQueryResponse(mixed $data): mixed
    {
        return SankhyaResponseFormatter::formatLoadViewResponse($data);
    }

    /**
     * Escapa valores para uso seguro na SQL
     */
    private function escapeValue(mixed $value): string
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            // Para arrays, cria uma lista separada por vírgulas
            $escaped = array_map([$this, 'escapeValue'], $value);
            return '(' . implode(',', $escaped) . ')';
        }

        // Para strings, adiciona aspas simples e escapa caracteres especiais
        return "'" . str_replace("'", "''", (string) $value) . "'";
    }

    /**
     * Configura os parâmetros específicos deste serviço
     */
    protected function configureParameters(): void
    {
        $this->parameterManager->addMany([
            // Parâmetro principal - ViewName
            ServiceParameter::text(
                name: 'ViewName',
                required: true,
                description: 'Nome da View ou entidade no Sankhya para consultar.',
                validation: [
                    'min_length' => 2,
                    'max_length' => 100,
                    'regex' => '/^[A-Z0-9_]+$/'
                ]
            )->withLabel('Nome da View'),

            // Parâmetro para listar os fields que serão retornados, forma de escrever separados por vírgula
            ServiceParameter::text(
                name: 'Fields',
                description: 'Lista de campos a serem retornados, separados por vírgula. Exemplo: "CODIGO,NOME,DATA_CADASTRO". Use "*" para todos os campos.',
                validation: [
                    'min_length' => 1,
                    'max_length' => 1000,
                    'regex' => '/^[A-Z0-9_,*]+$/'
                ]
            )->withLabel('Campos (Fields)'),

            ServiceParameter::sql(
                name: 'WHERE',
                description: 'Digite a cláusula WHERE para filtrar os resultados. Exemplo: "CODIGO = 123 AND NOME LIKE \'%Teste%\'"',
                validation: [
                    'min_length' => 10,
                    'max_length' => 50000
                ]
            )->withLabel('Consulta WHERE')
            ->withPlaceholder('CODIGO = 123 AND NOME LIKE \'%Teste%\'')
            ->withGroup('query'),
        ]);
    }
}