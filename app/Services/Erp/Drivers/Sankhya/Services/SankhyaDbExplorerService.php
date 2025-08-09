<?php

namespace App\Services\Erp\Drivers\Sankhya\Services;

use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceValidationException;
use App\Models\Company;
use App\Services\Core\ApiResponse;
use App\Services\Erp\Core\BaseErpService;
use App\Services\Erp\Drivers\Sankhya\SankhyaHttpRequest;
use App\Services\Utils\ResponseFormatters\SankhyaResponseFormatter;
use Exception;

/**
 * Serviço para executar consultas no Sankhya usando DbExplorerSP.executeQuery
 */
class SankhyaDbExplorerService extends BaseErpService
{
    protected string $serviceName = 'Sankhya DB Explorer';
    protected string $description = 'Executa consultas SQL no banco de dados do Sankhya';
    protected ServiceType $serviceType = ServiceType::QUERY;
    protected array $requiredParams = ['sql'];

    protected SankhyaHttpRequest $sankhyaRequest;

    /**
     * Inicializa o serviço com requisição específica do Sankhya
     * @throws Exception
     */
    public function __construct(Company $company)
    {
        parent::__construct($company);

        // Substitui a requisição genérica pela específica do Sankhya
        $this->sankhyaRequest = new SankhyaHttpRequest(
            $this->erpDriver->getSettings(),
            $this->erpDriver->getAuthHandler()
        );
    }

    /**
     * Executa uma consulta SQL no Sankhya
     */
    protected function performService(array $params): ApiResponse
    {
        try {
            $this->validateParams($params);

            $sql = $params['sql'];

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
        return SankhyaResponseFormatter::formatExecuteQueryResponse($data);
    }

    /**
     * Executa uma consulta simples (método auxiliar)
     */
    public function executeQuery(string $sql): ApiResponse
    {
        return $this->execute([
            'sql' => $sql
        ]);
    }

    /**
     * Executa uma consulta com parâmetros nomeados
     * @param string $sql Consulta SQL com parâmetros nomeados (ex: SELECT * FROM tabela WHERE coluna = :param)
     * @param array $namedParams Parâmetros nomeados a serem substituídos na consulta
     */
    public function executeQueryWithParams(string $sql, array $namedParams = [], int $maxRows = 1000): ApiResponse
    {
        // Substitui parâmetros nomeados na SQL
        foreach ($namedParams as $param => $value) {
            $sql = str_replace(":{$param}", $this->escapeValue($value), $sql);
        }

        return $this->executeQuery($sql, $maxRows);
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

        if (!isset($params['sql']) || empty(trim($params['sql']))) {
            throw new ServiceValidationException(
                'SQL é obrigatória',
                ['O parâmetro sql não pode estar vazio']
            );
        }

        if (!$this->validateSelectQuery($params['sql'])) {
            throw new ServiceValidationException(
                'SQL inválida',
                ['Apenas consultas SELECT são permitidas por motivos de segurança']
            );
        }

        return true;
    }

}