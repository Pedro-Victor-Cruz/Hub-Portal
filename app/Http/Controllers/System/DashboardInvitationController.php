<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para gerenciar convites de dashboard
 */
class DashboardInvitationController extends Controller
{
    private DashboardInvitationService $service;

    public function __construct(DashboardInvitationService $service)
    {
        $this->service = $service;
    }

    /**
     * Cria um novo convite para um dashboard
     * POST /api/dashboards/{key}/invitations
     */
    public function store(Request $request, string $dashboardKey): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'max_uses' => 'nullable|integer|min:1',
            'active' => 'nullable|boolean',
        ], [
            'expires_at.after' => 'A data de expiração deve ser futura',
            'expires_in_days.min' => 'O número de dias deve ser no mínimo 1',
            'expires_in_days.max' => 'O número de dias não pode exceder 365',
            'max_uses.min' => 'O número máximo de usos deve ser no mínimo 1',
        ]);

        return $this->service->createInvitation($dashboardKey, $validated)->toJson();
    }

    /**
     * Lista todos os convites de um dashboard
     * GET /api/dashboards/{key}/invitations
     */
    public function index(Request $request, string $dashboardKey): JsonResponse
    {
        $activeOnly = $request->boolean('active_only', false);
        return $this->service->listInvitations($dashboardKey, $activeOnly)->toJson();
    }

    /**
     * Obtém detalhes de um convite específico
     * GET /api/invitations/{token}
     */
    public function show(string $token): JsonResponse
    {
        return $this->service->getInvitation($token)->toJson();
    }

    /**
     * Valida um convite e registra acesso
     * POST /api/invitations/{token}/validate
     */
    public function validateToken(Request $request, string $token): JsonResponse
    {
        return $this->service->validateAndAccess(
            $token,
            $request->ip(),
            $request->userAgent()
        )->toJson();
    }

    /**
     * Atualiza um convite existente
     * PUT /api/invitations/{token}
     */
    public function update(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'expires_at' => 'nullable|date|after:now',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
            'max_uses' => 'nullable|integer|min:1',
            'active' => 'nullable|boolean',
        ]);

        return $this->service->updateInvitation($token, $validated)->toJson();
    }

    /**
     * Revoga um convite (desativa permanentemente)
     * POST /api/invitations/{token}/revoke
     */
    public function revoke(string $token): JsonResponse
    {
        return $this->service->revokeInvitation($token)->toJson();
    }

    /**
     * Deleta um convite permanentemente
     * DELETE /api/invitations/{token}
     */
    public function destroy(string $token): JsonResponse
    {
        return $this->service->deleteInvitation($token)->toJson();
    }

    /**
     * Obtém estatísticas de uso de um convite
     * GET /api/invitations/{token}/stats
     */
    public function stats(string $token): JsonResponse
    {
        return $this->service->getInvitationStats($token)->toJson();
    }

    /**
     * Limpa convites expirados (pode ser chamado por CRON)
     * POST /api/invitations/cleanup
     */
    public function cleanup(): JsonResponse
    {
        return $this->service->cleanupExpiredInvitations()->toJson();
    }
}