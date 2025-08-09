<?php

namespace App\Services\Core;

use Illuminate\Http\JsonResponse;

/**
 * Classe para padronizar as respostas dos serviços
 */
class ApiResponse
{
    private bool $success;
    private mixed $data;
    private ?string $message;
    private array $errors;
    private array $metadata;

    public function __construct(
        bool $success = true,
        mixed $data = null,
        ?string $message = null,
        array $errors = [],
        array $metadata = []
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
        $this->errors = $errors;
        $this->metadata = $metadata;
    }

    public static function success(mixed $data = null, ?string $message = null, array $metadata = []): self
    {
        return new self(true, $data, $message, [], $metadata);
    }

    public static function error(string $message, array $errors = [], array $metadata = []): self
    {
        return new self(false, null, $message, $errors, $metadata);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'metadata' => $this->metadata,
            'data' => $this->data,
            'errors' => $this->errors,
        ];
    }

    public function toJson(): JsonResponse
    {
        return response()->json($this->toArray(), $this->success ? 200 : 400);
    }
}