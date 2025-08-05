<?php

namespace App\Exceptions\Erp;

class ErpServiceNotSupportedException extends \Exception
{
    public function __construct(string $serviceName, string $erpName)
    {
        parent::__construct("O serviço '{$serviceName}' não é suportado pelo ERP '{$erpName}'.");
    }
}