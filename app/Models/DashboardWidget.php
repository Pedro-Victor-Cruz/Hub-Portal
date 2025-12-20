<?php

namespace App\Models;

use App\Services\Parameter\ServiceParameter;
use App\Services\Parameter\ServiceParameterManager;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model DashboardWidget
 *
 * Representa um componente visual (gráfico, tabela, métrica, etc)
 * dentro de uma seção do dashboard.
 *
 * @property int $id
 * @property int $section_id
 * @property int|null $dynamic_query_id
 * @property string $key
 * @property string|null $title
 * @property string|null $description
 * @property string $widget_type
 * @property int $order
 * @property bool $active
 */
class DashboardWidget extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'dynamic_query_id',
        'key',
        'title',
        'description',
        'widget_type',
        'config',
        'order',
        'active'
    ];

    protected $casts = [
        'order' => 'integer',
        'active' => 'boolean',
        'config' => 'array',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];



    /**
     * Seção ao qual pertence
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(DashboardSection::class, 'section_id');
    }

    /**
     * Consulta dinâmica usada como fonte de dados
     */
    public function dynamicQuery(): BelongsTo
    {
        return $this->belongsTo(DynamicQuery::class);
    }

    /**
     * Scope para widgets ativos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope por tipo de widget
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('widget_type', $type);
    }

    /**
     * Verifica se é um widget de gráfico
     */
    public function isChart(): bool
    {
        return str_starts_with($this->widget_type, 'chart_');
    }

    /**
     * Obtém a configuração completa do widget
     */
    public function getFullConfig(): array
    {
        $config = $this->makeHidden(['created_at', 'updated_at'])->toArray();

        $config['query'] = $this->dynamicQuery ? [
            'key' => $this->dynamicQuery->key,
            'name' => $this->dynamicQuery->name,
        ] : null;

        return $config;
    }


    public function parametersWidge(): array {
        $parameterService = new ServiceParameterManager();

        $type = $this->widget_type;


        // se type começa com chart_
        if (str_starts_with($type, 'chart_')) {

        } else if ($type === 'table') {
        } else if ($type === 'metric_card') {
        }

        return $parameterService->getGrouped();
    }

}