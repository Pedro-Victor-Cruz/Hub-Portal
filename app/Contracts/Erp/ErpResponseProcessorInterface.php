<?php

namespace App\Contracts\Erp;

use App\Services\Erp\Core\ErpServiceResponse;

interface ErpResponseProcessorInterface
{
    public function processResponse($response): ErpServiceResponse;
    public function extractData($response): mixed;
    public function extractError($response): ?string;
    public function isSuccessful($response): bool;
}