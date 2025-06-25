<?php

namespace App\Models;

use App\Casts\PermissionStatusCast;
use App\Traits\FiltersByAccessLevel;
use App\Utils\PermissionStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;

/**
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string|null $group
 * @property string|null $group_description
 * @property PermissionStatus $access_level
 * @property-read Collection<int, PermissionGroup> $groups
 */
class Permission extends Model
{
    use HasFactory, FiltersByAccessLevel;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'description',
        'group',
        'group_description',
        'access_level'
    ];

    protected $casts = [
        'access_level' => PermissionStatusCast::class,
    ];

    /**
     * Relacionamento muitos-para-muitos com PermissionGroup
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(
            PermissionGroup::class,
            'permission_group_has_permissions',
            'permission_id',
            'permission_group_id'
        )->withTimestamps();
    }

    /**
     * Acessor para o nome do nível de acesso
     */
    public function setAccessLevelAttribute($value): void
    {
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
     * Verifica se o usuário atual pode atribuir essa permissão
     */
    public function canBeAssignedByCurrentUser(): bool
    {
        /** @var User $user */
        $user = Auth::guard('auth')->user();

        if (!$user) return false;

        return $user->accessLevel()->value >= $this->access_level->value;
    }
}
