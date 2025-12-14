<?php

namespace App\Models;

use App\Traits\FiltersByAccessLevel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Class Parameter
 *
 * Representa um parâmetro configurável no sistema.
 * Cada parâmetro pode ter um valor padrão e opções de configuração,
 * e pode ser associado a várias empresas com valores específicos.
 *
 * @property  int $id
 * @property string $key Chave única do parâmetro
 * @property string $description Descrição do parâmetro
 * @property string $category Categoria do parâmetro (ex: "ERP", "Financeiro")
 * @property string $type Tipo do parâmetro (ex: "boolean", "integer", "decimal", "date", "text", "list")
 * @property mixed $default_value Valor padrão do parâmetro
 * @property array|null $options Opções disponíveis para o parâmetro (se aplicável)
 * @property bool $is_system Indica se o parâmetro é um parâmetro de sistema
 * @property int $access_level Nível de acesso necessário para visualizar/editar o parâmetro
 * @property \Illuminate\Support\Carbon|null $created_at Data de criação do parâmetro
 * @property \Illuminate\Support\Carbon|null $updated_at Data da última atualização do parâmetro
 *
 * @property mixed $value Valor do parâmetro para uma empresa específica ou o valor padrão
 * @property-read mixed $formatted_value Valor formatado do parâmetro para uma empresa específica ou o valor padrão
 */
class Parameter extends Model
{
    use HasFactory, FiltersByAccessLevel;

    protected $fillable = [
        'key',
        'description',
        'category',
        'type',
        'default_value',
        'options',
        'is_system',
        'access_level',
        'value'
    ];

    protected $casts = [
        'options' => 'array',
        'is_system' => 'boolean'
    ];


    /**
     * Retorna o valor formatado do parâmetro para a empresa especificada.
     * @return bool|Carbon|float|int|mixed
     */
    public function getFormattedValue(): mixed
    {
        $value = $this->value;

        return match ($this->type) {
            'boolean' => (bool)$value,
            'integer' => (int)$value,
            'decimal' => (float)$value,
            'date' => Carbon::parse($value),
            'list' => $this->options[$value] ?? $value,
            default => $value,
        };
    }

}
