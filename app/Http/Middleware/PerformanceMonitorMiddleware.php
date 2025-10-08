<?php

namespace App\Http\Middleware;

use App\Models\PerformanceMetric;
use App\Models\QueryPerformance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMonitorMiddleware
{
    private $startTime;
    private $startMemory;
    private $queries = [];

    public function handle(Request $request, Closure $next)
    {
        // Não monitorar rotas de performance para evitar recursão
        if ($this->shouldSkipMonitoring($request)) {
            return $next($request);
        }

        // Registrar início
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);

        // Habilitar log de queries
        $this->enableQueryLogging();

        // Processar request
        $response = $next($request);

        // Registrar métricas após response
        $this->recordMetrics($request, $response);

        return $response;
    }

    private function shouldSkipMonitoring(Request $request): bool
    {
        $skipRoutes = [
            'api/performance/*',
            'api/system/health',
        ];

        foreach ($skipRoutes as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            $this->queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        });
    }

    private function recordMetrics(Request $request, $response): void
    {
        try {
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            // Calcular métricas
            $responseTime = ($endTime - $this->startTime) * 1000; // em ms
            $memoryUsage = $endMemory - $this->startMemory;
            $memoryPeak = memory_get_peak_usage(true);

            // Criar registro de performance
            $performanceMetric = PerformanceMetric::create([
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'status_code' => $response->status(),
                'response_time' => round($responseTime, 2),
                'memory_usage' => $memoryUsage,
                'memory_peak' => $memoryPeak,
                'cpu_usage' => $this->getCpuUsage(),
                'queries_count' => count($this->queries),
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_data' => $this->sanitizeRequestData($request),
                'response_data' => $this->sanitizeResponseData($response),
                'session_id' => session()->getId(),
            ]);

            // Registrar queries
            $this->recordQueries($performanceMetric, $request);

            // Verificar alertas
            $this->checkAlerts($responseTime, $memoryUsage);

        } catch (\Exception $e) {
            // Não falhar a requisição por erro no monitoramento
            Log::error('Erro ao registrar métricas de performance: ' . $e->getMessage());
        }
    }

    private function recordQueries(PerformanceMetric $performanceMetric, Request $request): void
    {
        if (empty($this->queries)) {
            return;
        }

        // Detectar queries duplicadas (possível N+1)
        $queryHashes = [];

        foreach ($this->queries as $query) {
            $normalizedSql = $this->normalizeQuery($query['sql']);
            $queryHash = md5($normalizedSql);
            $isDuplicate = isset($queryHashes[$queryHash]);

            if (!$isDuplicate) {
                $queryHashes[$queryHash] = 1;
            } else {
                $queryHashes[$queryHash]++;
            }

            // Determinar tipo e tabela da query
            $queryType = $this->getQueryType($query['sql']);
            $tableName = $this->extractTableName($query['sql']);

            QueryPerformance::create([
                'performance_metric_id' => $performanceMetric->id,
                'sql_query' => $query['sql'],
                'query_hash' => $queryHash,
                'query_type' => $queryType,
                'duration' => $query['time'],
                'table_name' => $tableName,
                'endpoint' => $request->path(),
                'is_duplicate' => $isDuplicate,
                'bindings' => $query['bindings'],
                'stack_trace' => $this->getQueryStackTrace(),
            ]);
        }

        // Log se detectar muitas queries duplicadas
        foreach ($queryHashes as $hash => $count) {
            if ($count > 10) {
                Log::warning("Possível N+1 detectado no endpoint {$request->path()}: {$count} queries duplicadas");
            }
        }
    }

    private function normalizeQuery(string $sql): string
    {
        // Remove valores específicos para agrupar queries similares
        $normalized = preg_replace('/\b\d+\b/', '?', $sql);
        $normalized = preg_replace("/'.+?'/", '?', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    private function getQueryType(string $sql): string
    {
        $sql = strtoupper(trim($sql));

        if (str_starts_with($sql, 'SELECT')) return 'SELECT';
        if (str_starts_with($sql, 'INSERT')) return 'INSERT';
        if (str_starts_with($sql, 'UPDATE')) return 'UPDATE';
        if (str_starts_with($sql, 'DELETE')) return 'DELETE';

        return 'OTHER';
    }

    private function extractTableName(string $sql): ?string
    {
        // Tentar extrair nome da tabela
        if (preg_match('/\bFROM\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\bINTO\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }

        if (preg_match('/\bUPDATE\s+`?(\w+)`?/i', $sql, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function getQueryStackTrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $filtered = [];

        foreach ($trace as $item) {
            if (isset($item['file']) && !str_contains($item['file'], 'vendor')) {
                $filtered[] = [
                    'file' => str_replace(base_path(), '', $item['file']),
                    'line' => $item['line'] ?? null,
                    'function' => $item['function'] ?? null,
                ];
            }
        }

        return array_slice($filtered, 0, 5);
    }

    private function getCpuUsage(): ?float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0], 2);
        }

        return null;
    }

    private function sanitizeRequestData(Request $request): array
    {
        $data = [
            'query' => $request->query(),
            'route' => $request->route()?->getName(),
        ];

        // Não incluir dados sensíveis
        $sensitiveKeys = ['password', 'token', 'secret', 'key', 'authorization'];
        $input = $request->except($sensitiveKeys);

        // Limitar tamanho dos dados
        if (strlen(json_encode($input)) < 10000) {
            $data['input'] = $input;
        }

        return $data;
    }

    private function sanitizeResponseData($response): ?array
    {
        // Não salvar response completa para economizar espaço
        // Apenas informações básicas
        return [
            'status' => $response->status(),
            'headers' => $this->sanitizeHeaders($response->headers->all()),
        ];
    }

    private function sanitizeHeaders(array $headers): array
    {
        $allowed = ['content-type', 'cache-control'];
        return array_intersect_key($headers, array_flip($allowed));
    }

    private function checkAlerts(float $responseTime, int $memoryUsage): void
    {
        // Alerta para resposta lenta
        if ($responseTime > 2000) {
            Log::warning("Resposta lenta detectada: {$responseTime}ms");
        }

        // Alerta para uso alto de memória
        $memoryMB = $memoryUsage / 1024 / 1024;
        if ($memoryMB > 100) {
            Log::warning("Alto uso de memória detectado: {$memoryMB}MB");
        }

        // Alerta para muitas queries
        if (count($this->queries) > 50) {
            Log::warning("Muitas queries detectadas: " . count($this->queries));
        }
    }
}