<?php

namespace App\Services\Erp\Core;

class ErpServiceResponse
{
    private bool $success;
    private mixed $data;
    private ?string $message;

    public function __construct(
        bool $success,
        mixed $data = null,
        ?string $message = null,
    ) {
        $this->success = $success;
        $this->data = $data;
        $this->message = $message;
    }

    public function isSuccess(): bool { return $this->success; }
    public function getData(): mixed { return $this->data; }
    public function getMessage(): ?string { return $this->message; }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
        ];
    }

    public static function error(string $message, mixed $data = null): self
    {
        return new self(false, $data, $message);
    }

    public static function success(string $message, mixed $data = null): self
    {
        return new self(true, $data, $message);
    }
}