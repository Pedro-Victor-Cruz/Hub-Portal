<?php

namespace App\Services\Core;

use App\Contracts\Services\ServiceInterface;
use App\Enums\ErpType;
use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceNotFoundException;
use App\Exceptions\Services\ServiceValidationException;
use App\Models\Company;
use App\Services\Erp\Drivers\Sankhya\Services\SankhyaDbExplorerService;
use App\Services\Global\ApiConsultService;

/**
 * Gerenciador central de todos os serviços da aplicação
 * Otimizado com cache e identificação por slug
 */
class ServiceManager
{
    private array $serviceRegistry = [];
    private array $servicesBySlug = [];
    private array $servicesCache = [];
    private bool $initialized = false;

    /**
     * Registra todos os serviços disponíveis
     */
    private function registerServices(): void
    {
        // Serviços ERP - Sankhya
        $this->registerErpService(ErpType::SANKHYA, [
            'sankhya-db-explorer' => SankhyaDbExplorerService::class,
            // Adicione mais serviços Sankhya aqui
        ]);

        // Serviços Globais
        $this->registerGlobalServices([
            'api-consult' => ApiConsultService::class,
            // Adicione mais serviços globais aqui
        ]);
    }

    /**
     * Inicialização lazy dos serviços
     */
    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            $this->registerServices();
            $this->initialized = true;
        }
    }

    /**
     * Executa um serviço específico pelo seu slug/identificador
     * @throws ServiceNotFoundException
     */
    public function executeService(string $serviceSlug, array $params = [], ?Company $company = null): ApiResponse
    {
        $this->ensureInitialized();

        $serviceInstance = $this->getServiceInstance($serviceSlug, $company);
        return $this->instantiateAndExecuteService($serviceInstance, $params, $company);
    }

    /**
     * Instancia e executa um serviço
     */
    private function instantiateAndExecuteService(ServiceInterface $serviceInstance, array $params, ?Company $company): ApiResponse
    {
        try {
            $serviceInstance->validateParams($params);

            return $serviceInstance->execute($params);

        } catch (ServiceValidationException $e) {
            return ApiResponse::error($e->getMessage(), $e->getValidationErrors());
        } catch (\Exception $e) {
            return ApiResponse::error("Erro ao executar serviço: " . $e->getMessage());
        }
    }

    /**
     * Obtém a classe do serviço pelo slug
     */
    private function getServiceClassBySlug(string $slug): ?string
    {
        $this->ensureInitialized();
        return $this->servicesBySlug[$slug] ?? null;
    }

    /**
     * Registra serviços de ERP com identificadores
     */
    private function registerErpService(ErpType $erpType, array $services): void
    {
        foreach ($services as $slug => $serviceClass) {
            $fullSlug = $erpType->value . '.' . $slug;

            $this->serviceRegistry['erp'][$erpType->value][$slug] = [
                'class' => $serviceClass,
                'slug' => $fullSlug,
                'category' => 'erp',
                'erp_type' => $erpType
            ];

            $this->servicesBySlug[$fullSlug] = $serviceClass;
        }
    }

    /**
     * Registra serviços globais com identificadores
     */
    private function registerGlobalServices(array $services): void
    {
        foreach ($services as $slug => $serviceClass) {
            $this->serviceRegistry['global'][$slug] = [
                'class' => $serviceClass,
                'slug' => $slug,
                'category' => 'global',
                'erp_type' => null
            ];

            $this->servicesBySlug[$slug] = $serviceClass;
        }
    }

    /**
     * Lista serviços filtrados por tipo e opcionalmente por ERP
     * Versão otimizada com cache
     */
    public function getServicesByType(ServiceType $type, ?Company $company = null): array
    {
        $this->ensureInitialized();

        $cacheKey = $this->buildCacheKey($type, $company);

        if (isset($this->servicesCache[$cacheKey])) {
            return $this->servicesCache[$cacheKey];
        }

        $result = [];
        $erpSetting = $company?->activeErpSetting();

        // Se não há ERP configurado, retorna apenas serviços globais
        if ($erpSetting === null) {
            $result = $this->getGlobalServices($type);
        } else {
            $result = array_merge(
                $this->getErpServices($type, $company),
                $this->getGlobalServices($type)
            );
        }

        $this->servicesCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * Obtém serviços globais por tipo
     */
    private function getGlobalServices(?ServiceType $type = null): array
    {
        $result = [];

        foreach ($this->serviceRegistry['global'] ?? [] as $slug => $serviceData) {
            $details = $this->getServiceDetails($serviceData, null);

            if (!$type) {
                $result[] = $details;
            } elseif ($details && $details['service_type'] === $type->value) {
                $result[] = $details;
            }
        }

        return $result;
    }

    /**
     * Obtém serviços ERP por tipo
     */
    private function getErpServices(?ServiceType $type, ?Company $company): array
    {
        $result = [];
        $erpType = $company?->activeErpSetting()?->getErpType();

        foreach ($this->serviceRegistry['erp'][$erpType->value] ?? [] as $slug => $serviceData) {
            $details = $this->getServiceDetails($serviceData, $company);

            if (!$type) {
                $result[] = $details;
            } elseif ($details && $details['service_type'] === $type->value) {
                $result[] = $details;
            }
        }

        return $result;
    }

    /**
     * Obtém detalhes de um serviço com tratamento de erros otimizado
     */
    private function getServiceDetails(array $serviceData, ?Company $company): ?array
    {
        $serviceClass = $serviceData['class'];

        if (!class_exists($serviceClass)) {
            return null;
        }

        try {
            /** @var ServiceInterface $instance */
            $instance = $this->getServiceInstanceByClass($serviceClass, $company);

            if (!$instance instanceof ServiceInterface) {
                throw new \RuntimeException("O serviço {$serviceClass} deve implementar ServiceInterface");
            }

            return [
                'slug' => $serviceData['slug'],
                'category' => $serviceData['category'],
                'erp_type' => $serviceData['erp_type']?->value,
                'name' => $instance->getServiceName(),
                'description' => $instance->getDescription(),
                'service_type' => $instance->getServiceType(),
                'required_params' => $instance->getRequiredParams(),
                'class_name' => class_basename($serviceClass), // Nome da classe sem namespace
            ];

        } catch (\Exception $e) {
            return [
                'slug' => $serviceData['slug'],
                'category' => $serviceData['category'],
                'erp_type' => $serviceData['erp_type']?->value,
                'name' => class_basename($serviceClass),
                'description' => 'Serviço temporariamente indisponível',
                'service_type' => 'N/A',
                'required_params' => [],
                'class_name' => class_basename($serviceClass),
                'error' => true
            ];
        }
    }

    /**
     * Constrói chave de cache baseada no tipo e empresa
     */
    private function buildCacheKey(ServiceType $type, ?Company $company): string
    {
        $erpKey = $company?->activeErpSetting()?->getErpType()->value ?? 'no-erp';
        $companyKey = $company?->id ?? 'no-company';

        return "services.{$type->value}.{$erpKey}.{$companyKey}";
    }

    /**
     * Lista todos os serviços disponíveis
     */
    public function getAllServices(?Company $company = null): array
    {
        $this->ensureInitialized();

        $services = $this->getGlobalServices();

        if ($company) {
            $services[] = $this->getErpServices(null,  $company);
        }

        return $services;
    }

    /**
     * Verifica se um serviço existe pelo slug
     */
    public function serviceExists(string $slug): bool
    {
        $this->ensureInitialized();
        return isset($this->servicesBySlug[$slug]);
    }

    /**
     * Limpa o cache de serviços (útil para testes e desenvolvimento)
     */
    public function clearCache(): void
    {
        $this->servicesCache = [];
    }

    /**
     * Obtém informações detalhadas de um serviço pelo slug
     */
    public function getServiceInfo(string $slug, ?Company $company = null): ?array
    {
        $this->ensureInitialized();

        $serviceClass = $this->getServiceClassBySlug($slug);
        if (!$serviceClass) {
            return null;
        }

        // Encontra os dados do registro
        foreach ($this->serviceRegistry as $category => $services) {
            if ($category === 'global' && isset($services[$slug])) {
                return $this->getServiceDetails($services[$slug], $company);
            } elseif ($category === 'erp') {
                foreach ($services as $erpType => $erpServices) {
                    foreach ($erpServices as $serviceSlug => $serviceData) {
                        if ($serviceData['slug'] === $slug) {
                            return $this->getServiceDetails($serviceData, $company);
                        }
                    }
                }
            }
        }

        return null;
    }

    public function getServiceInstance(string $serviceSlug, ?Company $company = null): ?ServiceInterface
    {
        $this->ensureInitialized();

        $serviceClass = $this->getServiceClassBySlug($serviceSlug);
        if (!$serviceClass) {
            return null;
        }


        return app($serviceClass, ['company' => $company]);
    }

    public function getServiceInstanceByClass(string $serviceClass, ?Company $company = null): ?ServiceInterface
    {
        if (!class_exists($serviceClass)) {
            return null; // Retorna null se a classe não existir
        }

        try {
            return app($serviceClass, ['company' => $company]);
        } catch (\Exception $e) {
            return null; // Retorna null se não conseguir instanciar
        }
    }

    public function existsService(string $serviceSlug): bool
    {
        $this->ensureInitialized();
        return isset($this->servicesBySlug[$serviceSlug]);
    }

    public function isServiceAvailableForCompany(string $serviceSlug, Company $company): bool
    {
        $this->ensureInitialized();
        $isGlobal = isset($this->serviceRegistry['global'][$serviceSlug]);

        // Se o serviço é global
        if ($isGlobal) return true;

        // Verifica se o serviço é específico de ERP e se a empresa tem um ERP ativo
        $erpSettings = $company->activeErpSetting();
        if ($erpSettings && isset($this->serviceRegistry['erp'][$erpSettings->getErpType()->value][$serviceSlug])) {
            return true;
        }

        // Se não é global e não está registrado para o ERP da empresa
        return false;
    }


    public function getServiceParameters(string $serviceSlug): array
    {
        $serviceInstance = $this->getServiceInstance($serviceSlug);
        if ($serviceInstance) {
            return $serviceInstance->getRequiredParams();
        }
        return [];
    }

}