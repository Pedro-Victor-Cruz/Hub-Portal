<?php

namespace App\Exceptions\Erp;

class ErpServiceException extends \Exception
{

    /**
     * ErpServiceException constructor.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = "An error occurred in the ERP service.", int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}