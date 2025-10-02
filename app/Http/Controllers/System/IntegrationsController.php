<?php

namespace App\Http\Controllers\System;

use App\Enums\IntegrationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\CreateIntegrationRequest;
use App\Http\Requests\Integration\UpdateIntegrationRequest;
use App\Models\Company;
use App\Models\Integration;
use App\Services\Core\ApiResponse;
use App\Services\Core\Integration\IntegrationManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

            $response = $this->integrationManager->createIntegration(
                $company,
                $data['integration_name'],
                $data['configuration'],
                $data['active'] ?? true
            );

           return $response->toJson();
        } catch (\Exception $e) {
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

            $response = $this->integrationManager->updateIntegration(
                $integration,
                $data['configuration'],
                $data['active'] ?? null
            );

            return $response->toJson();
        } catch (\Exception $e) {
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

            $response = $this->integrationManager->deleteIntegration($integration);

            if ($response->isSuccess()) {
                return response()->json([
                    'message' => $response->getMessage()
                ]);
            }

            return response()->json([
                'message' => $response->getMessage(),
                'errors' => $response->getErrors()
            ], 400);

        } catch (\Exception $e) {
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

            return response()->json([
                'message' => $response->getMessage(),
                'data' => $response->getData(),
                'success' => $response->isSuccess(),
                'metadata' => $response->getMetadata()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao testar conexão',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Sincroniza dados de uma integração
     */
    public function syncData(int $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::findOrFail($integrationId);
            $options = $request->get('options', []);

            $response = $this->integrationManager->syncData($integration, $options);

            return response()->json([
                'message' => $response->getMessage(),
                'data' => $response->getData(),
                'success' => $response->isSuccess(),
                'metadata' => $response->getMetadata()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro na sincronização',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Ativa/desativa uma integração
     */
    public function toggleStatus(int $integrationId, Request $request): JsonResponse
    {
        try {
            $integration = Integration::findOrFail($integrationId);
            $active = $request->boolean('active');

            $response = $this->integrationManager->toggleIntegration($integration, $active);

            if ($response->isSuccess()) {
                return response()->json([
                    'message' => $response->getMessage(),
                    'data' => $response->getData()
                ]);
            }

            return response()->json([
                'message' => $response->getMessage(),
                'errors' => $response->getErrors()
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao alterar status da integração',
                'error' => $e->getMessage()
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
            return response()->json([
                'message' => 'Erro ao carregar integração',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida configuração de uma integração sem salvar
     */
    public function validateConfiguration(Request $request): JsonResponse
    {
        try {
            $integrationName = $request->get('integration_name');
            $configuration = $request->get('configuration', []);

            if (!$integrationName) {
                return response()->json([
                    'message' => 'Nome da integração é obrigatório',
                    'valid' => false
                ], 400);
            }

            // Cria uma integração temporária para validação
            $tempIntegration = new Integration([
                'integration_name' => $integrationName,
                'configuration' => $configuration,
                'active' => false,
            ]);

            $driver = $this->integrationManager->getDriver($tempIntegration);
            $validation = $driver->validateConfiguration($configuration);

            return response()->json([
                'message' => $validation['valid']
                    ? 'Configuração válida'
                    : 'Configuração inválida',
                'valid' => $validation['valid'],
                'errors' => $validation['errors'] ?? [],
                'sanitized' => $validation['sanitized'] ?? null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro na validação',
                'error' => $e->getMessage(),
                'valid' => false
            ], 500);
        }
    }
}