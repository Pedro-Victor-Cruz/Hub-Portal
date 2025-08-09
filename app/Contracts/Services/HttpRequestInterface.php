<?php

namespace App\Contracts\Services;

use App\Services\Core\ApiResponse;

/**
 * Interface para padronizar requisições HTTP
 */
interface HttpRequestInterface
{
    /**
     * Configura o método HTTP
     */
    public function setMethod(string $method): static;

    /**
     * Configura o endpoint da requisição
     */
    public function setEndpoint(string $endpoint): static;

    /**
     * Adiciona ou substitui headers
     */
    public function setHeaders(array $headers): static;

    /**
     * Adiciona um header específico
     */
    public function addHeader(string $key, string $value): static;

    /**
     * Configura parâmetros de query string
     */
    public function setParams(array $params): static;

    /**
     * Configura o corpo da requisição
     */
    public function setBody(array|string $body): static;

    /**
     * Configura timeout da requisição
     */
    public function setTimeout(int $seconds): static;

    /**
     * Configura upload de arquivo
     */
    public function setFileUpload(array $files): static;

    /**
     * Executa a requisição HTTP
     */
    public function execute(): ApiResponse;

    /**
     * Reseta a requisição para reutilização
     */
    public function reset(): static;
}