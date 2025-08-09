<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyErpSettingRequest;
use App\Models\Company;
use App\Models\CompanyErpSetting;
use App\Services\Erp\Core\ErpManager;
use App\Services\Erp\Drivers\Sankhya\Services\SankhyaDbExplorerService;
use Illuminate\Http\JsonResponse;

class ErpController extends Controller
{

    private ErpManager $erpManager;

    public function __construct(ErpManager $erpManager)
    {
        $this->erpManager = $erpManager;
    }

    public function testConnection(string $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);

        try {
            $connection = $this->erpManager->testConnection($company);
            return response()->json([
                'data' => $connection
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function showErpSettings($idCompany): JsonResponse
    {
        $company = CompanyErpSetting::where('company_id', $idCompany)->first();

        if (!$company) {
            return response()->json([
                'message' => 'Configuração de ERP não encontrada para esta empresa.',
            ], 404);
        }

        return response()->json([
            'message' => 'Configuração de ERP encontrada',
            'data' => $company
        ]);
    }

    public function createErpSettings(CompanyErpSettingRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Verifica se já existe uma configuração de ERP para a empresa
        $existingSetting = CompanyErpSetting::where('company_id', $data['company_id'])
            ->first();

        if ($existingSetting) {
            return response()->json([
                'message' => 'Já existe uma configuração de ERP para esta empresa.',
                'data' => $existingSetting
            ], 409);
        }

        $erpSetting = CompanyErpSetting::create($data);

        return response()->json([
            'message' => 'Configuração de ERP criada com sucesso',
            'data' => $erpSetting
        ], 201);
    }

    public function updateErpSettings(CompanyErpSettingRequest $request, $id): JsonResponse
    {
        $erpSetting = CompanyErpSetting::findOrFail($id);
        $data = $request->validated();
        $erpSetting->update($data);

        return response()->json([
            'message' => 'Configuração de ERP atualizada com sucesso',
            'data' => $erpSetting
        ]);
    }

    public function destroyErpSettings($id): JsonResponse
    {
        $erpSetting = CompanyErpSetting::findOrFail($id);
        $erpSetting->delete();

        return response()->json([
            'message' => 'Configuração de ERP deletada com sucesso'
        ]);
    }
}