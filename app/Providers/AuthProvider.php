<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Hashing\HashManager;
use Illuminate\Support\Facades\DB;

class AuthProvider implements UserProvider
{

    protected HashManager $hasher;
    protected $model;

    public function __construct($hasher, $model)
    {
        $this->hasher = $hasher;
        $this->model = $model;
    }

    public function retrieveById($identifier)
    {
        return $this->model->find($identifier);
    }

    public function retrieveByRefreshToken($token)
    {
        return $this->model::query()
            ->whereHas('refresh_tokens', function ($query) use ($token) {
                $query->where('token', $token)
                    ->where('expires_at', '>', now())
                    ->where('revoked', false);
            })
            ->first();
    }

    public function revokeRefreshToken($identifier, $token)
    {
        $this->model::query()
            ->where(DB::raw('sha1(id)'), $identifier)
            ->first()
            ->deleteRefreshToken($token);
    }

    public function retrieveByToken($identifier, $token)
    {
        /** @var User $model */
        $model = $this->model;
        return $model::query()
            ->where(DB::raw('sha1(id)'), $identifier)
            ->whereHas('refresh_tokens', function ($query) use ($token) {
                $query->where(DB::raw('sha1(id)'), $token)
                    ->where('expires_at', '>', now())
                    ->where('revoked', false);
            })
            ->first();
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // TODO: Implement updateRememberToken() method.
    }

    public function retrieveByCredentials(array $credentials)
    {
        return $this->model::where('email', $credentials['email'])->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $this->hasher->check($credentials['password'], $user->getAuthPassword());
    }
}
