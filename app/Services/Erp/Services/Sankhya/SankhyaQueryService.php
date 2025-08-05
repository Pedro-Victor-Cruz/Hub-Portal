<?php

namespace App\Services\Erp\Services\Sankhya;

use App\Services\Erp\Response\ErpServiceResponse;

class SankhyaQueryService extends BaseSankhyaService
{
    public function getServiceName(): string
    {
        return 'SANKHYA_QUERY';
    }

    public function getRequiredParams(): array
    {
        return ['sql'];
    }

    protected function getSankhyaServiceName(): string
    {
        return 'DbExplorerSP.executeQuery';
    }

    protected function buildRequestBody(array $params): array
    {
        return [
            'sql' => $params['sql'],
            'limit' => $params['limit'] ?? 1000,
            'offset' => $params['offset'] ?? 0,
        ];
    }

    protected function getDefaultParams(): array
    {
        return [
            'limit' => 1000,
            'offset' => 0,
        ];
    }
}