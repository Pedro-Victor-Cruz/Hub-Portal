<?php

namespace App\Modules\Data;

use App\Services\BaseService;
use App\Services\EntityService;
use Illuminate\Support\Facades\DB;

class Load extends BaseService
{
    public $serviceName = 'Data.load';

    /**
     * @var EntityService
     */
    protected $entityService;

    public function __construct()
    {
    }

    /**
     * Define as regras de validação para o requestBody.
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            'rootEntity' => 'required|string', // Nome da entidade principal
            'entities' => 'nullable|array', // Lista de entidades/relacionamentos (opcional)
            'entities.*.entity' => 'nullable|string', // Nome da entidade (opcional, vazio refere-se à rootEntity)
            'entities.*.fields' => 'nullable|string', // Campos a serem retornados (opcional, default *)
        ];
    }

    /**
     * Executa o serviço de carregamento de dados.
     *
     * @param array $requestBody
     * @return array
     * @throws \Exception
     */
    public function execute(array $requestBody): array
    {
        // Valida o requestBody
        $this->validateRequestBody($requestBody);

        $this->entityService = new EntityService();

        // Busca a entidade principal
        $rootEntity = $this->entityService->getEntity($requestBody['rootEntity']);

        // Monta a consulta SQL
        $sql = $this->buildSqlQuery($rootEntity, $requestBody['entities'] ?? []);

        try {
            // Executa a consulta e retorna os dados
            $data = DB::select($sql);
        } catch (\Exception $e) {
            // Retorna o erro com a query executada
            return $this->formatResponse('error', $e->getMessage(), ['sql' => $sql]);
        }

        // Formata os dados no padrão esperado
        $formattedData = $this->formatData($data, $requestBody['entities'] ?? []);

        return $this->formatResponse('success', 'Dados carregados com sucesso', $formattedData);
    }

    /**
     * Monta a consulta SQL como uma string.
     *
     * @param mixed $rootEntity
     * @param array $entities
     * @return string
     * @throws \Exception
     */
    private function buildSqlQuery($rootEntity, array $entities): string
    {
        // Inicia a consulta com a entidade principal
        $sql = "SELECT ";

        // Adiciona os campos da entidade principal
        $rootFields = [];
        foreach ($entities as $entityConfig) {
            if (empty($entityConfig['entity'])) {
                $rootFields = array_merge($rootFields, explode(',', $entityConfig['fields'] ?? '*'));
            }
        }

        if (empty($rootFields)) {
            $rootFields = ['*'];
        }

        // Adiciona os campos da entidade principal ao SELECT
        $sql .= implode(', ', array_map(function ($field) {
            return 'root.' . trim($field);
        }, $rootFields));

        // Adiciona joins e campos para entidades relacionadas
        $joins = [];
        $selectFields = [];
        $processedEntities = []; // Armazena entidades já processadas para evitar duplicação

        foreach ($entities as $entityConfig) {
            if (!empty($entityConfig['entity'])) {
                $entityPath = explode('.', $entityConfig['entity']);
                $currentAlias = 'root';
                $currentEntity = $rootEntity;

                foreach ($entityPath as $entityName) {
                    $relatedEntity = $this->entityService->getEntity($entityName);

                    // Verifica se há um relacionamento entre as entidades
                    $relationship = $currentEntity->relationships()
                        ->where('child_entity_id', $relatedEntity->id)
                        ->first();

                    if (!$relationship) {
                        throw new \Exception("Relacionamento não encontrado para a entidade: {$entityName}");
                    }

                    // Define o alias para a entidade relacionada
                    $alias = strtolower($entityName);

                    // Verifica se a entidade já foi processada
                    if (!in_array($alias, $processedEntities)) {
                        // Adiciona o INNER JOIN
                        $joins[] = "INNER JOIN {$relatedEntity->table_name} AS {$alias} ON {$currentAlias}.{$relationship->parent_field_name} = {$alias}.{$relationship->child_field_name}";
                        $processedEntities[] = $alias; // Marca a entidade como processada
                    }

                    // Adiciona os campos da entidade relacionada ao SELECT
                    $fields = explode(',', $entityConfig['fields'] ?? '*');
                    foreach ($fields as $field) {
                        $selectFields[] = "{$alias}." . trim($field) . " AS " . str_replace('.', '_', $entityConfig['entity']) . "_" . trim($field);
                    }

                    // Atualiza a entidade e o alias para o próximo nível de aninhamento
                    $currentEntity = $relatedEntity;
                    $currentAlias = $alias;
                }
            }
        }

        // Adiciona os campos das entidades relacionadas ao SELECT
        if (!empty($selectFields)) {
            $sql .= ', ' . implode(', ', $selectFields);
        }

        // Adiciona a cláusula FROM
        $sql .= " FROM {$rootEntity->table_name} AS root";

        // Adiciona os INNER JOIN
        if (!empty($joins)) {
            $sql .= ' ' . implode(' ', $joins);
        }

        return $sql;
    }

    /**
     * Formata os dados no padrão esperado.
     *
     * @param array $data
     * @param array $entities
     * @return array
     */
    private function formatData(array $data, array $entities): array
    {
        return array_map(function ($item) {
            return (array) $item;
        }, $data);
    }
}