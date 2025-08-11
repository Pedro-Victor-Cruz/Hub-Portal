<?php

namespace App\Services\Erp\Core;

use App\Contracts\Erp\ErpIntegrationInterface;
use App\Exceptions\Erp\ErpAuthenticationException;
use App\Models\Company;
use App\Services\Core\BaseService;
use App\Facades\ErpManager;
use App\Services\Core\ApiResponse;
use Exception;

/**
 * Classe base para serviços de ERP com método execute()
 * Atualizada para ser mais flexível com diferentes tipos de requisições HTTP
 */
abstract class BaseErpService extends BaseService
{
    protected ErpIntegrationInterface $erpDriver;

    /**
     * @throws Exception
     */
    public function __construct(?Company $company)
    {
        parent::__construct($company);

        if (!$this->company) {
            throw new Exception('Empresa não especificada ou inválida.');
        }

        $this->erpDriver = ErpManager::forCompany($this->company);
    }

    /**
     * Método principal para execução do serviço
     *
     * @param array $params Parâmetros para execução
     * @return ApiResponse Resultado da execução
     * @throws Exception
     */
    public function execute(array $params = []): ApiResponse
    {
        try {
            // Garante autenticação antes da execução
            $this->ensureAuthenticated();

            // Executa o serviço específico
            return $this->performService($params);

        } catch (\App\Exceptions\Services\ServiceValidationException $e) {
            return $this->error(
                'Erro de validação',
                $e->getValidationErrors(),
                ['exception_type' => get_class($e)]
            );

        } catch (ErpAuthenticationException $e) {
            return $this->error(
                'Erro de autenticação com o ERP',
                [$e->getMessage()],
                [
                    'exception_type' => get_class($e),
                    'erp_type' => $this->erpDriver->getSettings()->erp_type ?? 'unknown',
                ]
            );

        } catch (Exception $e) {
            return $this->error(
                'Erro interno na execução do serviço',
                [$e->getMessage()],
                [
                    'exception_type' => get_class($e),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ]
            );
        }
    }

    /**
     * Verifica se o ERP está autenticado antes da execução
     * @throws Exception
     */
    protected function ensureAuthenticated(): void
    {
        if (!$this->erpDriver->authenticate()) {
            throw new ErpAuthenticationException(
                'Falha na autenticação com o ERP: ' . ($this->erpDriver->getSettings()->erp_type ?? 'Desconhecido')
            );
        }
    }

    /**
     * Obtém o driver ERP atual
     */
    protected function getErpDriver(): ErpIntegrationInterface
    {
        return $this->erpDriver;
    }

    /**
     * Obtém as configurações do ERP
     */
    protected function getErpSettings(): \App\Models\CompanyErpSetting
    {
        return $this->erpDriver->getSettings();
    }

    /**
     * Obtém o handler de autenticação do ERP
     */
    protected function getAuthHandler(): \App\Contracts\Erp\ErpAuthInterface
    {
        return $this->erpDriver->getAuthHandler();
    }


    /**
     * Método abstrato que deve ser implementado pelos services específicos
     *
     * @param array $params Parâmetros do serviço
     * @return ApiResponse Resultado do serviço
     */
    abstract protected function performService(array $params): ApiResponse;
}