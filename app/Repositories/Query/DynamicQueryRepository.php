<?php

namespace App\Repositories\Query;

use App\Models\DynamicQuery;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para gerenciar consultas dinâmicas
 */
class DynamicQueryRepository
{

    public function findByKey(string $key): ?DynamicQuery
    {
        return DynamicQuery::active()->where('key', $key)->first();
    }

    public function getAvailableQueries(): Collection
    {
        return DynamicQuery::active()->orderBy('name', 'desc')->get()->values();
    }

    public function getByServiceSlug(string $serviceSlug): Collection
    {
        return DynamicQuery::active()->where('service_slug', $serviceSlug)->get();
    }

    public function createOrUpdate(array $data): DynamicQuery
    {
        return DynamicQuery::updateOrCreate(
            [
                'key' => $data['key'],
            ],
            $data
        );
    }

    public function delete(string $key): bool
    {
        $query = DynamicQuery::where('key', $key);
        return $query->delete() > 0;
    }

    public function getAvailableKeys(): array
    {
        return $this->getAvailableQueries()
            ->pluck('key')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Verifica se uma consulta existe
     */
    public function exists(string $key): bool
    {
        return $this->findByKey($key) !== null;
    }

    public function duplicateForCompany(string $key, array $overrides = []): ?DynamicQuery
    {
        $query = $this->findByKey($key);

        if (!$query) return null;

        $data = array_merge($query->toArray(), $overrides, [
            'created_at' => null,
            'updated_at' => null,
        ]);

        unset($data['id']);

        return DynamicQuery::create($data);
    }

    public function findGlobalByKey(string $key)
    {
        return DynamicQuery::active()
            ->where('key', $key)
            ->first();
    }
}