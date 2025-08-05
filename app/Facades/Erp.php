<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Erp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'erp.manager';
    }
}