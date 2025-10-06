<?php

namespace App\Http\Controllers\System;

use App\Enums\IntegrationType;
use App\Facades\ActivityLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\CreateIntegrationRequest;
use App\Http\Requests\Integration\UpdateIntegrationRequest;
use App\Models\Company;
use App\Models\Integration;
use App\Models\SystemLog;
use App\Services\Core\ApiResponse;
use App\Services\Core\Integration\IntegrationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntegrationsController extends Controller
{
    private IntegrationManager $integrationManager;

    public function __construct(IntegrationManager $integrationManager)
    {
        $this->integrationManager = $integrationManager;
    }

    /**
     * Lista todas as integrações disponíveis no sistema
     */
    public function availableIntegrations(): JsonResponse
    {
        try {
            $integrations = $this->integrationManager->getAvailableIntegrations();

            return response()->json([
                'message' => 'Integrações disponíveis carregadas com sucesso',
                'data' => $integrations,
                'total' => count($integrations)
            ]);

        } catch (\Exception $e) {
            // Log de erro ao carregar integrações disponíveis
            ActivityLog::logError(
                description: "Erro ao carregar lista de integrações disponíveis: {$e->getMessage()}",
                module: 'integration',
                context: ['error' => $e->getMessage()]
            );

            return response()->json([
                'message' => 'Erro ao carregar integrações disponíveis',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtém informações de uma integração específica
     */
    public function integrationInfo(string $integrationName): JsonResponse
    {
        try {
            $info = $this->integrationManager->getIntegrationInfo($integrationName);

            if (!$info) {
                return response()->json([
                    'message' => 'Integração não encontrada',
                ], 404);
            }

            return response()->json([
                'message' => 'Informações da integração carregadas com sucesso',
                'data' => $info
            ]);

        } catch (\Exception $e) {
            // Log de erro ao buscar informações
            ActivityLog::logError(
                description: "Erro ao carregar informações da integração '{$integrationName}': {$e->getMessage()}",
                module: 'integration',
                context: [
                    'integration_name' => $integrationName,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao carregar informações da integração',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtém a integração ativa de uma empresa por nome
     */
    public function getCompanyIntegration(Request $request, string $integrationName): JsonResponse
    {
        try {
            /** @var Company $company */
            $company = $request->get('company');

            $integrationType = IntegrationType::tryFrom($integrationName);

            if (!$integrationType) {
                return response()->json([
                    'message' => 'Tipo de integração inválido'
                ], 400);
            }

            $integration = $company->getActiveIntegration($integrationType);

            if (!$integration) {
                $response = ApiResponse::success(null, 'Nenhuma integração ativa encontrada para esta empresa');
            } else {
                $response = ApiResponse::success($integration->toArray(), 'Integração carregada com sucesso');
            }

            return $response->toJson();
        } catch (\Exception $e) {
            // Log de erro ao buscar integração da empresa
            ActivityLog::logError(
                description: "Erro ao carregar integração '{$integrationName}' da empresa: {$e->getMessage()}",
                module: 'integration',
                context: [
                    'company_id' => $company->id ?? null,
                    'integration_name' => $integrationName,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao carregar integração da empresa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cria uma nova integração para uma empresa
     */
    public function create(CreateIntegrationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $company = Company::findOrFail($data['company_id']);

            DB::beginTransaction();

            $response = $this->integrationManager->createIntegration(
                $company,
                $data['integration_name'],
                $data['configuration'],
                $data['active'] ?? true
            );

            if ($response->isSuccess()) {
                $integration = $response->getData();

                // Log de criação de integração
                ActivityLog::log(
                    action: 'integration_created',
                    description: "Nova integração criada: {$data['integration_name']} para empresa '{$company->name}'",
                    level: SystemLog::LEVEL_INFO,
                    module: 'integration',
                    model: $integration instanceof Integration ? $integration : null,
                    data: [
                        'metadata' => [
                            'company_id' => $company->id,
                            'company_name' => $company->name,
                            'integration_name' => $data['integration_name'],
                            'active' => $data['active'] ?? true,
                            'has_configuration' => !empty($data['configuration'])
                        ]
                    ]
                );

                DB::commit();
            } else {
                DB::rollBack();
            }

            return $response->toJson();
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao criar integração
            ActivityLog::logError(
                description: "Erro ao criar integração '{$data['integration_name']}': {$e->getMessage()}",
                module: 'integration',
                context: [
                    'company_id' => $data['company_id'] ?? null,
                    'integration_name' => $data['integration_name'] ?? null,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao criar integração',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza uma integração existente
     */
    public function update(UpdateIntegrationRequest $request, int $integrationId): JsonResponse
    {
        try {
            $integration = Integration::findOrFail($integrationId);
            $data = $request->validated();

            // Salva dados antigos para log
            $oldActive = $integration->active;
            $oldConfiguration = $integration->configuration;

            DB::beginTransaction();

            $response = $this->integrationManager->updateIntegration(
                $integration,
                $data['configuration'],
                $data['active'] ?? null
            );

            if ($response->isSuccess()) {
                // Prepara informações das mudanças
                $changes = [];

                if (isset($data['active']) && $oldActive !== $data['active']) {
                    $changes['status'] = $data['active'] ? 'ativada' : 'desativada';
                }

                if (isset($data['configuration']) && $oldConfiguration !== $data['configuration']) {
                    $changes['configuration'] = 'atualizada';
                }

                $changeDescription = !empty($changes)
                    ? implode(' e ', $changes)
                    : 'atualizada';

                // Log de atualização de integração
                ActivityLog::log(
                    action: 'integration_updated',
                    description: "Integração {$integration->integration_name} {$changeDescription} (ID: {$integrationId})",
                    level: SystemLog::LEVEL_INFO,
                    module: 'integration',
                    model: $integration,
                    data: [
                        'old_values' => [
                            'active' => $oldActive,
                        ],
                        'new_values' => [
                            'active' => $integration->active,
                        ],
                        'metadata' => [
                            'company_id' => $integration->company_id,
                            'integration_name' => $integration->integration_name,
                            'configuration_changed' => isset($changes['configuration']),
                            'status_changed' => isset($changes['status'])
                        ]
                    ]
                );

                DB::commit();
            } else {
                DB::rollBack();
            }

            return $response->toJson();
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao atualizar integração
            ActivityLog::logError(
                description: "Erro ao atualizar integração ID {$integrationId}: {$e->getMessage()}",
                module: 'integration',
                context: [
                    'integration_id' => $integrationId,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao atualizar integração',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove uma integração
     */
    public function destroy(int $integrationId): JsonResponse
    {
        try {
            $integration = Integration::findOrFail($integrationId);

            // Captura dados antes de deletar
            $integrationName = $integration->integration_name;
            $companyId = $integration->company_id;
            $companyName = $integration->company->name ?? "ID {$companyId}";

            DB::beginTransaction();

            $response = $this->integrationManager->deleteIntegration($integration);

            if ($response->isSuccess()) {
                // Log de exclusão de integração
                ActivityLog::log(
                    action: 'integration_deleted',
                    description: "Integração {$integrationName} removida da empresa '{$companyName}'",
                    level: SystemLog::LEVEL_WARNING,
                    module: 'integration',
                    data: [
                        'metadata' => [
                            'integration_id' => $integrationId,
                            'integration_name' => $integrationName,
                            'company_id' => $companyId,
                            'company_name' => $companyName
                        ]
                    ]
                );

                DB::commit();

                return response()->json([
                    'message' => $response->getMessage()
                ]);
            }

            DB::rollBack();

            return response()->json([
                'message' => $response->getMessage(),
                'errors' => $response->getErrors()
            ], 400);

        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao remover integração
            ActivityLog::logError(
                description: "Erro ao remover integração ID {$integrationId}: {$e->getMessage()}",
                module: 'integration',
                context: [
                    'integration_id' => $integrationId,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao remover integração',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Testa a conexão de uma integração
     */
    public function testConnection(int $integrationId): JsonResponse
    {
        try {
            $integration = Integration::findOrFail($integrationId);

            $response = $this->integrationManager->testConnection($integration);

            // Log do teste de conexão
            $level = $response->isSuccess() ? SystemLog::LEVEL_INFO : SystemLog::LEVEL_WARNING;

            ActivityLog::log(
                action: 'integration_connection_tested',
                description: "Teste de conexão da integração {$integration->integration_name}: " .
                ($response->isSuccess() ? "SUCESSO" : "FALHOU"),
                level: $level,
                module: 'integration',
                model: $integration,
                data: [
                    'metadata' => [
                        'integration_name' => $integration->integration_name,
                        'company_id' => $integration->company_id,
                        'test_result' => $response->isSuccess() ? 'success' : 'failed',
                        'message' => $response->getMessage(),
                        'response_data' => $response->getData()
                    ]
                ]
            );

            return response()->json([
                'message' => $response->getMessage(),
                'data' => $response->getData(),
                'success' => $response->isSuccess(),
                'metadata' => $response->getMetadata()
            ]);

        } catch (\Exception $e) {
            // Log de erro no teste de conexão
            ActivityLog::logError(
                description: "Erro ao testar conexão da integração ID {$integrationId}: {$e->getMessage()}",
                module: 'integration',
                context: [
                    'integration_id' => $integrationId,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao testar conexão',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Obtém uma integração específica
     */
    public function show(int $integrationId): JsonResponse
    {
        try {
            /** @var Integration $integration */
            $integration = Integration::with('company')->findOrFail($integrationId);
            $driver = $this->integrationManager->getDriver($integration);

            $data = array_merge(
                $integration->toArray(),
                ['info' => $driver->getInfo()]
            );

            return response()->json([
                'message' => 'Integração carregada com sucesso',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            // Log de erro ao carregar integração
            ActivityLog::logError(
                description: "Erro ao carregar integração ID {$integrationId}: {$e->getMessage()}",
                module: 'integration',
                context: [
                    'integration_id' => $integrationId,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao carregar integração',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}