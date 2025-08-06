<?php

namespace App\Contracts\Erp;

use App\Services\Erp\Core\ErpServiceResponse;

interface ErpServiceInterface
{
    public function execute(array $params = []): ErpServiceResponse;
    public function getServiceName(): string;
    public function validateParams(array $params): bool;
    public function getRequiredParams(): array;
}