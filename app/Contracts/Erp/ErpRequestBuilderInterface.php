<?php

namespace App\Contracts\Erp;

interface ErpRequestBuilderInterface
{
    public function setService(string $serviceName): self;
    public function setMethod(string $method): self;
    public function setHeaders(array $headers): self;
    public function addHeader(string $key, string $value): self;
    public function addHeaders(array $headers): self;
    public function setBody(array $body): self;
    public function addBodyParam(string $key, mixed $value): self;
    public function setTimeout(int $timeout): self;
    public function build(): array;
    public function reset(): self;
}