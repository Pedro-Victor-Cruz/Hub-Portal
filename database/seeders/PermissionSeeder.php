<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Utils\PermissionStatus;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private array $systemPermissions = [
        // Usuários
        [
            'name'              => 'user.view',
            'description'       => 'Visualizar usuários',
            'group'             => 'Usuários',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'user.create',
            'description'       => 'Criar usuários',
            'group'             => 'Usuários',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'user.edit',
            'description'       => 'Editar usuários',
            'group'             => 'Usuários',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'user.delete',
            'description'       => 'Excluir usuários',
            'group'             => 'Usuários',
            'access_level' => PermissionStatus::USER
        ],

        // Empresas
        [
            'name'              => 'company.view',
            'description'       => 'Visualizar empresas',
            'group'             => 'Empresas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.create',
            'description'       => 'Criar empresas',
            'group'             => 'Empresas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.edit',
            'description'       => 'Editar empresas',
            'group'             => 'Empresas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.delete',
            'description'       => 'Excluir empresas',
            'group'             => 'Empresas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],


        // ERP Settings
        [
            'name'              => 'company.erp_settings.view',
            'description'       => 'Visualizar configurações de ERP da empresa',
            'group'             => 'ERP Configurações',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.erp_settings.create',
            'description'       => 'Criar configurações de ERP da empresa',
            'group'             => 'ERP Configurações',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.erp_settings.edit',
            'description'       => 'Editar configurações de ERP da empresa',
            'group'             => 'ERP Configurações',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.erp_settings.delete',
            'description'       => 'Excluir configurações de ERP da empresa',
            'group'             => 'ERP Configurações',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],

        // Permissions
        [
            'name'              => 'permission.view',
            'description'       => 'Visualizar permissões',
            'group'             => 'Permissões',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission.create',
            'description'       => 'Criar permissões',
            'group'             => 'Permissões',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'permission.edit',
            'description'       => 'Editar permissões',
            'group'             => 'Permissões',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'permission.delete',
            'description'       => 'Excluir permissões',
            'group'             => 'Permissões',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'permission.assign',
            'description'       => 'Atribuir permissões específicas a usuários',
            'group'             => 'Permissões',
            'access_level' => PermissionStatus::USER
        ],

        // Permission Groups
        [
            'name'              => 'permission_group.view',
            'description'       => 'Visualizar grupos de permissões',
            'group'             => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission_group.create',
            'description'       => 'Criar grupos de permissões',
            'group'             => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission_group.edit',
            'description'       => 'Editar grupos de permissões',
            'group'             => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission_group.delete',
            'description'       => 'Excluir grupos de permissões',
            'group'             => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission_group.assign',
            'description'       => 'Atribuir grupos de permissões a usuários',
            'group'             => 'Grupos',
            'access_level' => PermissionStatus::USER
        ],

        // Parâmetros
        [
            'name'              => 'parameter.view',
            'description'       => 'Visualizar parâmetros do sistema',
            'group'             => 'Parâmetros',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'parameter.create',
            'description'       => 'Criar parâmetros do sistema',
            'group'             => 'Parâmetros',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'              => 'parameter.edit',
            'description'       => 'Editar parâmetros do sistema',
            'group'             => 'Parâmetros',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'parameter.delete',
            'description'       => 'Excluir parâmetros do sistema',
            'group'             => 'Parâmetros',
            'access_level' => PermissionStatus::ADMINISTRATOR
        ],
        [
            'name'              => 'parameter.company.update',
            'description'       => 'Atualizar valores de parâmetros específicos por empresa',
            'group'             => 'Parâmetros',
            'access_level' => PermissionStatus::USER
        ],

        // System
        [
            'name'              => 'system.settings',
            'description'       => 'Gerenciar configurações do sistema',
            'group'             => 'Sistema',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.view_other',
            'description'       => 'Visualizar dados de outras empresas',
            'group'             => 'Sistema',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.edit_other',
            'description'       => 'Editar dados de outras empresas',
            'group'             => 'Sistema',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
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
