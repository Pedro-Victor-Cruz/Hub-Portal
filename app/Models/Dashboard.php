<?php

namespace App\Models;

use App\Utils\PermissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

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
 * @property 'authenticated' |'restricted' $visibility;
 * @property int $permission_id;
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
        'active',
        'visibility',
        'is_navigable',
        'is_home',
        'permission_id',
    ];

    protected $casts = [
        'config'       => 'array',
        'active'       => 'boolean',
        'is_navigable' => 'boolean',
        'is_home'      => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }

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
     * Convites do dashboard
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(DashboardInvitation::class);
    }

    /**
     * Convites ativos e válidos
     */
    public function validInvitations(): HasMany
    {
        return $this->hasMany(DashboardInvitation::class)
            ->active()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('max_uses')
                    ->orWhereRaw('uses_count < max_uses');
            });
    }

    /**
     * Scope para dashboards ativos
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope para dashboards navegáveis
     */
    public function scopeNavigable($query)
    {
        return $query->where('is_navigable', true);
    }

    /**
     * Scope para dashboards definidos como home
     */
    public function scopeHome($query)
    {
        return $query->where('is_home', true);
    }

    /**
     * Obtém a estrutura completa do dashboard
     */
    public function getFullStructure(): array
    {
        return [
            'dashboard' => $this->makeHidden(['created_at', 'updated_at'])->toArray(),
            'filters'   => $this->filters()->get(),
            'sections'  => $this->buildSectionsTree(),
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
                'section'  => $section->makeHidden(['created_at', 'updated_at'])->toArray(),
                'widgets'  => $section->widgets()->where('active', true)->orderBy('order')->get(),
                'children' => $this->buildSectionsTree($section->id),
            ];
        })->toArray();
    }


    /**
     * Verifica se o usuário atual tem permissão para visualizar o dashboard.
     *
     * Regras de acesso:
     * - authenticated  → exige usuário autenticado
     * - restricted     → exige usuário autenticado E permissão explícita
     *
     * @return bool True se o usuário puder acessar o dashboard, false caso contrário
     */
    public function userHasAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::guard('auth')->user();

        return match ($this->visibility) {
            // Apenas usuário autenticado
            'authenticated' => $user !== null,

            // Autenticado + permissão específica
            'restricted' => $user !== null
                && $this->permission !== null
                && $user->hasAnyPermission([$this->permission->name, 'dashboard.view']),

            default => false,
        };
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
            }

            // Recursivamente clonar subseções
            $this->duplicateSections($newDashboardId, $section->id, $newSection->id);
        }
    }

    public function syncPermission(): void
    {
        // Somente dashboards RESTRICTED possuem permissão
        if ($this->visibility !== 'restricted') {

            if ($this->permission_id && $this->permission) {
                $this->permission->delete();

                $this->forceFill([
                    'permission_id' => null
                ])->saveQuietly();
            }

            return;
        }

        // A partir daqui: visibility === 'restricted'
        $permissionName = "dashboard.access.{$this->key}";
        $permissionDescription = "Acesso ao dashboard: {$this->name}";

        // Já existe permissão → apenas garante consistência
        if ($this->permission_id && $this->permission) {

            $updates = [];

            if ($this->permission->name !== $permissionName) {
                $updates['name'] = $permissionName;
            }

            if ($this->permission->description !== $permissionDescription) {
                $updates['description'] = $permissionDescription;
            }

            if (!empty($updates)) {
                $this->permission->update($updates);
            }

            return;
        }

        // Não existe permissão → cria
        $permission = Permission::create([
            'name'         => $permissionName,
            'description'  => $permissionDescription,
            'group'        => 'Dashboards',
            'access_level' => PermissionStatus::USER,
        ]);

        $this->forceFill([
            'permission_id' => $permission->id
        ])->saveQuietly();
    }

}