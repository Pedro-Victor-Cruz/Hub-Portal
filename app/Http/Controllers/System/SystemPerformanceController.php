<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\PerformanceMetric;
use App\Models\QueryPerformance;
use App\Models\EndpointMetric;
use App\Models\SystemHealth;
use App\Services\Core\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemPerformanceController extends Controller
{
    /**
     * Dashboard principal de performance
     */
    public function dashboard(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today'); // today, week, month, year
        $dateRange = $this->getDateRange($period);

        $metrics = [
            // Métricas gerais
            'overview' => [
                'total_requests' => PerformanceMetric::whereBetween('created_at', $dateRange)->count(),
                'avg_response_time' => round(PerformanceMetric::whereBetween('created_at', $dateRange)->avg('response_time'), 2),
                'avg_memory_usage' => round(PerformanceMetric::whereBetween('created_at', $dateRange)->avg('memory_usage') / 1024 / 1024, 2),
                'avg_cpu_usage' => round(PerformanceMetric::whereBetween('created_at', $dateRange)->avg('cpu_usage'), 2),
                'error_rate' => $this->calculateErrorRate($dateRange),
            ],

            // Endpoints mais lentos
            'slowest_endpoints' => $this->getSlowestEndpoints($dateRange),

            // Endpoints mais acessados
            'most_accessed_endpoints' => $this->getMostAccessedEndpoints($dateRange),

            // Queries mais lentas
            'slowest_queries' => $this->getSlowestQueries($dateRange),

            // Queries mais executadas
            'most_frequent_queries' => $this->getMostFrequentQueries($dateRange),

            // Uso de recursos ao longo do tempo
            'resource_usage_timeline' => $this->getResourceTimeline($dateRange),

            // Saúde do sistema atual
            'current_health' => $this->getCurrentSystemHealth(),

            // Alertas ativos
            'active_alerts' => $this->getActiveAlerts(),
        ];

        return ApiResponse::success($metrics, 'Dashboard de performance')->toJson();
    }

    /**
     * Métricas de performance detalhadas
     */
    public function metrics(Request $request): JsonResponse
    {
        $query = PerformanceMetric::query();

        // Filtros
        if ($request->has('endpoint')) {
            $query->where('endpoint', $request->endpoint);
        }

        if ($request->has('endpoint_method')) {
            $query->where('method', $request->endpoint_method);
        }

        if ($request->has('status_code')) {
            $query->where('status_code', $request->status_code);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        // Filtrar apenas requests lentas
        if ($request->boolean('slow_only')) {
            $threshold = $request->get('threshold', 1000); // ms
            $query->where('response_time', '>', $threshold);
        }

        $metrics = $query->orderBy('created_at', 'desc')
            ->limit(500)
            ->get();

        return ApiResponse::success($metrics, 'Métricas de performance')->toJson();
    }

    /**
     * Análise detalhada de um endpoint específico
     */
    public function endpointAnalysis(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|string',
            'period' => 'sometimes|in:today,week,month,year'
        ]);

        $endpoint = $request->endpoint;
        $dateRange = $this->getDateRange($request->get('period', 'week'));

        $analysis = [
            'endpoint' => $endpoint,
            'period' => $request->get('period', 'week'),

            // Estatísticas gerais
            'statistics' => [
                'total_requests' => PerformanceMetric::where('endpoint', $endpoint)
                    ->whereBetween('created_at', $dateRange)
                    ->count(),

                'avg_response_time' => round(PerformanceMetric::where('endpoint', $endpoint)
                    ->whereBetween('created_at', $dateRange)
                    ->avg('response_time'), 2),

                'min_response_time' => PerformanceMetric::where('endpoint', $endpoint)
                    ->whereBetween('created_at', $dateRange)
                    ->min('response_time'),

                'max_response_time' => PerformanceMetric::where('endpoint', $endpoint)
                    ->whereBetween('created_at', $dateRange)
                    ->max('response_time'),

                'p95_response_time' => $this->calculatePercentile($endpoint, $dateRange, 95),
                'p99_response_time' => $this->calculatePercentile($endpoint, $dateRange, 99),

                'avg_memory_usage' => round(PerformanceMetric::where('endpoint', $endpoint)
                        ->whereBetween('created_at', $dateRange)
                        ->avg('memory_usage') / 1024 / 1024, 2),

                'avg_queries_count' => round(PerformanceMetric::where('endpoint', $endpoint)
                    ->whereBetween('created_at', $dateRange)
                    ->avg('queries_count'), 2),
            ],

            // Distribuição por status code
            'status_distribution' => PerformanceMetric::where('endpoint', $endpoint)
                ->whereBetween('created_at', $dateRange)
                ->select('status_code', DB::raw('count(*) as count'))
                ->groupBy('status_code')
                ->get(),

            // Timeline de performance
            'timeline' => PerformanceMetric::where('endpoint', $endpoint)
                ->whereBetween('created_at', $dateRange)
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'),
                    DB::raw('AVG(response_time) as avg_response'),
                    DB::raw('COUNT(*) as requests'),
                    DB::raw('AVG(memory_usage) as avg_memory')
                )
                ->groupBy('hour')
                ->orderBy('hour')
                ->get(),

            // Queries relacionadas
            'related_queries' => QueryPerformance::where('endpoint', $endpoint)
                ->whereBetween('created_at', $dateRange)
                ->select('query_hash', 'query_type', DB::raw('AVG(duration) as avg_duration'), DB::raw('COUNT(*) as count'))
                ->groupBy('query_hash', 'query_type')
                ->orderByDesc('avg_duration')
                ->limit(10)
                ->get(),
        ];

        return ApiResponse::success($analysis, 'Análise do endpoint')->toJson();
    }

    /**
     * Análise de queries do banco de dados
     */
    public function queryAnalysis(Request $request): JsonResponse
    {
        $dateRange = $this->getDateRange($request->get('period', 'today'));

        $analysis = [
            // Queries mais lentas
            'slowest_queries' => QueryPerformance::whereBetween('created_at', $dateRange)
                ->select(
                    'query_hash',
                    'query_type',
                    'table_name',
                    DB::raw('AVG(duration) as avg_duration'),
                    DB::raw('MAX(duration) as max_duration'),
                    DB::raw('COUNT(*) as execution_count'),
                    DB::raw('MAX(sql_query) as sample_query')
                )
                ->groupBy('query_hash', 'query_type', 'table_name')
                ->orderByDesc('avg_duration')
                ->limit(20)
                ->get(),

            // Queries N+1 detectadas
            'n_plus_one_queries' => QueryPerformance::whereBetween('created_at', $dateRange)
                ->where('is_duplicate', true)
                ->select(
                    'query_hash',
                    'endpoint',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('MAX(sql_query) as sample_query')
                )
                ->groupBy('query_hash', 'endpoint')
                ->having('count', '>', 5)
                ->orderByDesc('count')
                ->limit(10)
                ->get(),

            // Queries por tipo
            'by_query_type' => QueryPerformance::whereBetween('created_at', $dateRange)
                ->select(
                    'query_type',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(duration) as avg_duration')
                )
                ->groupBy('query_type')
                ->get(),

            // Tabelas mais consultadas
            'most_queried_tables' => QueryPerformance::whereBetween('created_at', $dateRange)
                ->select(
                    'table_name',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('AVG(duration) as avg_duration')
                )
                ->whereNotNull('table_name')
                ->groupBy('table_name')
                ->orderByDesc('count')
                ->limit(15)
                ->get(),

            // Estatísticas gerais
            'statistics' => [
                'total_queries' => QueryPerformance::whereBetween('created_at', $dateRange)->count(),
                'avg_duration' => round(QueryPerformance::whereBetween('created_at', $dateRange)->avg('duration'), 2),
                'slow_queries_count' => QueryPerformance::whereBetween('created_at', $dateRange)
                    ->where('duration', '>', 100)
                    ->count(),
            ],
        ];

        return ApiResponse::success($analysis, 'Análise de queries')->toJson();
    }

    /**
     * Saúde do sistema em tempo real
     */
    public function systemHealth(): JsonResponse
    {
        $health = [
            'timestamp' => now()->toDateTimeString(),

            // Informações do servidor
            'server' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit'),
            ],

            // Uso de recursos atual
            'resources' => [
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
                'cpu_load' => $this->getCpuLoad(),
            ],

            // Status do banco de dados
            'database' => [
                'connected' => $this->checkDatabaseConnection(),
                'total_connections' => $this->getDatabaseConnections(),
                'slow_queries' => $this->getSlowQueriesCount(),
            ],

            // Status do cache
            'cache' => [
                'driver' => config('cache.default'),
                'working' => $this->checkCacheConnection(),
            ],

            // Últimas métricas (últimos 5 minutos)
            'recent_metrics' => [
                'avg_response_time' => round(PerformanceMetric::where('created_at', '>', now()->subMinutes(5))
                    ->avg('response_time'), 2),
                'requests_count' => PerformanceMetric::where('created_at', '>', now()->subMinutes(5))
                    ->count(),
                'error_rate' => $this->calculateErrorRate([now()->subMinutes(5), now()]),
            ],

            // Alertas
            'alerts' => $this->getActiveAlerts(),
        ];

        return ApiResponse::success($health, 'Saúde do sistema')->toJson();
    }

    /**
     * Histórico de saúde do sistema
     */
    public function healthHistory(Request $request): JsonResponse
    {
        $dateRange = $this->getDateRange($request->get('period', 'today'));

        $history = SystemHealth::whereBetween('created_at', $dateRange)
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($history, 'Histórico de saúde')->toJson();
    }

    /**
     * Comparação de performance entre períodos
     */
    public function comparePerformance(Request $request): JsonResponse
    {
        $request->validate([
            'period1_start' => 'required|date',
            'period1_end' => 'required|date',
            'period2_start' => 'required|date',
            'period2_end' => 'required|date',
        ]);

        $period1 = [$request->period1_start, $request->period1_end];
        $period2 = [$request->period2_start, $request->period2_end];

        $comparison = [
            'period1' => $this->getPeriodMetrics($period1),
            'period2' => $this->getPeriodMetrics($period2),
            'comparison' => $this->calculateComparison(
                $this->getPeriodMetrics($period1),
                $this->getPeriodMetrics($period2)
            ),
        ];

        return ApiResponse::success($comparison, 'Comparação de performance')->toJson();
    }

    /**
     * Relatório de performance (exportável)
     */
    public function generateReport(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $dateRange = [$request->start_date, $request->end_date];

        $report = [
            'period' => [
                'start' => $request->start_date,
                'end' => $request->end_date,
            ],
            'generated_at' => now()->toDateTimeString(),

            'summary' => $this->getPeriodMetrics($dateRange),
            'slowest_endpoints' => $this->getSlowestEndpoints($dateRange, 20),
            'most_accessed_endpoints' => $this->getMostAccessedEndpoints($dateRange, 20),
            'slowest_queries' => $this->getSlowestQueries($dateRange, 30),
            'most_frequent_queries' => $this->getMostFrequentQueries($dateRange, 30),
            'error_summary' => $this->getErrorSummary($dateRange),
            'recommendations' => $this->generateRecommendations($dateRange),
        ];

        return ApiResponse::success($report, 'Relatório de performance gerado')->toJson();
    }

    /**
     * Limpar métricas antigas
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);

        $days = $request->days;
        $date = now()->subDays($days);

        $deleted = [
            'performance_metrics' => PerformanceMetric::where('created_at', '<', $date)->delete(),
            'query_performance' => QueryPerformance::where('created_at', '<', $date)->delete(),
            'system_health' => SystemHealth::where('created_at', '<', $date)->delete(),
        ];

        return ApiResponse::success([
            'deleted_counts' => $deleted,
            'cutoff_date' => $date->toDateTimeString(),
            'total_deleted' => array_sum($deleted),
        ], "Métricas antigas removidas com sucesso")->toJson();
    }

    // ========== MÉTODOS AUXILIARES ==========

    private function getDateRange(string $period): array
    {
        return match($period) {
            'today' => [now()->startOfDay(), now()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            'week' => [now()->startOfWeek(), now()],
            'month' => [now()->startOfMonth(), now()],
            'year' => [now()->startOfYear(), now()],
            default => [now()->startOfDay(), now()],
        };
    }

    private function calculateErrorRate(array $dateRange): float
    {
        $total = PerformanceMetric::whereBetween('created_at', $dateRange)->count();

        if ($total === 0) return 0;

        $errors = PerformanceMetric::whereBetween('created_at', $dateRange)
            ->where('status_code', '>=', 400)
            ->count();

        return round(($errors / $total) * 100, 2);
    }

    private function getSlowestEndpoints(array $dateRange, int $limit = 10): mixed
    {
        return PerformanceMetric::whereBetween('created_at', $dateRange)
            ->select(
                'endpoint',
                'method',
                DB::raw('AVG(response_time) as avg_response_time'),
                DB::raw('MAX(response_time) as max_response_time'),
                DB::raw('COUNT(*) as requests_count')
            )
            ->groupBy('endpoint', 'method')
            ->orderByDesc('avg_response_time')
            ->limit($limit)
            ->get();
    }

    private function getMostAccessedEndpoints(array $dateRange, int $limit = 10): mixed
    {
        return PerformanceMetric::whereBetween('created_at', $dateRange)
            ->select(
                'endpoint',
                'method',
                DB::raw('COUNT(*) as requests_count'),
                DB::raw('AVG(response_time) as avg_response_time')
            )
            ->groupBy('endpoint', 'method')
            ->orderByDesc('requests_count')
            ->limit($limit)
            ->get();
    }

    private function getSlowestQueries(array $dateRange, int $limit = 10): mixed
    {
        return QueryPerformance::whereBetween('created_at', $dateRange)
            ->select(
                'query_hash',
                'query_type',
                'table_name',
                DB::raw('AVG(duration) as avg_duration'),
                DB::raw('COUNT(*) as execution_count'),
                DB::raw('MAX(sql_query) as sample_query')
            )
            ->groupBy('query_hash', 'query_type', 'table_name')
            ->orderByDesc('avg_duration')
            ->limit($limit)
            ->get();
    }

    private function getMostFrequentQueries(array $dateRange, int $limit = 10): mixed
    {
        return QueryPerformance::whereBetween('created_at', $dateRange)
            ->select(
                'query_hash',
                'query_type',
                'table_name',
                DB::raw('COUNT(*) as execution_count'),
                DB::raw('AVG(duration) as avg_duration'),
                DB::raw('MAX(sql_query) as sample_query')
            )
            ->groupBy('query_hash', 'query_type', 'table_name')
            ->orderByDesc('execution_count')
            ->limit($limit)
            ->get();
    }

    private function getResourceTimeline(array $dateRange): mixed
    {
        return PerformanceMetric::whereBetween('created_at', $dateRange)
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour'),
                DB::raw('AVG(response_time) as avg_response_time'),
                DB::raw('AVG(memory_usage) as avg_memory_usage'),
                DB::raw('AVG(cpu_usage) as avg_cpu_usage'),
                DB::raw('COUNT(*) as requests_count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
    }

    private function getCurrentSystemHealth(): array
    {
        return [
            'status' => 'healthy', // healthy, degraded, critical
            'uptime' => $this->getSystemUptime(),
            'load_average' => $this->getCpuLoad(),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'active_connections' => $this->getDatabaseConnections(),
        ];
    }

    private function getActiveAlerts(): array
    {
        $alerts = [];

        // Verificar response time alto
        $avgResponseTime = PerformanceMetric::where('created_at', '>', now()->subMinutes(5))
            ->avg('response_time');

        if ($avgResponseTime > 1000) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Tempo de resposta médio acima de 1 segundo',
                'value' => round($avgResponseTime, 2) . ' ms',
            ];
        }

        // Verificar taxa de erro
        $errorRate = $this->calculateErrorRate([now()->subMinutes(5), now()]);
        if ($errorRate > 5) {
            $alerts[] = [
                'type' => 'critical',
                'message' => 'Taxa de erro elevada',
                'value' => $errorRate . '%',
            ];
        }

        return $alerts;
    }

    private function calculatePercentile(string $endpoint, array $dateRange, int $percentile): ?float
    {
        $responseTimes = PerformanceMetric::where('endpoint', $endpoint)
            ->whereBetween('created_at', $dateRange)
            ->pluck('response_time')
            ->sort()
            ->values();

        if ($responseTimes->isEmpty()) {
            return null;
        }

        $index = ceil(($percentile / 100) * $responseTimes->count()) - 1;
        return round($responseTimes[$index] ?? 0, 2);
    }

    private function getPeriodMetrics(array $dateRange): array
    {
        return [
            'total_requests' => PerformanceMetric::whereBetween('created_at', $dateRange)->count(),
            'avg_response_time' => round(PerformanceMetric::whereBetween('created_at', $dateRange)->avg('response_time'), 2),
            'avg_memory_usage' => round(PerformanceMetric::whereBetween('created_at', $dateRange)->avg('memory_usage') / 1024 / 1024, 2),
            'error_rate' => $this->calculateErrorRate($dateRange),
            'total_queries' => QueryPerformance::whereBetween('created_at', $dateRange)->count(),
            'avg_query_duration' => round(QueryPerformance::whereBetween('created_at', $dateRange)->avg('duration'), 2),
        ];
    }

    private function calculateComparison(array $period1, array $period2): array
    {
        $comparison = [];

        foreach ($period1 as $key => $value) {
            if (isset($period2[$key]) && is_numeric($value) && is_numeric($period2[$key])) {
                $diff = $value - $period2[$key];
                $percentChange = $period2[$key] != 0 ? round(($diff / $period2[$key]) * 100, 2) : 0;

                $comparison[$key] = [
                    'difference' => round($diff, 2),
                    'percent_change' => $percentChange,
                    'trend' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'stable'),
                ];
            }
        }

        return $comparison;
    }

    private function getErrorSummary(array $dateRange): array
    {
        return [
            'total_errors' => PerformanceMetric::whereBetween('created_at', $dateRange)
                ->where('status_code', '>=', 400)
                ->count(),

            'by_status_code' => PerformanceMetric::whereBetween('created_at', $dateRange)
                ->where('status_code', '>=', 400)
                ->select('status_code', DB::raw('COUNT(*) as count'))
                ->groupBy('status_code')
                ->get(),

            'error_endpoints' => PerformanceMetric::whereBetween('created_at', $dateRange)
                ->where('status_code', '>=', 400)
                ->select('endpoint', 'method', 'status_code', DB::raw('COUNT(*) as count'))
                ->groupBy('endpoint', 'method', 'status_code')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
        ];
    }

    private function generateRecommendations(array $dateRange): array
    {
        $recommendations = [];

        // Verificar endpoints lentos
        $slowEndpoints = $this->getSlowestEndpoints($dateRange, 5);
        if ($slowEndpoints->isNotEmpty() && $slowEndpoints->first()->avg_response_time > 1000) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'high',
                'message' => 'Existem endpoints com tempo de resposta acima de 1 segundo',
                'action' => 'Otimizar queries ou implementar cache',
            ];
        }

        // Verificar queries N+1
        $nPlusOne = QueryPerformance::whereBetween('created_at', $dateRange)
            ->where('is_duplicate', true)
            ->count();

        if ($nPlusOne > 100) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'high',
                'message' => 'Detectadas possíveis queries N+1',
                'action' => 'Implementar eager loading nos relacionamentos',
            ];
        }

        // Verificar uso de memória
        $avgMemory = PerformanceMetric::whereBetween('created_at', $dateRange)
                ->avg('memory_usage') / 1024 / 1024;

        if ($avgMemory > 100) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'medium',
                'message' => 'Uso elevado de memória detectado',
                'action' => 'Revisar processamento de dados em massa',
            ];
        }

        return $recommendations;
    }

    private function checkDatabaseConnection(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function checkCacheConnection(): bool
    {
        try {
            Cache::put('health_check', true, 10);
            return Cache::get('health_check') === true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getDatabaseConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS WHERE variable_name = 'Threads_connected'");
            return $result[0]->Value ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getSlowQueriesCount(): int
    {
        return QueryPerformance::where('created_at', '>', now()->subHour())
            ->where('duration', '>', 100)
            ->count();
    }

    private function getCpuLoad(): array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2),
            ];
        }

        return ['1min' => 0, '5min' => 0, '15min' => 0];
    }

    private function getSystemUptime(): ?string
    {
        if (file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            $seconds = (int) explode(' ', $uptime)[0];

            $days = floor($seconds / 86400);
            $hours = floor(($seconds % 86400) / 3600);
            $minutes = floor(($seconds % 3600) / 60);

            return "{$days}d {$hours}h {$minutes}m";
        }

        return null;
    }
}