<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Exception;

abstract class BaseService
{
    public $serviceName;

    // Define as regras de validação para o requestBody
    abstract protected function rules(): array;

    // Executa o serviço
    abstract public function execute(array $requestBody): array;

    // Valida o requestBody com base nas regras definidas
    protected function validateRequestBody(array $requestBody): void
    {
        $validator = Validator::make($requestBody, $this->rules(), $this->validationMessages());

        if ($validator->fails()) {
            $errors = $this->formatValidationErrors($validator->errors());
            throw new Exception($errors);
        }
    }

    // Mensagens de validação personalizadas em português
    protected function validationMessages(): array
    {
        return [
            'required' => 'A propriedade :attribute não foi informada.',
            'string' => 'A propriedade :attribute deve ser uma string.',
            'array' => 'A propriedade :attribute deve ser um array.',
            'nullable' => 'A propriedade :attribute pode ser nula.',
        ];
    }

    // Formata os erros de validação em uma única string
    protected function formatValidationErrors($errors): string
    {
        $formattedErrors = [];

        foreach ($errors->all() as $error) {
            $formattedErrors[] = $error;
        }

        return implode(' ', $formattedErrors);
    }

    // Formata a resposta padrão
    protected function formatResponse(string $status, string $statusMessage, array $responseBody = []): array
    {
        return [
            'serviceName' => $this->serviceName,
            'status' => $status,
            'statusMessage' => $statusMessage,
            'responseBody' => $responseBody,
        ];
    }
}