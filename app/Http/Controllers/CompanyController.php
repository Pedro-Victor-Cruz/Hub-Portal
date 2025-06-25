<?php

namespace App\Http\Controllers;

use App\Http\Requests\Company\CompanyErpSettingRequest;
use App\Http\Requests\Company\CompanyRequest;
use App\Models\Company;
use App\Models\CompanyErpSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Lista de empresas',
            'data' => Company::with('responsibleUser')->get()
        ]);
    }

    public function show($id): JsonResponse
    {
        $company = Company::with('responsibleUser')->findOrFail($id);
        return response()->json([
            'message' => 'Detalhes da empresa',
            'data' => $company
        ]);
    }

    public function store(CompanyRequest $request): JsonResponse
    {
        $data = $request->validated();
        $company = Company::create($data);

        return response()->json([
            'message' => 'Empresa criada com sucesso',
            'data' => $company
        ], 201);
    }

    public function update(CompanyRequest $request, $id): JsonResponse
    {
        $company = Company::findOrFail($id);
        $data = $request->validated();
        $company->update($data);

        return response()->json([
            'message' => 'Empresa atualizada com sucesso',
            'data' => $company
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $company = Company::findOrFail($id);
        $company->delete();

        return response()->json([
            'message' => 'Empresa deletada com sucesso'
        ]);
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
