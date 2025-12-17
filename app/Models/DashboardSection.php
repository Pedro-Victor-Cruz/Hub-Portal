<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model DashboardSection
 *
 * Representa uma seção/nível dentro de um dashboard.
 * Suporta hierarquia através de parent_section_id.
 *
 * @property int $id
 * @property int $dashboard_id
 * @property int|null $parent_section_id
 * @property string $key
 * @property string|null $title
 * @property string|null $description
 * @property int $level
 * @property int $order
 * @property array|null $visibility_rules
 * @property array|null $navigation_config
 * @property array|null $drill_down_params
 * @property bool $active
 */
class DashboardSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'dashboard_id',
        'parent_section_id',
        'key',
        'title',
        'description',
        'level',
        'order',
        'active'
    ];

    protected $casts = [
        'level' => 'integer',
        'order' => 'integer',
        'active' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Dashboard ao qual pertence
     */
    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    /**
     * Seção pai (se for subseção)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(DashboardSection::class, 'parent_section_id');
    }

    /**
     * Subseções filhas
     */
    public function children(): HasMany
    {
        return $this->hasMany(DashboardSection::class, 'parent_section_id')
            ->where('active', true)
            ->orderBy('order');
    }

    /**
     * Widgets da seção
     */
    public function widgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class, 'section_id')
            ->with('dynamicQuery')
            ->orderBy('order');
    }

    /**
     * Scope para seções ativas
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para seções raiz (nível 1)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_section_id')->where('level', 1);
    }

    /**
     * Scope por nível
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Verifica se a seção é raiz
     */
    public function isRoot(): bool
    {
        return $this->parent_section_id === null && $this->level === 1;
    }

    /**
     * Verifica se tem subseções
     */
    public function hasChildren(): bool
    {
        return $this->children()->count() > 0;
    }

    /**
     * Obtém todos os ancestrais (caminho da raiz até esta seção)
     */
    public function getAncestors(): array
    {
        $ancestors = [];
        $current = $this->parent;

        while ($current) {
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Obtém o caminho completo (breadcrumb)
     */
    public function getBreadcrumb(): array
    {
        $path = array_map(fn($section) => [
            'id' => $section->id,
            'key' => $section->key,
            'title' => $section->title,
            'level' => $section->level,
        ], $this->getAncestors());

        $path[] = [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'level' => $this->level,
        ];

        return $path;
    }

    /**
     * Estrutura completa da seção com widgets e filhos
     */
    public function getFullStructure(): array
    {
        return [
            'section' => $this->makeHidden(['created_at', 'updated_at'])->toArray(),
            'breadcrumb' => $this->getBreadcrumb(),
            'widgets' => $this->widgets()->where('active', true)->get()->map(function ($widget) {
                return $widget->getFullConfig();
            }),
            'children' => $this->children->map(function ($child) {
                return $child->getFullStructure();
            }),
        ];
    }
}