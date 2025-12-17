<?php

namespace App\Models;

use App\Enums\FilterType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Filter
 *
 * Representa um filtro dinâmico para consultas configuráveis.
 * Permite definir variáveis que serão substituídas nas configurações dos serviços.
 *
 * @property int $id
 * @property int $dynamic_query_id ID da consulta dinâmica associada
 * @property int $dashboard_id ID do dashboard associado
 * @property string $name Nome amigável do filtro
 * @property string|null $description Descrição do filtro
 * @property string $var_name Nome da variável (usado nos placeholders)
 * @property FilterType $type Tipo do filtro (text, number, boolean, etc.)
 * @property mixed $default_value Valor padrão do filtro
 * @property bool $required Se o filtro é obrigatório
 * @property int $order Ordem de exibição do filtro
 * @property array|null $validation_rules Regras de validação específicas
 * @property bool $visible Se o filtro é visível na interface
 * @property bool $active Se o filtro está ativo
 * @property array|null $options Opções para tipos select/multiselect
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property DynamicQuery $dynamicQuery Consulta dinâmica associada
 */
class Filter extends Model
{
    use HasFactory;

    protected $table = 'filters';

    protected $fillable = [
        'dynamic_query_id',
        'dashboard_id',
        'name',
        'description',
        'var_name',
        'type',
        'default_value',
        'required',
        'order',
        'validation_rules',
        'visible',
        'active',
        'options',
    ];

    protected $casts = [
        'type' => FilterType::class,
        'default_value' => 'json',
        'validation_rules' => 'array',
        'options' => 'array',
        'required' => 'boolean',
        'visible' => 'boolean',
        'active' => 'boolean',
        'order' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Relacionamento: Filtro pertence a uma consulta dinâmica
     */
    public function dynamicQuery(): BelongsTo
    {
        return $this->belongsTo(DynamicQuery::class);
    }

    /**
     * Relacionamento: Filtro pertence a um dashboard
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Scope para filtros ativos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para filtros visíveis
     */
    public function scopeVisible($query)
    {
        return $query->where('visible', true);
    }

    /**
     * Scope para filtros obrigatórios
     */
    public function scopeRequired($query)
    {
        return $query->where('required', true);
    }

    /**
     * Scope para ordenação por order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Obtém o nome da variável com os dois pontos (placeholder completo)
     */
    public function getPlaceholderAttribute(): string
    {
        return ':' . $this->var_name;
    }

    /**
     * Valida um valor contra as regras do filtro
     */
    public function validateValue(mixed $value): array
    {
        $errors = [];

        // Verifica se é obrigatório
        if ($this->required && $this->isEmpty($value)) {
            $errors[] = "O filtro '{$this->name}' é obrigatório";
            return ['valid' => false, 'errors' => $errors];
        }

        // Se não tem valor e não é obrigatório, usar padrão
        if ($this->isEmpty($value)) {
            $value = $this->default_value;
        }

        // Se ainda não tem valor, é válido (opcional)
        if ($this->isEmpty($value)) {
            return ['valid' => true, 'value' => null];
        }

        // Validação por tipo
        try {
            $validatedValue = $this->validateByType($value);

            // Validações específicas das regras
            $this->applyValidationRules($validatedValue);

            return ['valid' => true, 'value' => $validatedValue];
        } catch (\Exception $e) {
            $errors[] = "Filtro '{$this->name}': {$e->getMessage()}";
            return ['valid' => false, 'errors' => $errors];
        }
    }

    /**
     * Valida valor baseado no tipo do filtro
     */
    private function validateByType(mixed $value): mixed
    {
        return match($this->type) {
            FilterType::TEXT => $this->validateText($value),
            FilterType::NUMBER => $this->validateNumber($value),
            FilterType::BOOLEAN => $this->validateBoolean($value),
            FilterType::DATE => $this->validateDate($value),
            FilterType::SELECT => $this->validateSelect($value),
            FilterType::MULTISELECT => $this->validateMultiselect($value),
            FilterType::ARRAY => $this->validateArray($value),
            default => $value,
        };
    }

    /**
     * Validações específicas por tipo
     */
    private function validateText(mixed $value): string
    {
        return (string) $value;
    }

    private function validateNumber(mixed $value): int|float
    {
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("deve ser um número");
        }
        return is_int($value) ? (int) $value : (float) $value;
    }

    private function validateBoolean(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_string($value)) {
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'sim', 'on'])) return true;
            if (in_array($lower, ['false', '0', 'no', 'não', 'off'])) return false;
        }
        if (is_numeric($value)) return (bool) $value;
        throw new \InvalidArgumentException("deve ser um valor booleano");
    }

    private function validateDate(mixed $value): string
    {
        if (!is_string($value)) {
            throw new \InvalidArgumentException("deve ser uma string");
        }

        $format = $this->validation_rules['date_format'] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);

        if (!$date || $date->format($format) !== $value) {
            throw new \InvalidArgumentException("formato de data inválido, esperado: {$format}");
        }

        return $value;
    }

    private function validateSelect(mixed $value): mixed
    {
        if (empty($this->options)) return $value;

        $validValues = is_array($this->options) ? array_keys($this->options) : $this->options;
        if (!in_array($value, $validValues, true)) {
            throw new \InvalidArgumentException("deve ser um dos valores: " . implode(', ', $validValues));
        }

        return $value;
    }

    private function validateMultiselect(mixed $value): array
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("deve ser um array");
        }

        if (!empty($this->options)) {
            $validValues = is_array($this->options) ? array_keys($this->options) : $this->options;
            foreach ($value as $item) {
                if (!in_array($item, $validValues, true)) {
                    throw new \InvalidArgumentException("contém valor inválido: {$item}");
                }
            }
        }

        return array_values($value);
    }

    private function validateArray(mixed $value): array
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException("deve ser um array");
        }
        return $value;
    }

    /**
     * Aplica regras de validação específicas
     */
    private function applyValidationRules(mixed $value): void
    {
        if (empty($this->validation_rules)) return;

        $rules = $this->validation_rules;

        // Validações numéricas
        if (is_numeric($value)) {
            if (isset($rules['min']) && $value < $rules['min']) {
                throw new \InvalidArgumentException("deve ser maior ou igual a {$rules['min']}");
            }
            if (isset($rules['max']) && $value > $rules['max']) {
                throw new \InvalidArgumentException("deve ser menor ou igual a {$rules['max']}");
            }
        }

        // Validações de string
        if (is_string($value)) {
            if (isset($rules['min_length']) && strlen($value) < $rules['min_length']) {
                throw new \InvalidArgumentException("deve ter pelo menos {$rules['min_length']} caracteres");
            }
            if (isset($rules['max_length']) && strlen($value) > $rules['max_length']) {
                throw new \InvalidArgumentException("deve ter no máximo {$rules['max_length']} caracteres");
            }
            if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                throw new \InvalidArgumentException("formato inválido");
            }
        }
    }

    public function getOptionsAttribute($value): ?array
    {
        if (is_null($value)) return null;
        if (is_array($value)) return $value;
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function setOptionsAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['options'] = null;
        } elseif (is_array($value)) {
            $this->attributes['options'] = json_encode($value);
        } elseif (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $this->attributes['options'] = json_encode($decoded);
            } else {
                $this->attributes['options'] = null;
            }
        } else {
            $this->attributes['options'] = null;
        }
    }

    /**
     * Verifica se um valor está vazio
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Converte o filtro para array de configuração de parâmetro
     */
    public function toParameterConfig(): array
    {
        return [
            'name' => $this->var_name,
            'type' => $this->type->value,
            'label' => $this->name,
            'description' => $this->description,
            'required' => $this->required,
            'default_value' => $this->default_value,
            'validation' => $this->validation_rules ?? [],
            'options' => $this->options ?? [],
            'order' => $this->order,
            'visible' => $this->visible,
        ];
    }

    /**
     * Cria um filtro a partir de array de configuração
     */
    public static function createFromConfigByQuery(int $dynamicQueryId, array $config): self
    {
        return self::create([
            'dynamic_query_id' => $dynamicQueryId,
            'dashboard_id' => null,
            'name' => $config['name'] ?? $config['var_name'],
            'description' => $config['description'] ?? null,
            'var_name' => $config['var_name'],
            'type' => FilterType::from($config['type'] ?? 'text'),
            'default_value' => $config['default_value'] ?? null,
            'required' => $config['required'] ?? false,
            'order' => $config['order'] ?? 0,
            'validation_rules' => $config['validation_rules'] ?? [],
            'visible' => $config['visible'] ?? true,
            'active' => $config['active'] ?? true,
            'options' => $config['options'] ?? [],
        ]);
    }

    /**
     * Cria um filtro a partir de array de configuração
     */
    public static function createFromConfigByDashboard(int $dashboard_id, array $config): self
    {
        return self::create([
            'dashboard_id' => $dashboard_id,
            'dynamic_query_id' => null,
            'name' => $config['name'] ?? $config['var_name'],
            'description' => $config['description'] ?? null,
            'var_name' => $config['var_name'],
            'type' => FilterType::from($config['type'] ?? 'text'),
            'default_value' => $config['default_value'] ?? null,
            'required' => $config['required'] ?? false,
            'order' => $config['order'] ?? 0,
            'validation_rules' => $config['validation_rules'] ?? [],
            'visible' => $config['visible'] ?? true,
            'active' => $config['active'] ?? true,
            'options' => $config['options'] ?? [],
        ]);
    }
}