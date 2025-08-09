<?php

namespace App\Exceptions\Services;

use Exception;

class ServiceValidationException extends Exception
{
    public function __construct(string $message, array $errors = [])
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getValidationErrors(): array
    {
        return $this->errors ?? [];
    }
}