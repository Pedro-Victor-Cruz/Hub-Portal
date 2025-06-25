<?php

namespace App\Traits;

use App\Models\User;
use App\Utils\PermissionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait FiltersByAccessLevel
{
    /**
     * Filtra os resultados pelo nível de acesso do usuário atual
     */
    public function scopeAccessibleByCurrentUser(Builder $query): Builder
    {
        /** @var User $user */
        $user = Auth::guard('auth')->user();
        $userLevel = $user->accessLevel();

        return $query->whereIn(
            'access_level',
            array_map(
                fn(PermissionStatus $level) => $level->value,
                PermissionStatus::getManageableLevels($userLevel)
            )
        );
    }

    /**
     * Filtra por um nível específico ou abaixo
     */
    public function scopeAccessibleByLevel(Builder $query, PermissionStatus $level): Builder
    {
        return $query->where(
            'access_level',
            '<=',
            $level->value
        );
    }
}