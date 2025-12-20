<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Utils\PermissionStatus;
use Illuminate\Database\Seeder;

/**
 * Seeder para popular os grupos de permissão do sistema.
 *
 * Command: php artisan db:seed --class=PermissionGroupSeeder
 */
class PermissionGroupSeeder extends Seeder
{
    private array $systemGroups = [
        [
            'name' => 'administrator',
            'description' => 'Administrador',
            'is_system' => true,
            'access_level' => PermissionStatus::ADMINISTRATOR,
            'permissions' => ['*']
        ],
        [
            'name' => 'users',
            'description' => 'Usuários',
            'is_system' => true,
            'access_level' => PermissionStatus::USER
        ]
    ];

    public function run(): void
    {
        foreach ($this->systemGroups as $groupData) {
            $permissions = $groupData['permissions'] ?? [];
            unset($groupData['permissions']);

            $group = PermissionGroup::firstOrCreate(
                ['name' => $groupData['name']],
                $groupData
            );

            if ($permissions === ['*']) {
                // Atribui todas as permissões
                $group->permissions()->sync(Permission::all()->pluck('id'));
            } else {
                // Atribui permissões específicas
                $permissionIds = Permission::whereIn('name', $permissions)
                    ->pluck('id')
                    ->toArray();

                $group->permissions()->syncWithoutDetaching($permissionIds);
            }
        }

        $this->command->info('System permission groups seeded successfully!');
    }
}
