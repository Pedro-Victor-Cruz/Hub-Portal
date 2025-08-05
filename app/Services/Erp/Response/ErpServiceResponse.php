<?php

namespace App\Services\Erp\Response;

class ErpServiceResponse
{
    private bool $success;
    private mixed $data;
    private ?string $message;
    private array $metadata;

    public function __construct(
        bool $success,
        mixed $data = null,
        ?string $message = null,
        array $metadata = []
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
        $this->metadata = $metadata;
    }

    public function isSuccess(): bool { return $this->success; }
    public function getData(): mixed { return $this->data; }
    public function getMessage(): ?string { return $this->message; }
    public function getMetadata(): array { return $this->metadata; }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}