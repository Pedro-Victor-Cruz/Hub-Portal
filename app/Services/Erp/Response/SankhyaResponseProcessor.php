<?php

namespace App\Services\Erp\Response;

use App\Contracts\Erp\ErpResponseProcessorInterface;

/**
 * Processador de respostas do Sankhya
 */
class SankhyaResponseProcessor implements ErpResponseProcessorInterface
{
    public function processResponse($response): ErpServiceResponse
    {
        if ($this->isSuccessful($response)) {
            return new ErpServiceResponse(
                true,
                $this->extractData($response),
                'Service executed successfully'
            );
        }

        return new ErpServiceResponse(
            false,
            null,
            $this->extractError($response) ?? 'Unknown error occurred'
        );
    }

    public function extractData($response): mixed
    {
        if (!$response->successful()) {
            return null;
        }

        $json = $response->json();

        // Estrutura típica de resposta do Sankhya
        return $json['responseBody'] ?? $json;
    }

    public function extractError($response): ?string
    {
        if ($response->successful()) {
            return null;
        }

        $json = $response->json();

        // Tenta extrair mensagem de erro da estrutura do Sankhya
        if (isset($json['status']) && $json['status'] === 'error') {
            return $json['message'] ?? $json['statusMessage'] ?? null;
        }

        if (isset($json['error'])) {
            return is_string($json['error']) ? $json['error'] : $json['error']['message'] ?? null;
        }

        return $response->body();
    }

    public function isSuccessful($response): bool
    {
        if (!$response->successful()) {
            return false;
        }

        $json = $response->json();

        // Verifica estrutura de sucesso específica do Sankhya
        if (isset($json['status'])) {
            return $json['status'] === 'OK' || $json['status'] === 'success';
        }

        return true;
    }
}