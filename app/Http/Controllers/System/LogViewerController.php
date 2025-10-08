<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
use App\Services\Core\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogViewerController extends Controller
{

    /**
     * Listagem de logs com filtros
     */
    public function index(Request $request): JsonResponse
    {
        $query = SystemLog::query()->with('user');

        // Filtro por nível
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Filtro por ação
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filtro por módulo
        if ($request->has('module')) {
            $query->where('module', $request->module);
        }

        // Filtro por usuário
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filtro por período
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        // Filtro por IP
        if ($request->has('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        // Filtro por batch
        if ($request->has('batch_id')) {
            $query->where('batch_id', $request->batch_id);
        }

        // Busca textual
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        // Ordenação
        $query->orderBy('created_at', 'desc');

        $logs = $query->limit(200)->get();


        return ApiResponse::success($logs, 'Logs encontrados com sucesso')->toJson();
    }

    /**
     * Detalhes de um log específico
     */
    public function show(int $id): JsonResponse
    {
        $log = SystemLog::with(['user', 'loggable'])->findOrFail($id);
        return ApiResponse::success($log, 'Detalhes do log')->toJson();
    }

    /**
     * Estatísticas dos logs
     */
    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30));
        $endDate = $request->get('end_date', now());

        $stats = [
            // Total de logs
            'total_logs' => SystemLog::whereBetween('created_at', [$startDate, $endDate])->count(),

            // Logs por nível
            'by_level' => SystemLog::whereBetween('created_at', [$startDate, $endDate])
                ->select('level', DB::raw('count(*) as total'))
                ->groupBy('level')
                ->get()
                ->pluck('total', 'level'),

            // Logs por ação
            'by_action' => SystemLog::whereBetween('created_at', [$startDate, $endDate])
                ->select('action', DB::raw('count(*) as total'))
                ->groupBy('action')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),

            // Logs por módulo
            'by_module' => SystemLog::whereBetween('created_at', [$startDate, $endDate])
                ->select('module', DB::raw('count(*) as total'))
                ->groupBy('module')
                ->orderByDesc('total')
                ->get(),

            // Usuários mais ativos
            'top_users' => SystemLog::whereBetween('created_at', [$startDate, $endDate])
                ->select('user_id', 'user_name', DB::raw('count(*) as total'))
                ->whereNotNull('user_id')
                ->groupBy('user_id', 'user_name')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),

            // IPs mais frequentes
            'top_ips' => SystemLog::whereBetween('created_at', [$startDate, $endDate])
                ->select('ip_address', DB::raw('count(*) as total'))
                ->whereNotNull('ip_address')
                ->groupBy('ip_address')
                ->orderByDesc('total')
                ->limit(10)
                ->get(),

            // Logs de erro
            'error_count' => SystemLog::whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('level', [
                    SystemLog::LEVEL_ERROR,
                    SystemLog::LEVEL_CRITICAL,
                    SystemLog::LEVEL_ALERT,
                    SystemLog::LEVEL_EMERGENCY
                ])
                ->count(),

            // Logs por dia (últimos 30 dias)
            'daily_logs' => SystemLog::whereBetween('created_at', [$startDate, $endDate])
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get(),

            // Tempo médio de resposta
            'avg_response_time' => SystemLog::whereBetween('created_at', [$startDate, $endDate])
                ->whereNotNull('response_time')
                ->avg('response_time'),
        ];

        return ApiResponse::success($stats, 'Estatísticas de logs')->toJson();
    }

    /**
     * Timeline de atividades de um usuário
     */
    public function userTimeline(int $userId): JsonResponse
    {
        $logs = SystemLog::where('user_id', $userId)
            ->with('loggable')
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($logs, 'Timeline do usuário')->toJson();
    }

    /**
     * Logs de um model específico (histórico de mudanças)
     */
    public function modelHistory(Request $request): JsonResponse
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer'
        ]);

        $logs = SystemLog::where('loggable_type', $request->model_type)
            ->where('loggable_id', $request->model_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return ApiResponse::success($logs, 'Histórico do modelo')->toJson();
    }

    /**
     * Logs de um batch específico
     */
    public function batchLogs(string $batchId): JsonResponse
    {
        $logs = SystemLog::where('batch_id', $batchId)
            ->with('user')
            ->orderBy('created_at')
            ->get();

        return ApiResponse::success($logs, 'Logs do batch')->toJson();
    }

    /**
     * Limpar logs antigos
     */
    public function cleanup(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);

        $days = $request->days;
        $date = now()->subDays($days);

        $count = SystemLog::where('created_at', '<', $date)->delete();

        return ApiResponse::success([
            'deleted_count' => $count,
            'cutoff_date' => $date->toDateTimeString()
        ], "Removidos {$count} logs com mais de {$days} dias")->toJson();
    }
}