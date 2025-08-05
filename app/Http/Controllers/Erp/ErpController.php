<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Services\Erp\ErpManager;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ErpController extends Controller
{

    private ErpManager $erpManager;

    public function __construct(ErpManager $erpManager)
    {
        $this->erpManager = $erpManager;
    }



}