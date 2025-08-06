<?php

namespace App\Services\Erp\Drivers\Sankhya\Request;

use App\Contracts\Erp\ErpRequestBuilderInterface;

/**
 * Builder para requisições do Sankhya
 */
class SankhyaRequestBuilder implements ErpRequestBuilderInterface
{
    private string $baseUrl;
    private string $serviceName = '';
    private string $method = 'POST';
    private array $headers = [];
    private array $body = [];
    private int $timeout = 30;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->reset();
    }

    public function setService(string $serviceName): self
    {
        $this->serviceName = $serviceName;
        return $this;
    }

    public function setMethod(string $method): self
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function addHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function addHeaders(array $headers): self
    {
        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }
        return $this;
    }

    public function setBody(array $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function addBodyParam(string $key, mixed $value): self
    {
        $this->body[$key] = $value;
        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function build(): array
    {
        return [
            'url' => $this->buildUrl(),
            'method' => $this->method,
            'headers' => $this->buildHeaders(),
            'body' => $this->buildBody(),
            'timeout' => $this->timeout,
        ];
    }

    public function reset(): self
    {
        $this->serviceName = '';
        $this->method = 'POST';
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
        $this->body = [];
        $this->timeout = 30;
        return $this;
    }

    private function buildUrl(): string
    {
        return $this->baseUrl . '/mge/service.sbr';
    }

    private function buildHeaders(): array
    {
        return $this->headers;
    }

    private function buildBody(): array
    {
        if (empty($this->serviceName)) {
            return $this->body;
        }

        // Estrutura padrão do Sankhya
        return [
            'serviceName' => $this->serviceName,
            'requestBody' => $this->body,
        ];
    }
}