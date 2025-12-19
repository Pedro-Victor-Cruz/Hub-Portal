<?php

namespace App\Services\Integrations\Sankhya\Services;

use App\Enums\IntegrationType;
use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceValidationException;
use App\Services\Core\ApiResponse;
use App\Services\Core\Integration\IntegrationService;
use App\Services\Integrations\Sankhya\SankhyaHttpRequest;
use App\Services\Parameter\ServiceParameter;
use App\Services\Utils\ResponseFormatters\ResponseFormatter;
use Exception;

/**
 * Serviço para executar consultas no Sankhya usando DbExplorerSP.executeQuery
 */
class SankhyaDbExplorerService extends IntegrationService
{
    protected string $serviceName = 'Sankhya | DB Explorer';
    protected string $description = 'Executa consultas SQL no banco de dados do Sankhya';
    protected ServiceType $serviceType = ServiceType::QUERY;
    protected SankhyaHttpRequest $sankhyaRequest;
    protected IntegrationType $requiredIntegrationType = IntegrationType::SANKHYA;

    public function __construct()
    {
        parent::__construct();

        // Cria instância de requisição HTTP específica do Sankhya
        $this->sankhyaRequest = new SankhyaHttpRequest($this->integration);
    }

    /**
     * Executa uma consulta SQL no Sankhya
     */
    protected function performService(array $params): ApiResponse
    {
        try {
            $validatedParams = $this->validateAndSanitizeParams($params);

            $sql = $validatedParams['SQL'];

            // Valida se é uma consulta SELECT segura
            if (!$this->validateSelectQuery($sql)) {
                return $this->error(
                    'Consulta SQL inválida',
                    ['Apenas consultas SELECT são permitidas']
                );
            }

            // Executa a consulta usando o método específico do SankhyaHttpRequest
            $response = $this->sankhyaRequest->callService('DbExplorerSP.executeQuery', [
                'sql' => $sql
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
                    'sql_executed' => $sql,
                    'original_metadata' => $response->getMetadata(),
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
        return ResponseFormatter::formatExecuteQueryResponse($data);
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
     * Valida se a SQL é uma consulta SELECT (por segurança)
     */
    public function validateSelectQuery(string $sql): bool
    {
        $sql = trim(strtoupper($sql));

        // Verifica se começa com SELECT ou WITH (para CTEs)
        if (!str_starts_with($sql, 'SELECT') && !str_starts_with($sql, 'WITH')) {
            return false;
        }

        // Lista de palavras perigosas que não devem estar presentes
        $dangerousKeywords = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE',
            'ALTER', 'TRUNCATE', 'EXEC', 'EXECUTE', 'MERGE',
            'BULK', 'BACKUP', 'RESTORE', 'SHUTDOWN', 'GRANT',
            'REVOKE', 'DENY', 'sp_', 'xp_', 'OPENROWSET',
            'OPENDATASOURCE', 'OPENQUERY'
        ];

        foreach ($dangerousKeywords as $keyword) {
            if (str_contains($sql, $keyword)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Valida parâmetros específicos do serviço
     * @throws ServiceValidationException
     */
    public function validateParams(array $params): bool
    {
        parent::validateParams($params);

        if (!isset($params['SQL']) || empty(trim($params['SQL']))) {
            throw new ServiceValidationException(
                'SQL é obrigatória',
                ['O parâmetro sql não pode estar vazio']
            );
        }

        if (!$this->validateSelectQuery($params['SQL'])) {
            throw new ServiceValidationException(
                'SQL inválida',
                ['Apenas consultas SELECT são permitidas por motivos de segurança']
            );
        }

        return true;
    }

    /**
     * Configura os parâmetros específicos deste serviço
     */
    protected function configureParameters(): void
    {
        $this->parameterManager->addMany([
            // Parâmetro principal - SQL obrigatória
            ServiceParameter::sql(
                name: 'SQL',
                required: true,
                description: 'Consulta SQL SELECT para execução no banco do Sankhya',
                validation: [
                    'min_length' => 10,
                    'max_length' => 50000
                ]
            )->withLabel('Consulta SQL')
            ->withPlaceholder('SELECT * FROM TABELA WHERE CONDICAO')
            ->withGroup('query'),

        ]);
    }
}