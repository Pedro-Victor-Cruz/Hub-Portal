<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use App\Models\Entity;
use App\Models\EntityRelationship;
use Illuminate\Validation\ValidationException;

class EntityService
{
    // Busca a entidade e suas propriedades
    public function getEntity($entityName)
    {
        $entity = Entity::where('entity_name', $entityName)
            ->with(['properties', 'relationships'])
            ->first();
        if (!$entity) {
            throw new \Exception('Entidade ('. $entityName .') não encontrada');
        }
        return $entity;
    }

    // Valida os dados com base nas propriedades da entidade
    public function validateData($entity, $data): void
    {
        $rules = [];
        foreach ($entity->properties as $property) {
            $rule = [];
            if ($property->is_required) {
                $rule[] = 'required';
            }
            if ($property->is_unique) {
                $rule[] = 'unique:' . $entity->table_name . ',' . $property->field_name;
            }
            $rules[$property->field_name] = implode('|', $rule);
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }


    // Valida os dados de relacionamento
    public function validateRelationshipData($relationship, $data): void
    {
        $rules = [
            $relationship->parent_field_name => 'required|exists:' . $relationship->childEntity->table_name . ',' . $relationship->child_field_name,
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}