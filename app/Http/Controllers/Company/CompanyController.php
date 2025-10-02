<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\CompanyRequest;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

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

}
