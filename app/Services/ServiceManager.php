<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\File;

class ServiceManager
{

    protected $services = [];

    // Carrega todos os serviços disponíveis na pasta Modules
    public function __construct()
    {
        $modulesPath = app_path('Modules');
        $moduleFolders = File::directories($modulesPath);

        foreach ($moduleFolders as $moduleFolder) {
            $serviceFiles = File::files($moduleFolder);

            foreach ($serviceFiles as $serviceFile) {
                $serviceClassName = 'App\\Modules\\' . basename($moduleFolder) . '\\' . $serviceFile->getFilenameWithoutExtension();
                $serviceInstance = new $serviceClassName();

                if ($serviceInstance instanceof BaseService) {
                    $this->services[$serviceInstance->serviceName] = $serviceInstance;
                }
            }
        }
    }

    // Executa um serviço específico
    public function executeService(?string $serviceName, array $requestBody): array
    {

        if (!$serviceName) {
            throw new Exception("'serviceName' não informado");
        }

        if (!isset($this->services[$serviceName])) {
            throw new Exception("Serviço não encontrado: $serviceName");
        }

        return $this->services[$serviceName]->execute($requestBody);
    }

}