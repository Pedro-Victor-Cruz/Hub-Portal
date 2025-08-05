<?php

namespace App\Repositories\Erp;
use App\Models\CompanyErpSetting;

        class ErpSettingsRepository
        {
            public function getActiveForCompany(int $companyId): ?CompanyErpSetting
            {
                return CompanyErpSetting::where('company_id', $companyId)
                    ->where('active', true)
                    ->first();
            }


            public function getErpSettingsByType(int $companyId, string $erpName): ?CompanyErpSetting
            {
                return CompanyErpSetting::where('company_id', $companyId)
                    ->where('erp_name', $erpName)
                    ->first();
            }
        }