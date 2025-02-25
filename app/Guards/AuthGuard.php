<?php

namespace App\Guards;

use App\Providers\AuthProvider;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Mockery\Exception;

class AuthGuard implements Guard
{

    protected AuthProvider $provider;
    protected Request $request;
    protected ?Authenticatable $user = null;

    public function __construct(AuthProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    public function attempt(array $credentials = [], $remember = false): bool|array
    {
        if (empty($credentials)) {
            $refresh_token = $this->request->header('Token');
            $user = $this->provider->retrieveByRefreshToken($refresh_token);
            if ($user)
                $this->provider->revokeRefreshToken(sha1($user->id), $refresh_token);
        } else $user = $this->validate($credentials);

        if ($user) {
            $jwt = $user->generateJwt();
            $this->setUser($user);
            return $jwt;
        }

        return false;
    }

    public function logout()
    {
        try {
            $refresh_token = $this->request->header('Token');
            $user = $this->provider->retrieveByRefreshToken($refresh_token);
            if ($user) {
                $this->provider->revokeRefreshToken($user->id, $refresh_token);
            }
            return true;
        } catch (Exception|\Throwable $ignored) {
            return false;
        }
    }

    public function check()
    {
        if ($this->user) return $this->user;
        try {

            $jwt = $this->request->bearerToken();
            $decoded = JWT::decode($jwt, new Key(config('app.key'), 'HS256'));
            if ($decoded->exp < time()) return null;
            $userIdentifier = $decoded->data->uid;
            $refreshTokenId = $decoded->data->rt;
            $user = $this->provider->retrieveByToken($userIdentifier, $refreshTokenId);
            if ($user) {
                $this->setUser($user);
                return $user;
            }
        } //        catch (ExpiredException $ignored) {}
        catch (Exception|\Throwable $ignored) {
        }

        return null;
    }

    public function guest()
    {
        return !$this->check();
    }

    public function user()
    {
        return $this->check();
    }

    public function id()
    {
        $user = $this->check();
        if ($user) return $user->getAuthIdentifier();
        return null;
    }

    public function validate(array $credentials = []): bool|Authenticatable
    {
        $user = $this->provider->retrieveByCredentials($credentials);
        if ($user && $this->provider->validateCredentials($user, $credentials)) {
            return $user;
        }
        return false;
    }

    public function hasUser()
    {
        if ($this->user) return true;
    }

    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }
}
