<?php

namespace App\Models;

use App\Casts\PermissionStatusCast;
use App\Traits\FiltersByAccessLevel;
use App\Utils\PermissionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int|null $company_id
 * @property PermissionStatus $access_level
 * @property bool $is_system
 * @property-read Company|null $company
 * @property-read Collection<int, Permission> $permissions
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, PermissionGroup> $groups
 */
class PermissionGroup extends Model
{
    use HasFactory, FiltersByAccessLevel;

    protected $table = 'permission_groups';

    protected $fillable = ['name', 'description', 'company_id', 'access_level', 'is_system'];

    protected $casts = [
        'is_system' => 'boolean',
        'access_level' => PermissionStatusCast::class,
        'company_id' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_group_has_permissions');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_has_permission_groups');
    }


    /**
     * Adiciona uma permissão ao grupo de permissões
     * @param string $permissionName
     * @return $this
     */
    public function assignPermission(string $permissionName): static
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();

        $this->permissions()->syncWithoutDetaching($permission->id);

        return $this;
    }

    /**
     * Remove uma permissão do grupo de permissões
     * @param string $permissionName
     * @return $this
     */
    public function removePermission(string $permissionName): static
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();

        $this->permissions()->detach($permission->id);

        return $this;
    }

    /**
     * Sincroniza as permissões do grupo com um array de nomes
     * @param array $permissionNames
     * @return $this
     */
    public function syncPermissions(array $permissionNames): static
    {
        $permissions = Permission::whereIn('name', $permissionNames)->get();

        $this->permissions()->sync($permissions->pluck('id'));

        return $this;
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('company_id');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function isGlobal(): bool
    {
        return is_null($this->company_id);
    }

    public function setAccessLevelAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['access_level'] = PermissionStatus::USER->value;
            return;
        }

        if ($value instanceof PermissionStatus) {
            $this->attributes['access_level'] = $value->value;
        } else {
            $this->attributes['access_level'] = PermissionStatus::fromValue($value)->value;
        }
    }

    public function getAccessLevelAttribute($value): PermissionStatus
    {
        return PermissionStatus::fromValue($value);
    }

    /**
     * Verifica se o usuário atual pode gerenciar este grupo
     * @return bool
     */
    public function canBeManagedByCurrentUser(): bool
    {
        /** @var User $user */
        $user = Auth::guard('auth')->user();

        // Usuário não autenticado não pode gerenciar nada
        if (!$user) return false;

        // Verifica primeiro o nível de acesso
        if ($this->access_level->value > $user->accessLevel()->value) return false;

        // Grupos globais (sem company_id) podem ser gerenciados se o nível de acesso permitir
        if (is_null($this->company_id)) return true;

        // Administradores podem gerenciar grupos de qualquer empresa (desde que o nível permita)
        if ($user->isAdmin()) return true;

        // Para não-administradores, verifica se o grupo pertence à mesma empresa
        return $user->company_id && $user->company_id === $this->company_id;
    }

    /**
     * Escopo para filtrar grupos de permissões que o usuário atual pode gerenciar
     * @param Builder $query
     * @return Builder
     */
    public function scopeManageableByCurrentUser(Builder $query): Builder
    {
        /** @var User $user */
        $user = Auth::guard('auth')->user();

        // Se não houver usuário autenticado, não retorna nada
        if (!$user) return $query->where('id', 0);

        return $query->where('access_level', '<=', $user->accessLevel()->value)
            ->where(function($q) use ($user) {
                // Grupos globais OU
                $q->whereNull('company_id')
                    // Do admin OU
                    ->orWhere(function($q) use ($user) {
                        if ($user->isAdmin()) {
                            // Admin pode ver grupos de qualquer empresa
                            $q->whereNotNull('company_id');
                        } else {
                            // Não-admin só pode ver grupos da própria empresa
                            $q->where('company_id', $user->company_id);
                        }
                    });
            });
    }

}
