<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponder
{
    /**
     * Retorna uma resposta padronizada para dados paginados
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param array $additionalData
     * @return JsonResponse
     */
    protected function respondWithPagination(
        LengthAwarePaginator $paginator,
        string $message = '',
        array $additionalData = []
    ): JsonResponse {
        $response = [
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];

        if (!empty($additionalData)) {
            $response = array_merge($response, $additionalData);
        }

        return response()->json($response);
    }

    /**
     * Obtém o número de itens por página baseado no header X-Paginate
     *
     * @param int $default
     * @param int $max
     * @return int
     */
    protected function getPerPage(int $default = 15, int $max = 100): int
    {
        $perPage = request()->header('X-Paginate', $default);

        // Garante que o valor está dentro dos limites
        return min(max((int)$perPage, 1), $max);
    }
}