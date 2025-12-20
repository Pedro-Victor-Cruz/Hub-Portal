<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Utils\PermissionStatus;
use Illuminate\Database\Seeder;

/**
 * Seeder para popular as permissões do sistema.
 *
 * Command: php artisan db:seed --class=PermissionSeeder
 */
class PermissionSeeder extends Seeder
{
    private array $systemPermissions = [
        // Usuários
        [
            'name'         => 'user.view',
            'description'  => 'Visualizar usuários',
            'group'        => 'Usuários',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'user.create',
            'description'  => 'Criar usuários',
            'group'        => 'Usuários',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'user.edit',
            'description'  => 'Editar usuários',
            'group'        => 'Usuários',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'user.delete',
            'description'  => 'Excluir usuários',
            'group'        => 'Usuários',
            'access_level' => PermissionStatus::USER
        ],


        // Integrações | Permissão para poder configurar integrações
        [
            'name'         => 'integration.manage',
            'description'  => 'Gerenciar integrações',
            'group'        => 'Integrações',
            'access_level' => PermissionStatus::USER
        ],

        // Permissions
        [
            'name'         => 'permission.view',
            'description'  => 'Visualizar permissões',
            'group'        => 'Permissões',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'permission.create',
            'description'  => 'Criar permissões',
            'group'        => 'Permissões',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'permission.edit',
            'description'  => 'Editar permissões',
            'group'        => 'Permissões',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'permission.delete',
            'description'  => 'Excluir permissões',
            'group'        => 'Permissões',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'permission.assign',
            'description'  => 'Atribuir permissões específicas a usuários',
            'group'        => 'Permissões',
            'access_level' => PermissionStatus::USER
        ],

        // Permission Groups
        [
            'name'         => 'permission_group.view',
            'description'  => 'Visualizar grupos de permissões',
            'group'        => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'permission_group.create',
            'description'  => 'Criar grupos de permissões',
            'group'        => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'permission_group.edit',
            'description'  => 'Editar grupos de permissões',
            'group'        => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'permission_group.delete',
            'description'  => 'Excluir grupos de permissões',
            'group'        => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'permission_group.assign',
            'description'  => 'Atribuir grupos de permissões a usuários',
            'group'        => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],

        // Parâmetros
        [
            'name'         => 'parameter.view',
            'description'  => 'Visualizar parâmetros do sistema',
            'group'        => 'Parâmetros',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'parameter.create',
            'description'  => 'Criar parâmetros do sistema',
            'group'        => 'Parâmetros',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'parameter.edit',
            'description'  => 'Editar parâmetros do sistema',
            'group'        => 'Parâmetros',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'parameter.delete',
            'description'  => 'Excluir parâmetros do sistema',
            'group'        => 'Parâmetros',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],

        // System
        [
            'name'         => 'system.settings',
            'description'  => 'Gerenciar configurações do sistema',
            'group'        => 'Sistema',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],

        // Dynamic Queries
        [
            'name'         => 'dynamic_query.view',
            'description'  => 'Visualizar consultas dinâmicas',
            'group'        => 'Consultas Dinâmicas',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'dynamic_query.create',
            'description'  => 'Criar consultas dinâmicas',
            'group'        => 'Consultas Dinâmicas',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'dynamic_query.edit',
            'description'  => 'Editar consultas dinâmicas',
            'group'        => 'Consultas Dinâmicas',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'dynamic_query.delete',
            'description'  => 'Excluir consultas dinâmicas',
            'group'        => 'Consultas Dinâmicas',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],

        // Logs
        [
            'name'         => 'log.view',
            'description'  => 'Visualizar logs de atividades',
            'group'        => 'Logs',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'log.delete',
            'description'  => 'Excluir logs de atividades',
            'group'        => 'Logs',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],

        // Dashboards
        [
            'name'         => 'dashboard.view',
            'description'  => 'Visualizar dashboards',
            'group'        => 'Dashboards',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'dashboard.create',
            'description'  => 'Criar dashboards',
            'group'        => 'Dashboards',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'dashboard.edit',
            'description'  => 'Editar dashboards',
            'group'        => 'Dashboards',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'         => 'dashboard.delete',
            'description'  => 'Excluir dashboards',
            'group'        => 'Dashboards',
            'access_level' => PermissionStatus::USER
        ],

        // System Performance
        [
            'name'         => 'system_performance.view',
            'description'  => 'Visualizar métricas de performance do sistema',
            'group'        => 'Performance do Sistema',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'         => 'system_performance.delete',
            'description'  => 'Excluir métricas de performance antigas',
            'group'        => 'Performance do Sistema',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
    ];

    public function run()
    {
        foreach ($this->systemPermissions as $permissionData) {
            $existingPermission = Permission::where('name', $permissionData['name'])->first();
            if ($existingPermission) {
                $existingPermission->update($permissionData);
            } else {
                Permission::create($permissionData);
            }
        }

        $this->command->info('System permissions seeded successfully!');
    }
}
