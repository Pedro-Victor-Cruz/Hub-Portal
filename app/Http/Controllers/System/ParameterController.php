<?php

namespace App\Http\Controllers\System;

use App\Facades\ActivityLog;
use App\Http\Controllers\Controller;
use App\Http\Requests\System\ParameterRequest;
use App\Http\Resources\ParameterResource;
use App\Models\Parameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ParameterController extends Controller
{

    public function index(): JsonResponse
    {
        $query = Parameter::accessibleByCurrentUser();

        $parameters = $query->orderByDesc('id')->get();

        return response()->json([
            'message' => 'Lista de parâmetros',
            'data' => ParameterResource::collection($parameters)
        ]);
    }

    public function show($id): JsonResponse
    {
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);

        // Log de visualização de parâmetro específico
        ActivityLog::logViewed(
            model: $parameter,
            description: "Visualização do parâmetro: {$parameter->name}"
        );

        return response()->json([
            'message' => 'Detalhes do parâmetro',
            'data' => $parameter
        ]);
    }

    public function store(ParameterRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $parameter = Parameter::create($data);

            // Log de criação de parâmetro
            ActivityLog::logCreated(
                model: $parameter,
                description: "Novo parâmetro criado: {$parameter->name} (Categoria: {$parameter->category})"
            );

            DB::commit();

            return response()->json([
                'message' => 'Parâmetro criado com sucesso',
                'data' => $parameter
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao criar parâmetro
            ActivityLog::logError(
                description: "Erro ao criar parâmetro: {$e->getMessage()}",
                module: 'parameter',
                context: [
                    'data' => $data,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao criar parâmetro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(ParameterRequest $request, $id): JsonResponse
    {
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);

        // Salva dados antigos para log
        $oldData = $parameter->getOriginal();
        $data = $request->validated();

        try {
            DB::beginTransaction();

            $parameter->update($data);

            // Log de atualização de parâmetro
            ActivityLog::logUpdated(
                model: $parameter,
                oldValues: $oldData,
                description: "Parâmetro atualizado: {$parameter->name}"
            );

            DB::commit();

            return response()->json([
                'message' => 'Parâmetro atualizado com sucesso',
                'data' => $parameter
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao atualizar parâmetro
            ActivityLog::logError(
                description: "Erro ao atualizar parâmetro ID {$id}: {$e->getMessage()}",
                module: 'parameter',
                context: [
                    'parameter_id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao atualizar parâmetro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $parameter = Parameter::accessibleByCurrentUser()->findOrFail($id);

        try {
            DB::beginTransaction();

            // Log ANTES de deletar (para ter os dados)
            ActivityLog::logDeleted(
                model: $parameter,
                description: "Parâmetro deletado: {$parameter->name} (Categoria: {$parameter->category})"
            );

            $parameter->delete();

            DB::commit();

            return response()->json([
                'message' => 'Parâmetro excluído com sucesso'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log de erro ao deletar parâmetro
            ActivityLog::logError(
                description: "Erro ao deletar parâmetro ID {$id}: {$e->getMessage()}",
                module: 'parameter',
                context: [
                    'parameter_id' => $id,
                    'error' => $e->getMessage()
                ]
            );

            return response()->json([
                'message' => 'Erro ao deletar parâmetro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function listCategories(): JsonResponse
    {
        $categories = Parameter::accessibleByCurrentUser()
            ->select('category')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->values();

        return response()->json([
            'message' => 'Lista de categorias de parâmetros',
            'data' => $categories
        ]);
    }
}