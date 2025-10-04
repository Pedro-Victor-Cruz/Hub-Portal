<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SystemLog;
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

        // Paginação
        $perPage = $request->get('per_page', 50);
        $logs = $query->paginate($perPage);

        return response()->json([
            'message' => 'Logs encontrados com sucesso',
            'data' => $logs
        ]);
    }

    /**
     * Detalhes de um log específico
     */
    public function show(int $id): JsonResponse
    {
        $log = SystemLog::with(['user', 'loggable'])->findOrFail($id);

        return response()->json([
            'message' => 'Log encontrado com sucesso',
            'data' => $log
        ]);
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

        return response()->json([
            'message' => 'Estatísticas geradas com sucesso',
            'data' => $stats
        ]);
    }

    /**
     * Timeline de atividades de um usuário
     */
    public function userTimeline(int $userId, Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 50);

        $logs = SystemLog::where('user_id', $userId)
            ->with('loggable')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'message' => 'Timeline do usuário encontrada',
            'data' => $logs
        ]);
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

        return response()->json([
            'message' => 'Histórico do registro encontrado',
            'data' => $logs
        ]);
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

        return response()->json([
            'message' => 'Logs do batch encontrados',
            'data' => $logs
        ]);
    }

    /**
     * Logs de segurança (tentativas de acesso, falhas de login, etc)
     */
    public function securityLogs(Request $request): JsonResponse
    {
        $query = SystemLog::query()
            ->whereIn('action', [
                SystemLog::ACTION_LOGIN_FAILED,
                'delete_attempt_blocked',
                'delete_superadmin_blocked',
                'unauthorized_access',
                'password_reset_failed'
            ])
            ->orWhere('level', SystemLog::LEVEL_WARNING)
            ->orWhere('level', SystemLog::LEVEL_ERROR)
            ->with('user');

        // Filtro por período
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return response()->json([
            'message' => 'Logs de segurança encontrados',
            'data' => $logs
        ]);
    }

    /**
     * Exportar logs (CSV)
     */
    public function export(Request $request)
    {
        $query = SystemLog::query()->with('user');

        // Aplicar os mesmos filtros do index
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }
        if ($request->has('module')) {
            $query->where('module', $request->module);
        }
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $filename = 'logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // Cabeçalho do CSV
            fputcsv($file, [
                'ID',
                'Data/Hora',
                'Nível',
                'Ação',
                'Módulo',
                'Usuário',
                'IP',
                'Descrição'
            ]);

            // Dados
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->level,
                    $log->action,
                    $log->module,
                    $log->user_name ?? 'Sistema',
                    $log->ip_address,
                    $log->description
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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

        return response()->json([
            'message' => "Removidos {$count} logs com mais de {$days} dias",
            'data' => [
                'deleted_count' => $count,
                'cutoff_date' => $date->toDateTimeString()
            ]
        ]);
    }
}