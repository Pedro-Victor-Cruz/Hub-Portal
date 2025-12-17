<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Model Dashboard
 *
 * Representa um dashboard configurável com múltiplas seções,
 * widgets e filtros compartilhados.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property string|null $icon
 * @property array|null $config
 * @property bool $active
 */
class Dashboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'config',
        'active'
    ];

    protected $casts = [
        'config' => 'array',
        'active' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    /**
     * Seções de primeiro nível (root sections)
     */
    public function rootSections(): HasMany
    {
        return $this->hasMany(DashboardSection::class)
            ->whereNull('parent_section_id')
            ->orderBy('order');
    }

    /**
     * Todas as seções do dashboard
     */
    public function sections(): HasMany
    {
        return $this->hasMany(DashboardSection::class)->orderBy('level')->orderBy('order');
    }


    public function filters(): HasMany
    {
        return $this->hasMany(Filter::class, 'dashboard_id')->ordered();
    }

    /**
     * Todos os widgets do dashboard (através das seções)
     */
    public function allWidgets()
    {
        return DashboardWidget::whereIn('section_id', $this->sections->pluck('id'));
    }

    /**
     * Scope para dashboards ativos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Obtém a estrutura completa do dashboard
     */
    public function getFullStructure(): array
    {
        return [
            'dashboard' => $this->makeHidden(['created_at', 'updated_at'])->toArray(),
            'filters' => $this->filters()->get(),
            'sections' => $this->buildSectionsTree(),
        ];
    }

    /**
     * Constrói árvore hierárquica de seções
     */
    private function buildSectionsTree(?int $parentId = null): array
    {
        $sections = $this->sections()
            ->where('parent_section_id', $parentId)
            ->where('active', true)
            ->get();

        return $sections->map(function ($section) {
            return [
                'section' => $section->makeHidden(['created_at', 'updated_at'])->toArray(),
                'widgets' => $section->widgets()->where('active', true)->orderBy('order')->get(),
                'children' => $this->buildSectionsTree($section->id),
            ];
        })->toArray();
    }

    /**
     * Valida se o dashboard está pronto para uso
     */
    public function isReady(): array
    {
        $issues = [];

        if (!$this->active) {
            $issues[] = 'Dashboard está inativo';
        }

        if ($this->rootSections()->count() === 0) {
            $issues[] = 'Dashboard não possui seções';
        }

        $sectionsWithoutWidgets = $this->sections()
            ->whereDoesntHave('widgets')
            ->where('active', true)
            ->count();

        if ($sectionsWithoutWidgets > 0) {
            $issues[] = "{$sectionsWithoutWidgets} seção(ões) sem widgets";
        }

        return [
            'ready' => empty($issues),
            'issues' => $issues
        ];
    }

    /**
     * Clona o dashboard com todas suas dependências
     */
    public function duplicate(string $newKey, string $newName): self
    {
        $newDashboard = $this->replicate(['key', 'name']);
        $newDashboard->key = $newKey;
        $newDashboard->name = $newName;
        $newDashboard->save();

        // Clonar seções e widgets recursivamente
        $this->duplicateSections($newDashboard->id);

        return $newDashboard;
    }

    private function duplicateSections(int $newDashboardId, ?int $oldParentId = null, ?int $newParentId = null): void
    {
        $sections = $this->sections()->where('parent_section_id', $oldParentId)->get();

        foreach ($sections as $section) {
            $newSection = $section->replicate();
            $newSection->dashboard_id = $newDashboardId;
            $newSection->parent_section_id = $newParentId;
            $newSection->save();

            // Clonar widgets da seção
            foreach ($section->widgets as $widget) {
                $newWidget = $widget->replicate();
                $newWidget->section_id = $newSection->id;
                $newWidget->save();

                // Clonar ações do widget
                foreach ($widget->actions as $action) {
                    $newAction = $action->replicate();
                    $newAction->widget_id = $newWidget->id;
                    $newAction->save();
                }
            }

            // Clonar ações da seção
            foreach ($section->actions as $action) {
                $newAction = $action->replicate();
                $newAction->section_id = $newSection->id;
                $newAction->save();
            }

            // Recursivamente clonar subseções
            $this->duplicateSections($newDashboardId, $section->id, $newSection->id);
        }
    }
}