<?php

namespace App\Models;

use App\Utils\PermissionStatus;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class User
 *
 * Representa um usuário do sistema.
 *
 * @property int $id
 * @property string $email
 * @property string $name
 * @property string $password
 * @property int $status
 * @property int $dashboard_home_id
 * @property Carbon|null $last_login
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property Collection|UsersRefreshToken[] $refresh_tokens Tokens de atualização vinculados ao usuário
 * @property Collection|PermissionGroup[] $permissionGroups Grupos de permissão do usuário
 */
class User extends Model implements Authenticatable
{
    use SoftDeletes, HasFactory;

    protected $table = 'users';

    protected $casts = [
        'status'     => 'int',
        'last_login' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    protected $fillable = [
        'email',
        'name',
        'password',
        'status',
        'last_login',
        'dashboard_home_id',
    ];

    /**
     * Hash automático da senha ao setar.
     */
    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function dashboardHome(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class, 'dashboard_home_id');
    }

    /**
     * Tokens de atualização vinculados ao usuário.
     */
    public function refresh_tokens(): HasMany
    {
        return $this->hasMany(UsersRefreshToken::class);
    }

    /**
     * Revoga um token de atualização.
     */
    public function revokeRefreshToken($token)
    {
        return $this->refresh_tokens()
            ->where('token', $token)
            ->update(['revoked' => true]);
    }

    /**
     * Exclui um token de atualização.
     */
    public function deleteRefreshToken($token)
    {
        return $this->refresh_tokens()
            ->where('token', $token)
            ->delete();
    }


    public function generateRefreshToken(): UsersRefreshToken
    {
        $request = request();

        $refreshToken = new UsersRefreshToken();
        $refreshToken->user_id = $this->id;
        $refreshToken->token = bin2hex(random_bytes(32));
        $refreshToken->expires_at = now()->addDays(30);
        $refreshToken->last_ip = $request->ip();
        $refreshToken->device_name = $request->header('Device-Name');
        $refreshToken->manufacturer = $request->header('Manufacturer');
        $refreshToken->model = $request->header('Model');
        $refreshToken->platform = $request->header('Platform');
        $refreshToken->os_version = $request->header('OS-Version');
        $refreshToken->operating_system = $request->header('Operating-System');
        $refreshToken->latitude = $request->header('Latitude');
        $refreshToken->longitude = $request->header('Longitude');
        $refreshToken->save();

        return $refreshToken;
    }

    /**
     * Gera o token JWT de acesso e o token de refresh.
     */
    public function generateJwt(): array
    {
        $refreshToken = $this->generateRefreshToken();

        $expiration = 60 * 60; // 1 hora

        $payload = [
            'iss'  => config('app.url'),
            'aud'  => config('app.url'),
            'iat'  => time(),
            'exp'  => time() + $expiration,
            'data' => [
                'uid' => sha1($this->getAuthIdentifier()),
                'rt'  => sha1($refreshToken['id']),
            ],
        ];

        return [
            'access_token'            => JWT::encode($payload, config('app.key'), 'HS256'),
            'access_token_expires_in' => $expiration,
            'refresh_token'           => $refreshToken['token'],
        ];
    }


    // Métodos do contrato Authenticatable

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPassword()
    {
        return $this->password;
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // Método não utilizado
    }

    public function getRememberTokenName()
    {
        return null;
    }

    public function permissionGroups(): BelongsToMany
    {
        return $this->belongsToMany(PermissionGroup::class, 'user_has_permission_groups');
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    /**
     * Verifica se o usuário tem uma permissão específica.
     *
     * @param string $permissionName Nome da permissão a ser verificada
     * @return bool Retorna true se o usuário tiver a permissão, caso contrário, false
     */
    public function hasPermissionTo(string $permissionName): bool
    {
        // Verifica primeiro as permissões diretas
        $directPermission = $this->directPermissions()
            ->where('name', $permissionName)
            ->first();

        if ($directPermission) {
            return (bool)$directPermission->pivot->is_active;
        }

        // Se não tiver permissão direta, verifica nos grupos
        foreach ($this->permissionGroups as $group) {
            if ($group->permissions->contains('name', $permissionName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se o usuário tem qualquer uma das permissões especificadas.
     *
     * @param array $permissionNames Lista de nomes de permissões a serem verificadas
     * @return bool Retorna true se o usuário tiver pelo menos uma das permissões, caso contrário, false
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        foreach ($permissionNames as $permissionName) {
            if ($this->hasPermissionTo($permissionName)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Verifica se o usuário é um administrador.
     * Um administrador é um usuário que pertence a um grupo de permissões com nível de acesso
     * de administrador ou super administrador.
     *
     * @return bool Retorna true se o usuário for um administrador, caso contrário, false
     */
    public function isAdmin(): bool
    {
        return $this->permissionGroups->contains(function (PermissionGroup $group) {
            return $group->access_level === PermissionStatus::ADMINISTRATOR;
        });
    }

    public function accessLevel(): PermissionStatus
    {
        $highestLevel = PermissionStatus::USER;

        foreach ($this->permissionGroups as $group) {
            if ($group->access_level->value >= $highestLevel->value) {
                $highestLevel = $group->access_level;
            }
        }

        return $highestLevel;
    }

    public function group(): ?PermissionGroup
    {
        return $this->permissionGroups->first();
    }

    /**
     * Atribui um grupo de permissão ao usuário.
     *
     * @param PermissionGroup $group Instância do grupo de permissão a ser atribuído
     * @return static Retorna a instância do usuário para encadeamento de métodos
     */
    public function assignPermissionGroup(PermissionGroup $group): static
    {
        $this->permissionGroups()->syncWithoutDetaching([$group->id]);
        return $this;
    }

    /**
     * Remove um grupo de permissão do usuário.
     *
     * @param PermissionGroup $group Instância do grupo de permissão a ser removido
     * @return static Retorna a instância do usuário para encadeamento de métodos
     */
    public function removePermissionGroup(PermissionGroup $group): static
    {
        $this->permissionGroups()->detach($group->id);
        return $this;
    }

    /**
     * Atribui uma permissão direta ao usuário.
     *
     * @param string $permissionName Nome da permissão a ser atribuída
     * @param bool $isActive Indica se a permissão está ativa (padrão é true)
     * @return static Retorna a instância do usuário para encadeamento de métodos
     */
    public function assignDirectPermission(string $permissionName, bool $isActive = true): static
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();

        $this->directPermissions()->syncWithoutDetaching([
            $permission->id => ['is_active' => $isActive]
        ]);

        return $this;
    }

    /**
     * Remove uma permissão direta do usuário.
     *
     * @param string $permissionName Nome da permissão a ser removida
     * @return static Retorna a instância do usuário para encadeamento de métodos
     */
    public function removeDirectPermission(string $permissionName): static
    {
        $permission = Permission::where('name', $permissionName)->firstOrFail();

        $this->directPermissions()->detach($permission->id);

        return $this;
    }

    /**
     * Sincroniza as permissões diretas do usuário.
     * Formato esperad do array:
     * ['name' => 'permission_name', 'value' => true]
     * @param array $permissions
     * @return $this
     */
    public function syncDirectPermissions(array $permissions): static
    {
        foreach ($permissions as $permission) {
            if (!isset($permission['name']) || !isset($permission['value'])) {
                continue; // Ignora se não tiver nome ou valor
            }

            $permissionName = $permission['name'];
            $isActive = (bool)$permission['value'];

            if ($isActive) {
                // Verifica se a permissão já está atribuída no grupo caso o usuário possua um grupo
                $existingPermissionInGroup = $this->permissionGroups
                    ->flatMap(fn($group) => $group->permissions)
                    ->contains('name', $permissionName);

                // Se a permissão já existe no grupo e estou tentando ativar, remova do usuário
                // Para que não haja duplicidade
                if ($existingPermissionInGroup) {
                    $this->removeDirectPermission($permissionName);
                    continue; // Pula para a próxima permissão
                }

            }
            $this->assignDirectPermission($permissionName, $isActive);
        }

        return $this;
    }

    /**
     * Obtém a fonte de uma permissão específica.
     *
     * @param string $permissionName Nome da permissão a ser verificada
     * @return array|null Retorna um array com a fonte e informações adicionais se a permissão for encontrada,
     *                    ou null se não for encontrada
     */
    public function getPermissionSource(string $permissionName): ?array
    {
        // Verifica se tem permissão direta
        $directPermission = $this->directPermissions()
            ->where('name', $permissionName)
            ->first();

        if ($directPermission) {
            return [
                'source'    => 'user',
                'is_active' => (bool)$directPermission->pivot->is_active
            ];
        }

        // Verifica nos grupos
        foreach ($this->permissionGroups as $group) {
            if ($group->permissions->contains('name', $permissionName)) {
                return [
                    'source'     => 'group',
                    'group_id'   => $group->id,
                    'group_name' => $group->name
                ];
            }
        }

        return null;
    }

    /**
     * Obtém todas as informações do usuário, incluindo permissões e grupos
     */
    public function getFullInfo(): array
    {
        // Carrega os relacionamentos necessários de forma otimizada
        $this->load([
            'permissionGroups.permissions',
            'directPermissions'
        ]);

        // Obtém todas as permissões disponíveis no sistema
        $allPermissions = Permission::all()->keyBy('name');

        // Mapeia as permissões do usuário (diretas e de grupos)
        $permissions = [];
        foreach ($allPermissions as $permission) {
            $source = $this->getPermissionSource($permission->name);

            if (!$source) continue;

            $permissions[] = [
                'name'        => $permission->name,
                'description' => $permission->description,
                'group'       => $permission->group,
                'source'      => $source['source'],
                'is_active'   => $source['is_active'] ?? true,
                'group_name'  => $source['group_name'] ?? null,
                'group_id'    => $source['group_id'] ?? null,
            ];
        }

        $group = $this->permissionGroups->first();

        return [
            'id'                => $this->id,
            'email'             => $this->email,
            'name'              => $this->name,
            'status'            => $this->status,
            'last_login'        => $this->last_login?->toDateTimeString(),
            'group' => $group ? [
                'id'          => $group->id,
                'name'        => $group->name,
                'description' => $group->description,
                'is_system'   => $group->is_system,
            ] : null,
            'permissions'       => $permissions,
        ];
    }

    public function canBeAssignedPermissions(array $permissionsName): bool
    {
        /** @var Permission $permissions */
        $permissions = Permission::whereIn('name', $permissionsName)->get();

        foreach ($permissions as $permission) {
            if ($permission->canBeAssignedByCurrentUser()) continue;
            return false;
        }

        return true;
    }

    /**
     * Verifica se o usuário pode ser atribuído a um grupo de permissões.
     * @param int $groupId
     * @return bool
     */
    public function canBeAssignedGroup(int $groupId): bool
    {
        /** @var PermissionGroup $group */
        $group = PermissionGroup::find($groupId);
        if (!$group) return false;
        return $group->canBeManagedByCurrentUser();
    }
}
