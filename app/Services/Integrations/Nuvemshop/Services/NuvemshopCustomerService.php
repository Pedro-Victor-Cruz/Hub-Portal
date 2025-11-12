<?php

namespace App\Services\Integrations\Nuvemshop\Services;

use App\Enums\IntegrationType;
use App\Enums\ServiceType;
use App\Exceptions\Services\ServiceValidationException;
use App\Models\Company;
use App\Services\Core\ApiResponse;
use App\Services\Core\Integration\IntegrationService;
use App\Services\Integrations\Nuvemshop\NuvemshopHttpRequest;
use App\Services\Parameter\ServiceParameter;
use App\Services\Utils\ResponseFormatters\NuvemshopResponseFormatter;
use Exception;

/**
 * Serviço para gerenciar clientes na Nuvemshop
 *
 * Operações disponíveis:
 * - list: Listar clientes (com paginação)
 * - get: Buscar cliente por ID
 * - create: Criar novo cliente
 * - update: Atualizar cliente existente
 * - delete: Deletar cliente
 * - search: Buscar cliente por email
 *
 * @see https://tiendanube.github.io/api-documentation/resources/customer
 */
class NuvemshopCustomerService extends IntegrationService
{
    protected string $serviceName = 'Nuvemshop | Clientes';
    protected string $description = 'Gerencia clientes na plataforma Nuvemshop';
    protected ServiceType $serviceType = ServiceType::GENERAL;
    protected IntegrationType $requiredIntegrationType = IntegrationType::NUVEMSHOP;
    protected NuvemshopHttpRequest $httpRequest;

    public function __construct(?Company $company = null)
    {
        parent::__construct($company);
        $this->httpRequest = new NuvemshopHttpRequest($this->integration);
    }

    /**
     * Executa a operação solicitada
     */
    protected function performService(array $params): ApiResponse
    {
        try {
            $validatedParams = $this->validateAndSanitizeParams($params);
            $operation = $validatedParams['operation'];

            return match ($operation) {
                'list' => $this->listCustomers($validatedParams),
                'get' => $this->getCustomer($validatedParams),
                'create' => $this->createCustomer($validatedParams),
                'update' => $this->updateCustomer($validatedParams),
                'delete' => $this->deleteCustomer($validatedParams),
                'search' => $this->searchCustomer($validatedParams),
                default => $this->error('Operação não suportada', ["Operação '{$operation}' não existe"])
            };

        } catch (ServiceValidationException $e) {
            return $this->error('Parâmetros inválidos', $e->getValidationErrors());
        } catch (Exception $e) {
            return $this->error(
                'Erro ao processar operação',
                [$e->getMessage()],
                [
                    'line' => $e->getLine(),
                    'file' => basename($e->getFile()),
                ]
            );
        }
    }

    /**
     * Lista clientes com filtros e paginação
     */
    private function listCustomers(array $params): ApiResponse
    {
        $filters = [];

        // Filtros disponíveis
        if (!empty($params['created_at_min'])) {
            $filters['created_at_min'] = $params['created_at_min'];
        }

        if (!empty($params['created_at_max'])) {
            $filters['created_at_max'] = $params['created_at_max'];
        }

        if (!empty($params['updated_at_min'])) {
            $filters['updated_at_min'] = $params['updated_at_min'];
        }

        if (!empty($params['updated_at_max'])) {
            $filters['updated_at_max'] = $params['updated_at_max'];
        }

        // Paginação
        $page = (int)($params['page'] ?? 1);
        $perPage = min((int)($params['per_page'] ?? 50), 200);

        $filters['page'] = $page;
        $filters['per_page'] = $perPage;

        // Busca completa
        if (!empty($params['fetch_all']) && $params['fetch_all'] === 'true') {
            $response = $this->httpRequest->paginate('customers', $filters);
        } else {
            $response = $this->httpRequest->get('customers', $filters);
        }

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao listar clientes',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $customers = NuvemshopResponseFormatter::formatCustomers($response->getData());

        return $this->success($customers, 'Clientes listados com sucesso', [
            'total' => count($customers),
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Busca um cliente por ID
     */
    private function getCustomer(array $params): ApiResponse
    {
        $customerId = $params['customer_id'];

        $response = $this->httpRequest->get("customers/{$customerId}");

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao buscar cliente',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $customer = NuvemshopResponseFormatter::formatCustomer($response->getData());

        return $this->success($customer, 'Cliente encontrado com sucesso');
    }

    /**
     * Busca cliente por email
     */
    private function searchCustomer(array $params): ApiResponse
    {
        $email = $params['email'];

        $response = $this->httpRequest->get('customers/search', [
            'query' => $email
        ]);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao buscar cliente',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $customers = NuvemshopResponseFormatter::formatCustomers($response->getData());

        // Se encontrou apenas um, retorna o cliente
        if (count($customers) === 1) {
            return $this->success($customers[0], 'Cliente encontrado com sucesso');
        }

        // Se encontrou múltiplos ou nenhum
        return $this->success($customers, 'Busca concluída', [
            'total_found' => count($customers),
            'query' => $email
        ]);
    }

    /**
     * Cria um novo cliente
     */
    private function createCustomer(array $params): ApiResponse
    {
        $customerData = $this->buildCustomerData($params);

        $response = $this->httpRequest->post('customers', $customerData);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao criar cliente',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $customer = NuvemshopResponseFormatter::formatCustomer($response->getData());

        return $this->success($customer, 'Cliente criado com sucesso', [
            'customer_id' => $customer['id'],
        ]);
    }

    /**
     * Atualiza um cliente existente
     */
    private function updateCustomer(array $params): ApiResponse
    {
        $customerId = $params['customer_id'];
        $customerData = $this->buildCustomerData($params);

        $response = $this->httpRequest->put("customers/{$customerId}", $customerData);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao atualizar cliente',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $customer = NuvemshopResponseFormatter::formatCustomer($response->getData());

        return $this->success($customer, 'Cliente atualizado com sucesso', [
            'customer_id' => $customer['id'],
        ]);
    }

    /**
     * Deleta um cliente
     */
    private function deleteCustomer(array $params): ApiResponse
    {
        $customerId = $params['customer_id'];

        $response = $this->httpRequest->delete("customers/{$customerId}");

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao deletar cliente',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        return $this->success(['deleted' => true], 'Cliente deletado com sucesso', [
            'customer_id' => $customerId,
        ]);
    }

    /**
     * Monta estrutura de dados do cliente
     */
    private function buildCustomerData(array $params): array
    {
        $data = [];

        // Campos simples
        $simpleFields = [
            'email', 'name', 'phone', 'identification',
            'accepts_marketing', 'note'
        ];

        foreach ($simpleFields as $field) {
            if (isset($params[$field])) {
                $data[$field] = $params[$field];
            }
        }

        // Default address
        if (isset($params['default_address'])) {
            $address = is_array($params['default_address'])
                ? $params['default_address']
                : json_decode($params['default_address'], true);

            $data['default_address'] = $this->buildAddress($address);
        } elseif (isset($params['address'])) {
            // Permite passar endereço direto
            $data['default_address'] = $this->buildAddress($params);
        }

        return NuvemshopResponseFormatter::removeEmptyFields($data);
    }

    /**
     * Monta estrutura de endereço
     */
    private function buildAddress(array $params): array
    {
        $address = [];

        $addressFields = [
            'address', 'number', 'complement', 'neighborhood',
            'city', 'province', 'zipcode', 'country', 'phone'
        ];

        foreach ($addressFields as $field) {
            if (isset($params[$field])) {
                $address[$field] = $params[$field];
            }
        }

        return $address;
    }

    /**
     * Configura os parâmetros do serviço
     */
    protected function configureParameters(): void
    {
        $this->parameterManager->addMany([
            // Operação
            ServiceParameter::select(
                name: 'operation',
                options: [
                    'list' => 'Listar Clientes',
                    'get' => 'Buscar Cliente',
                    'search' => 'Buscar por Email',
                    'create' => 'Criar Cliente',
                    'update' => 'Atualizar Cliente',
                    'delete' => 'Deletar Cliente',
                ],
                required: true
            )->withLabel('Operação')
                ->withGroup('operation'),

            // ID do cliente (para get, update, delete)
            ServiceParameter::text(
                name: 'customer_id',
                description: 'ID do cliente (obrigatório para get/update/delete)'
            )->withLabel('ID do Cliente')
                ->withGroup('identification'),

            // Dados do cliente
            ServiceParameter::email(
                name: 'email',
                description: 'Email do cliente'
            )->withLabel('Email')
                ->withGroup('customer_data'),

            ServiceParameter::text(
                name: 'name',
                description: 'Nome completo'
            )->withLabel('Nome')
                ->withGroup('customer_data'),

            ServiceParameter::text(
                name: 'phone',
                description: 'Telefone'
            )->withLabel('Telefone')
                ->withGroup('customer_data'),

            ServiceParameter::text(
                name: 'identification',
                description: 'CPF/CNPJ'
            )->withLabel('CPF/CNPJ')
                ->withGroup('customer_data'),

            ServiceParameter::select(
                name: 'accepts_marketing',
                options: [
                    'true' => 'Sim',
                    'false' => 'Não',
                ],
                defaultValue: 'false'
            )->withLabel('Aceita Marketing')
                ->withGroup('customer_data'),

            ServiceParameter::text(
                name: 'note',
                description: 'Observações sobre o cliente'
            )->withLabel('Observações')
                ->withGroup('customer_data'),

            // Endereço
            ServiceParameter::text(
                name: 'address',
                description: 'Logradouro'
            )->withLabel('Endereço')
                ->withGroup('address'),

            ServiceParameter::text(
                name: 'number',
                description: 'Número'
            )->withLabel('Número')
                ->withGroup('address'),

            ServiceParameter::text(
                name: 'complement',
                description: 'Complemento'
            )->withLabel('Complemento')
                ->withGroup('address'),

            ServiceParameter::text(
                name: 'neighborhood',
                description: 'Bairro'
            )->withLabel('Bairro')
                ->withGroup('address'),

            ServiceParameter::text(
                name: 'city',
                description: 'Cidade'
            )->withLabel('Cidade')
                ->withGroup('address'),

            ServiceParameter::text(
                name: 'province',
                description: 'Estado (sigla)'
            )->withLabel('Estado')
                ->withGroup('address'),

            ServiceParameter::text(
                name: 'zipcode',
                description: 'CEP'
            )->withLabel('CEP')
                ->withGroup('address'),

            ServiceParameter::text(
                name: 'country',
                defaultValue: 'BR',
                description: 'País (código ISO)'
            )->withLabel('País')
                ->withGroup('address'),

            // Filtros para listagem
            ServiceParameter::number(
                name: 'page',
                defaultValue: 1
            )->withLabel('Página')
                ->withGroup('filters'),

            ServiceParameter::number(
                name: 'per_page',
                defaultValue: 50,
            )->withLabel('Itens por Página')
                ->withGroup('filters'),

            ServiceParameter::select(
                name: 'fetch_all',
                options: [
                    'false' => 'Não',
                    'true' => 'Sim',
                ],
                defaultValue: 'false'
            )->withLabel('Buscar Tudo')
                ->withGroup('filters'),

            ServiceParameter::date(
                name: 'created_at_min',
                description: 'Data mínima de criação'
            )->withLabel('Criado Após')
                ->withGroup('filters'),

            ServiceParameter::date(
                name: 'created_at_max',
                description: 'Data máxima de criação'
            )->withLabel('Criado Antes')
                ->withGroup('filters'),
        ]);
    }

    /**
     * Valida parâmetros específicos
     */
    public function validateParams(array $params): bool
    {
        parent::validateParams($params);

        $operation = $params['operation'] ?? null;

        switch ($operation) {
            case 'get':
            case 'update':
            case 'delete':
                if (empty($params['customer_id'])) {
                    throw new ServiceValidationException(
                        'ID do cliente obrigatório',
                        ["O parâmetro 'customer_id' é obrigatório para a operação '{$operation}'"]
                    );
                }
                break;

            case 'search':
                if (empty($params['email'])) {
                    throw new ServiceValidationException(
                        'Email obrigatório',
                        ["O parâmetro 'email' é obrigatório para buscar cliente"]
                    );
                }
                break;

            case 'create':
                if (empty($params['email'])) {
                    throw new ServiceValidationException(
                        'Email obrigatório',
                        ["O parâmetro 'email' é obrigatório para criar um cliente"]
                    );
                }
                break;
        }

        return true;
    }
}