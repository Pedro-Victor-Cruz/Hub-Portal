<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Utils\PermissionStatus;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    private array $systemPermissions = [
        // Users
        [
            'name'              => 'user.view',
            'description'       => 'Visualizar usuários',
            'group'             => 'users',
            'group_description' => 'Gerenciamento de usuários do sistema',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'user.create',
            'description'       => 'Criar usuários',
            'group'             => 'users',
            'group_description' => 'Gerenciamento de usuários do sistema',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'user.edit',
            'description'       => 'Editar usuários',
            'group'             => 'users',
            'group_description' => 'Gerenciamento de usuários do sistema',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'user.delete',
            'description'       => 'Excluir usuários',
            'group'             => 'users',
            'group_description' => 'Gerenciamento de usuários do sistema',
            'access_level' => PermissionStatus::USER
        ],

        // Companies
        [
            'name'              => 'company.view',
            'description'       => 'Visualizar empresas',
            'group'             => 'companies',
            'group_description' => 'Gerenciamento de empresas cadastradas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.create',
            'description'       => 'Criar empresas',
            'group'             => 'companies',
            'group_description' => 'Gerenciamento de empresas cadastradas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.edit',
            'description'       => 'Editar empresas',
            'group'             => 'companies',
            'group_description' => 'Gerenciamento de empresas cadastradas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.delete',
            'description'       => 'Excluir empresas',
            'group'             => 'companies',
            'group_description' => 'Gerenciamento de empresas cadastradas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.assign_user',
            'description'       => 'Atribuir usuários a empresas',
            'group'             => 'companies',
            'group_description' => 'Gerenciamento de empresas cadastradas',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],


        // ERP Settings
        [
            'name'              => 'company.erp_settings.view',
            'description'       => 'Visualizar configurações de ERP da empresa',
            'group'             => 'company_erp_settings',
            'group_description' => 'Configurações de ERP específicas por empresa',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.erp_settings.create',
            'description'       => 'Criar configurações de ERP da empresa',
            'group'             => 'company_erp_settings',
            'group_description' => 'Configurações de ERP específicas por empresa',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.erp_settings.edit',
            'description'       => 'Editar configurações de ERP da empresa',
            'group'             => 'company_erp_settings',
            'group_description' => 'Configurações de ERP específicas por empresa',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'company.erp_settings.delete',
            'description'       => 'Excluir configurações de ERP da empresa',
            'group'             => 'company_erp_settings',
            'group_description' => 'Configurações de ERP específicas por empresa',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],

        // Permissions
        [
            'name'              => 'permission.view',
            'description'       => 'Visualizar permissões',
            'group'             => 'permissions',
            'group_description' => 'Gerenciamento de permissões do sistema',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission.create',
            'description'       => 'Criar permissões',
            'group'             => 'permissions',
            'group_description' => 'Gerenciamento de permissões do sistema',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'permission.edit',
            'description'       => 'Editar permissões',
            'group'             => 'permissions',
            'group_description' => 'Gerenciamento de permissões do sistema',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'permission.delete',
            'description'       => 'Excluir permissões',
            'group'             => 'permissions',
            'group_description' => 'Gerenciamento de permissões do sistema',
            'access_level' => PermissionStatus::SUPER_ADMINISTRATOR
        ],
        [
            'name'              => 'permission.assign',
            'description'       => 'Atribuir permissões específicas a usuários',
            'group'             => 'permissions',
            'group_description' => 'Gerenciamento de permissões do sistema',
            'access_level' => PermissionStatus::USER
        ],

        // Permission Groups
        [
            'name'              => 'permission_group.view',
            'description'       => 'Visualizar grupos de permissões',
            'group'             => 'permission_groups',
            'group_description' => 'Gerenciamento de grupos de permissões',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission_group.create',
            'description'       => 'Criar grupos de permissões',
            'group'             => 'permission_groups',
            'group_description' => 'Gerenciamento de grupos de permissões',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission_group.edit',
            'description'       => 'Editar grupos de permissões',
            'group'             => 'permission_groups',
            'group_description' => 'Gerenciamento de grupos de permissões',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission_group.delete',
            'description'       => 'Excluir grupos de permissões',
            'group'             => 'permission_groups',
            'group_description' => 'Gerenciamento de grupos de permissões',
            'access_level' => PermissionStatus::USER
        ],
        [
            'name'              => 'permission_group.assign',
            'description'       => 'Atribuir grupos de permissões a usuários',
            'group'             => 'permission_groups',
            'group_description' => 'Gerenciamento de grupos de permissões',
            'access_level' => PermissionStatus::USER
        ],

        // System
        [
            'name'              => 'system.settings',
            'description'       => 'Gerenciar configurações do sistema',
            'group'             => 'system',
            'group_description' => 'Configurações gerais do sistema',
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
