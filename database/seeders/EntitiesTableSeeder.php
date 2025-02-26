<?php

namespace Database\Seeders;

use App\Models\Entity;
use App\Models\EntityProperty;
use App\Models\EntityRelationship;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EntitiesTableSeeder extends Seeder
{
    public function run(): void
    {

        // Cria as entidades
        $entities = [
            [
                'entity_name'  => 'User',
                'table_name'  => 'users',
                'entity_label' => 'Usuário'
            ],
            [
                'entity_name'  => 'Portal',
                'table_name'   => 'portals',
                'entity_label' => 'Portal'
            ],
            [
                'entity_name'  => 'Department',
                'table_name'   => 'departments',
                'entity_label' => 'Departamento'
            ],
            [
                'entity_name'  => 'PortalUsers',
                'table_name'   => 'portal_users',
                'entity_label' => 'Usuários do Portal'
            ]
        ];

        Entity::insert($entities);

        // Busca as entidades criadas
        $userEntity = Entity::where('entity_name', 'User')->first();
        $portalEntity = Entity::where('entity_name', 'Portal')->first();
        $departmentEntity = Entity::where('entity_name', 'Department')->first();
        $portalUsersEntity = Entity::where('entity_name', 'PortalUsers')->first();

        // Cria as propriedades das entidades
        $entity_properties = [
            // Proriedades do Usuer
            [
                'entity_id'    => $userEntity->id,
                'field_name'   => 'name',
                'field_type'   => 'string',
                'field_label'  => 'Nome',
                'is_required'  => true,
                'is_unique'    => false,
                'show_in_form' => true
            ],
            [
                'entity_id'    => $userEntity->id,
                'field_name'   => 'email',
                'field_type'   => 'string',
                'field_label'  => 'E-mail',
                'is_required'  => true,
                'is_unique'    => true,
                'show_in_form' => true
            ],
            [
                'entity_id'    => $userEntity->id,
                'field_name'   => 'password',
                'field_type'   => 'string',
                'field_label'  => 'Senha',
                'is_required'  => true,
                'is_unique'    => false,
                'show_in_form' => true
            ],
            // Propriedades do Portal
            [
                'entity_id'    => $portalEntity->id,
                'field_name'   => 'name',
                'field_type'   => 'string',
                'field_label'  => 'Nome',
                'is_required'  => true,
                'is_unique'    => true,
                'show_in_form' => true
            ],
            [
                'entity_id'    => $portalEntity->id,
                'field_name'   => 'slug',
                'field_type'   => 'string',
                'field_label'  => 'Slug',
                'is_required'  => true,
                'is_unique'    => true,
                'show_in_form' => false
            ],
            [
                'entity_id'    => $portalEntity->id,
                'field_name'   => 'phone',
                'field_type'   => 'string',
                'field_label'  => 'Telefone',
                'is_required'  => false,
                'is_unique'    => false,
                'show_in_form' => true
            ],
            [
                'entity_id'    => $portalEntity->id,
                'field_name'   => 'user_id',
                'field_type'   => 'integer',
                'field_label'  => 'Responsável',
                'is_required'  => true,
                'is_unique'    => false,
                'show_in_form' => true
            ],

            // Propriedades do Department
            [
                'entity_id'    => $departmentEntity->id,
                'field_name'   => 'portal_id',
                'field_type'   => 'integer',
                'field_label'  => 'Portal',
                'is_required'  => true,
                'is_unique'    => false,
                'show_in_form' => true
            ],
            [
                'entity_id'    => $departmentEntity->id,
                'field_name'   => 'name',
                'field_type'   => 'string',
                'field_label'  => 'Nome (Departamento)',
                'is_required'  => true,
                'is_unique'    => false,
                'show_in_form' => true
            ],
            [
                'entity_id'    => $departmentEntity->id,
                'field_name'   => 'slug',
                'field_type'   => 'string',
                'field_label'  => 'Descrição',
                'is_required'  => true,
                'is_unique'    => true,
                'show_in_form' => false
            ],
            [
                'entity_id'    => $departmentEntity->id,
                'field_name'   => 'is_default',
                'field_type'   => 'boolean',
                'field_label'  => 'Padrão',
                'is_required'  => true,
                'is_unique'    => false,
                'show_in_form' => true
            ],

            // Propriedades do PortalUsers
            [
                'entity_id'    => $portalUsersEntity->id,
                'field_name'   => 'portal_id',
                'field_type'   => 'integer',
                'field_label'  => 'Portal',
                'is_required'  => true,
                'is_unique'    => false,
                'show_in_form' => true
            ],
            [
                'entity_id'    => $portalUsersEntity->id,
                'field_name'   => 'user_id',
                'field_type'   => 'integer',
                'field_label'  => 'Usuário',
                'is_required'  => true,
                'is_unique'    => false,
                'show_in_form' => true
            ]
        ];

        EntityProperty::insert($entity_properties);

        // Cria os relacionamentos entre as entidades
        $entity_relationships = [

            // Relação entre Portal e User
            [
                'parent_entity_id' => $portalEntity->id,
                'child_entity_id'  => $userEntity->id,
                'parent_field_name' => 'user_id',
                'child_field_name'  => 'id',
                'relationship_type' => 'belongsTo'
            ],

            // Relação entre Department e Portal
            [
                'parent_entity_id' => $departmentEntity->id,
                'child_entity_id'  => $portalEntity->id,
                'parent_field_name' => 'portal_id',
                'child_field_name'  => 'id',
                'relationship_type' => 'belongsTo'
            ],

            // Relação entre PortalUsers e Portal
            [
                'parent_entity_id' => $portalUsersEntity->id,
                'child_entity_id'  => $portalEntity->id,
                'parent_field_name' => 'portal_id',
                'child_field_name'  => 'id',
                'relationship_type' => 'belongsTo'
            ],

            // Relação entre PortalUsers e User
            [
                'parent_entity_id' => $portalUsersEntity->id,
                'child_entity_id'  => $userEntity->id,
                'parent_field_name' => 'user_id',
                'child_field_name'  => 'id',
                'relationship_type' => 'belongsTo'
            ]
        ];

        EntityRelationship::insert($entity_relationships);
    }
}