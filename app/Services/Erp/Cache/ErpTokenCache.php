<?php

namespace App\Services\Erp\Cache;

use Illuminate\Support\Facades\Cache;

class ErpTokenCache
{
    private const DEFAULT_TTL = 300; // 5 minutos
    private const TOKEN_PREFIX = 'erp_token:';

    public static function get(string $cacheKey): ?string
    {
        return Cache::get(self::TOKEN_PREFIX . $cacheKey);
    }

    public static function put(string $cacheKey, string $token, int $ttl = self::DEFAULT_TTL): void
    {
        Cache::put(self::TOKEN_PREFIX . $cacheKey, $token, $ttl);
    }

    public static function forget(string $cacheKey): void
    {
        Cache::forget(self::TOKEN_PREFIX . $cacheKey);
    }

    public static function has(string $cacheKey): bool
    {
        return Cache::has(self::TOKEN_PREFIX . $cacheKey);
    }
}