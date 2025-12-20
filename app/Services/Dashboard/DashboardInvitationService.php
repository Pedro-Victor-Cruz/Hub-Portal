<?php

namespace App\Services\Dashboard;

use App\Models\Dashboard;
use App\Models\DashboardInvitation;
use App\Services\Core\ApiResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciamento de convites de dashboard
 */
class DashboardInvitationService
{
    /**
     * Cria um novo convite para um dashboard
     */
    public function createInvitation(string $dashboardKey, array $data): ApiResponse
    {
        DB::beginTransaction();
        try {
            $dashboard = Dashboard::where('key', $dashboardKey)->first();

            if (!$dashboard) {
                return ApiResponse::error("Dashboard '{$dashboardKey}' não encontrado");
            }

            // Processar data de expiração
            $expiresAt = null;
            if (!empty($data['expires_at'])) {
                $expiresAt = Carbon::parse($data['expires_at']);

                if ($expiresAt->isPast()) {
                    return ApiResponse::error('A data de expiração deve ser futura');
                }
            } elseif (!empty($data['expires_in_days'])) {
                $expiresAt = now()->addDays($data['expires_in_days']);
            }

            $auth = Auth::guard('auth')->user();

            $invitation = DashboardInvitation::create([
                'dashboard_id' => $dashboard->id,
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'expires_at' => $expiresAt,
                'max_uses' => $data['max_uses'] ?? null,
                'active' => $data['active'] ?? true,
                'created_by' => $auth->id,
            ]);

            DB::commit();

            return ApiResponse::success([
                'invitation' => $invitation,
                'url' => $invitation->getUrl(),
                'status' => $invitation->getStatusInfo(),
            ], 'Convite criado com sucesso');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar convite: " . $e->getMessage());
            return ApiResponse::error('Erro ao criar convite', [$e->getMessage()]);
        }
    }

    /**
     * Lista todos os convites de um dashboard
     */
    public function listInvitations(string $dashboardKey, bool $activeOnly = false): ApiResponse
    {
        try {
            $dashboard = Dashboard::where('key', $dashboardKey)->first();

            if (!$dashboard) {
                return ApiResponse::error("Dashboard '{$dashboardKey}' não encontrado");
            }

            $query = $dashboard->invitations()->with('creator:id,name');

            if ($activeOnly) {
                $query->valid();
            }

            $invitations = $query->orderBy('created_at', 'desc')->get();

            $data = $invitations->map(function ($invitation) {
                return [
                    'token' => $invitation->token,
                    'name' => $invitation->name,
                    'description' => $invitation->description,
                    'url' => $invitation->getUrl(),
                    'created_by' => $invitation->creator?->name,
                    'created_at' => $invitation->created_at,
                    'status' => $invitation->getStatusInfo(),
                ];
            });

            return ApiResponse::success($data, 'Convites carregados');

        } catch (\Exception $e) {
            Log::error("Erro ao listar convites: " . $e->getMessage());
            return ApiResponse::error('Erro ao listar convites', [$e->getMessage()]);
        }
    }

    /**
     * Obtém detalhes de um convite específico
     */
    public function getInvitation(string $token): ApiResponse
    {
        try {
            $invitation = DashboardInvitation::where('token', $token)
                ->with(['dashboard', 'creator:id,name'])
                ->first();

            if (!$invitation) {
                return ApiResponse::error('Convite não encontrado');
            }

            $data = [
                'token' => $invitation->token,
                'name' => $invitation->name,
                'description' => $invitation->description,
                'dashboard' => [
                    'key' => $invitation->dashboard->key,
                    'name' => $invitation->dashboard->name,
                    'description' => $invitation->dashboard->description,
                    'icon' => $invitation->dashboard->icon,
                ],
                'url' => $invitation->getUrl(),
                'created_by' => $invitation->creator?->name,
                'created_at' => $invitation->created_at,
                'status' => $invitation->getStatusInfo(),
            ];

            return ApiResponse::success($data, 'Convite carregado');

        } catch (\Exception $e) {
            Log::error("Erro ao buscar convite: " . $e->getMessage());
            return ApiResponse::error('Erro ao buscar convite', [$e->getMessage()]);
        }
    }

    /**
     * Valida e registra acesso via convite
     */
    public function validateAndAccess(string $token, ?string $ipAddress = null, ?string $userAgent = null): ApiResponse
    {
        DB::beginTransaction();
        try {
            $invitation = DashboardInvitation::where('token', $token)
                ->with('dashboard')
                ->first();

            if (!$invitation) {
                return ApiResponse::error('Convite não encontrado');
            }

            if (!$invitation->isValid()) {
                $status = $invitation->getStatusInfo();
                return ApiResponse::error($status['message']);
            }

            $user = Auth::guard('auth')->user();

            // Registra o acesso
            $invitation->recordAccess(
                $user?->id,
                $ipAddress,
                $userAgent
            );

            DB::commit();

            return ApiResponse::success([
                'dashboard' => [
                    'key' => $invitation->dashboard->key,
                    'name' => $invitation->dashboard->name,
                ],
                'invitation_status' => $invitation->fresh()->getStatusInfo(),
            ], 'Acesso autorizado');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao validar convite: " . $e->getMessage());
            return ApiResponse::error('Erro ao processar convite', [$e->getMessage()]);
        }
    }

    /**
     * Atualiza um convite existente
     */
    public function updateInvitation(string $token, array $data): ApiResponse
    {
        DB::beginTransaction();
        try {
            $invitation = DashboardInvitation::where('token', $token)->first();

            if (!$invitation) {
                return ApiResponse::error('Convite não encontrado');
            }

            $updates = [];

            if (isset($data['name'])) {
                $updates['name'] = $data['name'];
            }

            if (isset($data['description'])) {
                $updates['description'] = $data['description'];
            }

            if (isset($data['active'])) {
                $updates['active'] = $data['active'];
            }

            if (isset($data['max_uses'])) {
                $updates['max_uses'] = $data['max_uses'];
            }

            if (!empty($data['expires_at'])) {
                $expiresAt = Carbon::parse($data['expires_at']);
                if ($expiresAt->isPast()) {
                    return ApiResponse::error('A data de expiração deve ser futura');
                }
                $updates['expires_at'] = $expiresAt;
            } elseif (!empty($data['expires_in_days'])) {
                $updates['expires_at'] = now()->addDays($data['expires_in_days']);
            }

            $invitation->update($updates);

            DB::commit();

            return ApiResponse::success([
                'invitation' => $invitation->fresh(),
                'status' => $invitation->getStatusInfo(),
            ], 'Convite atualizado com sucesso');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao atualizar convite: " . $e->getMessage());
            return ApiResponse::error('Erro ao atualizar convite', [$e->getMessage()]);
        }
    }

    /**
     * Revoga um convite (desativa permanentemente)
     */
    public function revokeInvitation(string $token): ApiResponse
    {
        DB::beginTransaction();
        try {
            $invitation = DashboardInvitation::where('token', $token)->first();

            if (!$invitation) {
                return ApiResponse::error('Convite não encontrado');
            }

            $invitation->revoke();

            DB::commit();

            return ApiResponse::success(null, 'Convite revogado com sucesso');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao revogar convite: " . $e->getMessage());
            return ApiResponse::error('Erro ao revogar convite', [$e->getMessage()]);
        }
    }

    /**
     * Deleta um convite permanentemente
     */
    public function deleteInvitation(string $token): ApiResponse
    {
        DB::beginTransaction();
        try {
            $invitation = DashboardInvitation::where('token', $token)->first();

            if (!$invitation) {
                return ApiResponse::error('Convite não encontrado');
            }

            $invitation->delete();

            DB::commit();

            return ApiResponse::success(null, 'Convite deletado com sucesso');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao deletar convite: " . $e->getMessage());
            return ApiResponse::error('Erro ao deletar convite', [$e->getMessage()]);
        }
    }

    /**
     * Obtém estatísticas de uso de um convite
     */
    public function getInvitationStats(string $token): ApiResponse
    {
        try {
            $invitation = DashboardInvitation::where('token', $token)
                ->with(['logs' => function ($query) {
                    $query->orderBy('accessed_at', 'desc');
                }])
                ->first();

            if (!$invitation) {
                return ApiResponse::error('Convite não encontrado');
            }

            $stats = [
                'total_accesses' => $invitation->uses_count,
                'max_uses' => $invitation->max_uses,
                'remaining_uses' => $invitation->getRemainingUses(),
                'last_access' => $invitation->logs->first()?->accessed_at,
                'unique_ips' => $invitation->logs->unique('ip_address')->count(),
                'unique_users' => $invitation->logs->whereNotNull('user_id')->unique('user_id')->count(),
                'recent_accesses' => $invitation->logs->take(10)->map(fn ($log) => [
                    'accessed_at' => $log->accessed_at,
                    'ip_address' => $log->ip_address,
                    'user_id' => $log->user_id,
                ]),
            ];

            return ApiResponse::success($stats, 'Estatísticas carregadas');

        } catch (\Exception $e) {
            Log::error("Erro ao buscar estatísticas: " . $e->getMessage());
            return ApiResponse::error('Erro ao buscar estatísticas', [$e->getMessage()]);
        }
    }

    /**
     * Remove convites expirados automaticamente
     */
    public function cleanupExpiredInvitations(): ApiResponse
    {
        try {
            $count = DashboardInvitation::expired()->delete();

            return ApiResponse::success(
                ['deleted_count' => $count],
                "Limpeza concluída: {$count} convite(s) expirado(s) removido(s)"
            );

        } catch (\Exception $e) {
            Log::error("Erro ao limpar convites expirados: " . $e->getMessage());
            return ApiResponse::error('Erro ao limpar convites', [$e->getMessage()]);
        }
    }
}