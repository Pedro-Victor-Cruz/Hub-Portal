<?php

namespace App\Exceptions\Services;

class ServiceNotFoundException extends \Exception
{
    public function __construct(string $message = "Serviço não encontrado", int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}