<?php

namespace App\Services\Parameter;

use App\Enums\ParameterType;

class ServiceParameterManager {
    /** @var ServiceParameter[] */
    private array $parameters = [];

    /**
     * Adiciona um parâmetro
     */
    public function add(ServiceParameter $parameter): self
    {
        $this->parameters[$parameter->name] = $parameter;
        return $this;
    }

    /**
     * Adiciona múltiplos parâmetros
     * @param ServiceParameter[] $parameters
     */
    public function addMany(array $parameters): self
    {
        foreach ($parameters as $parameter) {
            $this->add($parameter);
        }
        return $this;
    }

    /**
     * Obtém todos os parâmetros
     * @return ServiceParameter[]
     */
    public function getAll(): array
    {
        // Ordena por group e order
        $parameters = $this->parameters;
        uasort($parameters, function (ServiceParameter $a, ServiceParameter $b) {
            if ($a->group !== $b->group) {
                return ($a->group ?: 'zzz') <=> ($b->group ?: 'zzz');
            }
            return $a->order <=> $b->order;
        });

        return $parameters;
    }

    /**
     * Obtém parâmetros obrigatórios
     */
    public function getRequired(): array
    {
        return array_filter($this->parameters, fn($p) => $p->required);
    }

    /**
     * Obtém parâmetros opcionais
     */
    public function getOptional(): array
    {
        return array_filter($this->parameters, fn($p) => !$p->required);
    }

    /**
     * Obtém parâmetro por nome
     */
    public function get(string $name): ?ServiceParameter
    {
        return $this->parameters[$name] ?? null;
    }

    /**
     * Verifica se parâmetro existe
     */
    public function has(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Obtém apenas os nomes dos parâmetros obrigatórios
     */
    public function getRequiredNames(): array
    {
        return array_keys($this->getRequired());
    }

    /**
     * Converte todos os parâmetros para array (para API)
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->getAll() as $parameter) {
            $result[] = $parameter->toArray();
        }
        return $result;
    }

    /**
     * Agrupa parâmetros por grupo
     */
    public function getGrouped(): array
    {
        $grouped = [];
        foreach ($this->getAll() as $parameter) {
            $group = $parameter->group ?: 'Geral';
            $grouped[$group][] = $parameter;
        }
        return $grouped;
    }

    /**
     * Valida os parâmetros fornecidos
     */
    public function validate(array $params): array
    {
        $errors = [];
        $sanitized = [];

        foreach ($this->getAll() as $parameter) {
            $value = $params[$parameter->name] ?? $parameter->defaultValue;

            // Verifica parâmetros obrigatórios
            if ($parameter->required && $this->isEmpty($value)) {
                $errors[] = "Parâmetro obrigatório '{$parameter->name}' não foi fornecido";
                continue;
            }

            // Se não tem valor e não é obrigatório, usa o padrão
            if ($this->isEmpty($value) && !$parameter->required) {
                if ($parameter->defaultValue !== null) {
                    $sanitized[$parameter->name] = $parameter->defaultValue;
                }
                continue;
            }

            // Valida e sanitiza o valor
            $validationResult = $this->validateParameter($parameter, $value);

            if (!empty($validationResult['errors'])) {
                $errors = array_merge($errors, $validationResult['errors']);
            } else {
                $sanitized[$parameter->name] = $validationResult['value'];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized' => $sanitized
        ];
    }

    /**
     * Valida um parâmetro específico
     */
    private function validateParameter(ServiceParameter $parameter, mixed $value): array
    {
        $errors = [];
        $sanitizedValue = $value;
        $parameterName = $parameter->label ?? $parameter->name;

        try {
            switch ($parameter->type) {
                case ParameterType::TEXT:
                case ParameterType::EMAIL:
                case ParameterType::URL:
                    $sanitizedValue = $this->validateText($parameter, $value);
                    break;

                case ParameterType::NUMBER:
                    $sanitizedValue = $this->validateNumber($parameter, $value);
                    break;

                case ParameterType::BOOLEAN:
                    $sanitizedValue = $this->validateBoolean($parameter, $value);
                    break;

                case ParameterType::SELECT:
                    $sanitizedValue = $this->validateSelect($parameter, $value);
                    break;

                case ParameterType::MULTISELECT:
                    $sanitizedValue = $this->validateMultiselect($parameter, $value);
                    break;

                case ParameterType::DATE:
                case ParameterType::DATETIME:
                    $sanitizedValue = $this->validateDate($parameter, $value);
                    break;

                case ParameterType::ARRAY:
                    $sanitizedValue = $this->validateArray($parameter, $value);
                    break;

                case ParameterType::OBJECT:
                    $sanitizedValue = $this->validateObject($parameter, $value);
                    break;
            }
        } catch (\Exception $e) {
            $errors[] = "Parâmetro '{$parameterName}': {$e->getMessage()}";
        }

        return [
            'errors' => $errors,
            'value' => $sanitizedValue
        ];
    }

    private function validateText(ServiceParameter $parameter, mixed $value): string
    {
        $value = (string) $value;

        if (isset($parameter->validation['min_length']) && strlen($value) < $parameter->validation['min_length']) {
            throw new \InvalidArgumentException("deve ter pelo menos {$parameter->validation['min_length']} caracteres");
        }

        if (isset($parameter->validation['max_length']) && strlen($value) > $parameter->validation['max_length']) {
            throw new \InvalidArgumentException("deve ter no máximo {$parameter->validation['max_length']} caracteres");
        }

        if (isset($parameter->validation['pattern']) && !preg_match($parameter->validation['pattern'], $value)) {
            throw new \InvalidArgumentException("formato inválido");
        }

        if ($parameter->type === ParameterType::EMAIL && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("email inválido");
        }

        if ($parameter->type === ParameterType::URL && !filter_var($value, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException("URL inválida");
        }

        return $value;
    }

    private function validateNumber(ServiceParameter $parameter, mixed $value): int|float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("deve ser um número");
        }

        $value = is_int($value) ? (int) $value : (float) $value;

        if (isset($parameter->validation['min']) && $value < $parameter->validation['min']) {
            throw new \InvalidArgumentException("deve ser maior ou igual a {$parameter->validation['min']}");
        }

        if (isset($parameter->validation['max']) && $value > $parameter->validation['max']) {
            throw new \InvalidArgumentException("deve ser menor ou igual a {$parameter->validation['max']}");
        }

        return $value;
    }

    private function validateBoolean(ServiceParameter $parameter, mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'sim', 'on'])) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'não', 'off'])) {
                return false;
            }
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        throw new \InvalidArgumentException("deve ser um valor booleano");
    }

    private function validateSelect(ServiceParameter $parameter, mixed $value): mixed
    {
        if (empty($parameter->options)) {
            return $value;
        }

        $options = $parameter->options;

        // Se options for associativo, as chaves são os valores válidos
        $validValues = array_keys($options) !== range(0, count($options) - 1)
            ? array_keys($options)
            : $options;

        if (!in_array($value, $validValues, true)) {
            throw new \InvalidArgumentException(
                "Valor inválido para '{$parameter->name}'. Deve ser um dos valores: " . implode(', ', $validValues)
            );
        }

        return $value;
    }

    private function validateMultiselect(ServiceParameter $parameter, mixed $value): array
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("deve ser um array");
        }

        if (!empty($parameter->options)) {
            $validValues = array_keys($parameter->options);
            foreach ($value as $item) {
                if (!in_array($item, $validValues, true)) {
                    throw new \InvalidArgumentException("contém valor inválido: {$item}");
                }
            }
        }

        return array_values($value); // Reindexar array
    }

    private function validateDate(ServiceParameter $parameter, mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("deve ser uma string");
        }

        $format = $parameter->validation['date_format'] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);

        if (!$date || $date->format($format) !== $value) {
            throw new \InvalidArgumentException("formato de data inválido, esperado: {$format}");
        }

        return $value;
    }

    private function validateArray(ServiceParameter $parameter, mixed $value): array
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("deve ser um array");
        }

        // Validação de tipo dos itens do array se especificado
        if ($parameter->arrayItemType) {
            foreach ($value as $index => $item) {
                // Aqui poderia implementar validação recursiva dos itens
            }
        }

        return $value;
    }

    private function validateObject(ServiceParameter $parameter, mixed $value): array
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("deve ser um objeto/array associativo. Valor fornecido: " . gettype($value));
        }

        // Validação das propriedades se especificadas
        if (isset($parameter->validation['properties'])) {
            foreach ($parameter->validation['properties'] as $prop => $rules) {
                // Implementar validação de propriedades do objeto
            }
        }

        return $value;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }
}