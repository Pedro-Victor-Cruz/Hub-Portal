<?php

namespace App\Observers;

use App\Models\Dashboard;

class DashboardObserver
{
    public function created(Dashboard $dashboard): void
    {
        $dashboard->syncPermission();
        if ($dashboard->is_home) $this->unsetOtherHomeDashboards($dashboard);
    }

    public function updating(Dashboard $dashboard): void
    {
        if ($this->shouldSyncPermission($dashboard)) $dashboard->syncPermission();

        if ($dashboard->isDirty('is_home') && $dashboard->is_home === true) {
            $this->unsetOtherHomeDashboards($dashboard);
        }
    }

    private function shouldSyncPermission(Dashboard $dashboard): bool
    {
        return $dashboard->isDirty([
            'visibility',
            'key',
            'name'
        ]);
    }

    private function unsetOtherHomeDashboards(Dashboard $dashboard): void
    {
        Dashboard::where('id', '!=', $dashboard->id)
            ->where('is_home', true)
            ->update(['is_home' => false]);
    }
}
