<?php

namespace App\Models;

use Firebase\JWT\JWT;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model implements Authenticatable
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'users';

    protected $casts = [
        'status' => 'int',
        'last_login' => 'datetime'
    ];

    protected $hidden = [
        'password'
    ];

    protected $fillable = [
        'email',
        'name',
        'password',
        'status',
        'last_login'
    ];

    public function ownerPortal(): BelongsTo
    {
        return $this->belongsTo(Portal::class, 'user_id');
    }

    public function portals(): BelongsToMany
    {
        return $this->belongsToMany(Portal::class, 'portal_users');
    }

    public function accessGroups(): BelongsToMany
    {
        return $this->belongsToMany(AccessGroup::class, 'user_access_groups');
    }

    public function hasPermission(string $permissionName): bool
    {
        return $this->accessGroups()
            ->whereHas('permissions', function ($query) use ($permissionName) {
                $query->where('name', $permissionName);
            })
            ->exists();
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = bcrypt($value);
    }

    public function refresh_tokens()
    {
        return $this->hasMany(UsersRefreshToken::class);
    }

    public function revokeRefreshToken($token)
    {
        return $this->refresh_tokens()
            ->where('token', $token)
            ->update(['revoked' => true]);
    }

    public function deleteRefreshToken($token)
    {
        return $this->refresh_tokens()
            ->where('token', $token)
            ->delete();
    }

    /**
     * @throws RandomException
     */
    public function generateRefreshToken()
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

    public function generateJwt()
    {
        $refreshToken = $this->generateRefreshToken();
        // 1h em segundos
        $expiration = 60 * 60;
        $payload = [
            'iss' => config('app.url'),   // Emissor (Issuer)
            'aud' => config('app.url'),       // Público (Audience)
            'iat' => time(),              // Emitido em (Issued At)
            'exp' => time() + $expiration,       // Expiração (1 hora)
            'rule' => $this->role,           // Regra (Role)
            'data' => [
                'uid' => sha1($this->getAuthIdentifier()),
                'rt' => sha1($refreshToken['id'])
            ]
        ];

        return [
            'access_token' => JWT::encode($payload, config('app.key'), 'HS256'),
            'access_token_expires_in' => $expiration,
            'refresh_token' => $refreshToken['token']
        ];

    }

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
        // TODO: Implement setRememberToken() method.
    }

    public function getRememberTokenName()
    {
        return null;
    }
}
