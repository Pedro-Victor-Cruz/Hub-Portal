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
 * @property-read Collection<int, Company> $companies Empresas associadas a este parâmetro
 * @property-read mixed $value Valor do parâmetro para uma empresa específica ou o valor padrão
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
        'access_level'
    ];

    protected $casts = [
        'options' => 'array',
        'is_system' => 'boolean'
    ];

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_parameter_values')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function getValueForCompany(?Company $company = null)
    {
        if ($company === null) return $this->default_value;

        $companyValue = $this->companies()->where('company_id', $company->id)->first();

        return $companyValue ? $companyValue->pivot->value : $this->default_value;
    }

    /**
     * Retorna o valor formatado do parâmetro para a empresa especificada.
     * Se a empresa não for especificada, retorna o valor global ou o valor padrão.
     * @param Company|null $company
     * @return bool|Carbon|float|int|mixed
     */
    public function getFormattedValue(?Company $company = null): mixed
    {
        $value = $this->getValueForCompany($company);

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
