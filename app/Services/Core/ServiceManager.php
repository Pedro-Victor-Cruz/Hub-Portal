<?php

namespace App\Services\Core;

use App\Contracts\Services\ServiceInterface;
use App\Enums\IntegrationType;
use App\Enums\ServiceType;
use App\Models\Company;
use App\Services\Erp\Sankhya\Services\SankhyaDbExplorerService;
use App\Services\Erp\Sankhya\Services\SankhyaLoadViewService;
use App\Services\Global\ApiConsultService;
use Exception;

/**
 * Gerenciador central de todos os serviços da aplicação
 * Otimizado com cache e identificação por slug
 */
class ServiceManager
{
    private array $serviceRegistry = [];
    private array $servicesBySlug = [];
    private bool $initialized = false;

    private function registerServices(): void
    {
        // Serviços de Integração Sankhya
        $this->registerIntegrationServices(IntegrationType::SANKHYA, [
            'db-explorer' => SankhyaDbExplorerService::class,
            'load-view' => SankhyaLoadViewService::class,
        ]);

        // Serviços Globais
        $this->registerGlobalServices([
            'api-consult' => ApiConsultService::class,
        ]);
    }

    private function registerIntegrationServices(IntegrationType $integrationType, array $services): void
    {
        foreach ($services as $slug => $serviceClass) {
            $fullSlug = "{$integrationType->value}.{$slug}";

            $this->serviceRegistry['integrations'][$integrationType->value][$slug] = [
                'class' => $serviceClass,
                'slug' => $fullSlug,
                'integration_type' => $integrationType
            ];

            $this->servicesBySlug[$fullSlug] = $serviceClass;
        }
    }

    private function registerGlobalServices(array $services): void
    {
        foreach ($services as $slug => $serviceClass) {
            $this->serviceRegistry['global'][$slug] = [
                'class' => $serviceClass,
                'slug' => $slug,
                'integration_type' => null
            ];

            $this->servicesBySlug[$slug] = $serviceClass;
        }
    }

    public function executeService(string $serviceSlug, array $params = [], ?Company $company = null): ApiResponse
    {
        $this->ensureInitialized();

        $serviceClass = $this->servicesBySlug[$serviceSlug] ?? null;
        if (!$serviceClass) {
            return ApiResponse::error("Serviço '{$serviceSlug}' não encontrado");
        }

        try {
            $service = app($serviceClass, ['company' => $company]);
            return $service->execute($params);
        } catch (Exception $e) {
            return ApiResponse::error("Erro ao executar serviço: " . $e->getMessage());
        }
    }

    private function ensureInitialized(): void
    {
        if (!$this->initialized) {
            $this->registerServices();
            $this->initialized = true;
        }
    }

    public function getServiceInstance(string $serviceSlug, ?Company $company = null)
    {
        $this->ensureInitialized();

        $serviceClass = $this->servicesBySlug[$serviceSlug] ?? null;
        if (!$serviceClass) {
            throw new Exception("Serviço '{$serviceSlug}' não encontrado");
        }

        return app($serviceClass, ['company' => $company]);
    }

    public function getServiceInfo(string $slug, ?Company $company = null): ?array
    {
        $this->ensureInitialized();

        $serviceClass = $this->getServiceClassBySlug($slug);
        if (!$serviceClass) {
            return null;
        }

        try {
            /** @var ServiceInterface $instance */
            $instance = $this->getServiceInstance($serviceClass, $company);

            if (!$instance instanceof ServiceInterface) {
                throw new \RuntimeException("O serviço {$serviceClass} deve implementar ServiceInterface");
            }

            return [
                'slug' => $slug,
                'name' => $instance->getServiceName(),
                'description' => $instance->getDescription(),
                'service_type' => $instance->getServiceType(),
                'required_params' => $instance->getRequiredParams(),
                'class_name' => class_basename($serviceClass), // Nome da classe sem namespace
            ];

        } catch (\Exception $e) {
            return [
                'slug' => $slug,
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
     * Lista serviços filtrados por tipo e opcionalmente por ERP
     * Versão otimizada com cache
     */
    public function getServicesByType(ServiceType $type, ?Company $company = null): array
    {
        $this->ensureInitialized();

        $filteredServices = [];

        foreach ($this->serviceRegistry as $category => $services) {
            foreach ($services as $slug => $serviceInfo) {

                // Se o "serviceInfo" ainda tiver arrays dentro (ex: sankhya)
                if (is_array($serviceInfo) && !isset($serviceInfo['class'])) {
                    foreach ($serviceInfo as $innerSlug => $innerServiceInfo) {
                        $this->processService($innerServiceInfo, $innerSlug, $company, $type, $filteredServices);
                    }
                } else {
                    $this->processService($serviceInfo, $slug, $company, $type, $filteredServices);
                }
            }
        }


        return $filteredServices;
    }

    private function processService(array $serviceInfo, string $slug, ?Company $company, ServiceType $type, array &$filteredServices): void
    {
        if (!isset($serviceInfo['class'])) {
            return;
        }

        $serviceClass = $serviceInfo['class'];

        try {
            /** @var ServiceInterface $instance */
            $instance = app($serviceClass, ['company' => $company]);

            if ($instance->getServiceType() == $type) {
                $filteredServices[] = [
                    'slug' => $serviceInfo['slug'],
                    'name' => $instance->getServiceName(),
                    'description' => $instance->getDescription(),
                    'required_params' => $instance->getRequiredParams(),
                    'class_name' => class_basename($serviceClass),
                ];
            }
        } catch (\Exception $e) {
            // ignora
        }
    }

    private function getServiceClassBySlug(string $slug): ?string
    {
        $this->ensureInitialized();
        return $this->servicesBySlug[$slug] ?? null;
    }

    public function existsService(string $slug): bool
    {
        $this->ensureInitialized();
        return isset($this->servicesBySlug[$slug]);
    }
}