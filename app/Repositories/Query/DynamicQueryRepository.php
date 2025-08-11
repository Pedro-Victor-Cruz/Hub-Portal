<?php

namespace App\Repositories\Query;

use App\Models\Company;
use App\Models\DynamicQuery;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repository para gerenciar consultas dinâmicas
 */
class DynamicQueryRepository
{
    /**
     * Busca uma consulta dinâmica por chave e empresa
     * Prioriza consultas específicas da empresa sobre as globais
     */
    public function findByKey(string $key, ?Company $company = null): ?DynamicQuery
    {
        $query = DynamicQuery::active()->where('key', $key);

        if ($company) {
            // Busca primeiro por consulta específica da empresa
            $companyQuery = clone $query;
            $companySpecific = $companyQuery->forCompany($company)
                ->orderBy('priority', 'desc')
                ->first();

            if ($companySpecific) {
                return $companySpecific;
            }
        }

        // Se não encontrou específica da empresa, busca global
        return $query->global()
            ->orderBy('priority', 'desc')
            ->first();
    }

    /**
     * Lista todas as consultas disponíveis para uma empresa
     */
    public function getAvailableQueries(?Company $company = null): Collection
    {
        $globalQueries = DynamicQuery::active()
            ->global()
            ->get();

        if ($company) {
            $companyQueries = DynamicQuery::active()
                ->forCompany($company)
                ->get();

            // Sobrescreve consultas globais com as específicas da empresa
            $queries = $globalQueries
                ->keyBy('key')
                ->merge($companyQueries->keyBy('key'))
                ->values(); // 🔹 reseta para array numérico
        } else {
            $queries = $globalQueries->values(); // 🔹 array numérico
        }

        return $queries->sortBy('name')->values();
    }

    /**
     * Lista consultas por classe de serviço
     */
    public function getByServiceSlug(string $serviceSlug, ?Company $company = null): Collection
    {
        $query = DynamicQuery::active()->where('service_slug', $serviceSlug);

        if ($company) {
            $query->where(function ($q) use ($company) {
                $q->where('is_global', true)
                    ->orWhere('company_id', $company->id);
            });
        } else {
            $query->where('is_global', true);
        }

        return $query->orderBy('priority', 'desc')->get();
    }

    /**
     * Cria ou atualiza uma consulta dinâmica
     */
    public function createOrUpdate(array $data): DynamicQuery
    {
        return DynamicQuery::updateOrCreate(
            [
                'key' => $data['key'],
                'company_id' => $data['company_id'] ?? null,
            ],
            $data
        );
    }

    /**
     * Remove uma consulta dinâmica
     */
    public function delete(string $key, ?Company $company = null): bool
    {
        $query = DynamicQuery::where('key', $key);

        if ($company) {
            $query->where('company_id', $company->id);
        } else {
            $query->where('is_global', true);
        }

        return $query->delete() > 0;
    }

    /**
     * Lista todas as chaves de consulta disponíveis
     */
    public function getAvailableKeys(?Company $company = null): array
    {
        return $this->getAvailableQueries($company)
            ->pluck('key')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Verifica se uma consulta existe
     */
    public function exists(string $key, ?Company $company = null): bool
    {
        return $this->findByKey($key, $company) !== null;
    }

    /**
     * Duplica uma consulta global para uma empresa específica
     */
    public function duplicateForCompany(string $key, Company $company, array $overrides = []): ?DynamicQuery
    {
        $globalQuery = $this->findByKey($key, null);

        if (!$globalQuery || !$globalQuery->is_global) {
            return null;
        }

        $data = array_merge($globalQuery->toArray(), $overrides, [
            'company_id' => $company->id,
            'is_global' => false,
            'created_at' => null,
            'updated_at' => null,
        ]);

        unset($data['id']);

        return DynamicQuery::create($data);
    }
}