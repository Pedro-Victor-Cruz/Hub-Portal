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
 * Serviço para gerenciar pedidos na Nuvemshop
 *
 * Operações disponíveis:
 * - list: Listar pedidos (com paginação e filtros)
 * - get: Buscar pedido por ID
 * - update: Atualizar pedido (status, tracking, etc)
 * - cancel: Cancelar pedido
 * - pack: Marcar como empacotado
 * - fulfill: Marcar como enviado/entregue
 *
 * Nota: A API da Nuvemshop não permite criar pedidos via API
 *
 * @see https://tiendanube.github.io/api-documentation/resources/order
 */
class NuvemshopOrderService extends IntegrationService
{
    protected string $serviceName = 'Nuvemshop | Pedidos';
    protected string $description = 'Gerencia pedidos na plataforma Nuvemshop';
    protected ServiceType $serviceType = ServiceType::GENERAL;
    protected IntegrationType $requiredIntegrationType = IntegrationType::NUVEMSHOP;
    protected NuvemshopHttpRequest $httpRequest;

    // Status disponíveis
    private const STATUSES = [
        'open' => 'Aberto',
        'closed' => 'Fechado',
        'cancelled' => 'Cancelado',
    ];

    private const PAYMENT_STATUSES = [
        'pending' => 'Pendente',
        'authorized' => 'Autorizado',
        'paid' => 'Pago',
        'voided' => 'Cancelado',
        'refunded' => 'Reembolsado',
    ];

    private const SHIPPING_STATUSES = [
        'unpacked' => 'Não Empacotado',
        'packed' => 'Empacotado',
        'ready_for_pickup' => 'Pronto para Coleta',
        'shipped' => 'Enviado',
        'delivered' => 'Entregue',
    ];

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
                'list' => $this->listOrders($validatedParams),
                'get' => $this->getOrder($validatedParams),
                'update' => $this->updateOrder($validatedParams),
                'cancel' => $this->cancelOrder($validatedParams),
                'pack' => $this->packOrder($validatedParams),
                'fulfill' => $this->fulfillOrder($validatedParams),
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
     * Lista pedidos com filtros e paginação
     */
    private function listOrders(array $params): ApiResponse
    {
        $filters = [];

        // Filtros de status
        if (!empty($params['status'])) {
            $filters['status'] = $params['status'];
        }

        if (!empty($params['payment_status'])) {
            $filters['payment_status'] = $params['payment_status'];
        }

        if (!empty($params['shipping_status'])) {
            $filters['shipping_status'] = $params['shipping_status'];
        }

        // Filtros de data
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

        // Ordenação
        if (!empty($params['sort_by'])) {
            $filters['sort_by'] = $params['sort_by'];
        }

        // Paginação
        $page = (int)($params['page'] ?? 1);
        $perPage = min((int)($params['per_page'] ?? 50), 200);

        $filters['page'] = $page;
        $filters['per_page'] = $perPage;

        // Busca completa
        if (!empty($params['fetch_all']) && $params['fetch_all'] === 'true') {
            $response = $this->httpRequest->paginate('orders', $filters);
        } else {
            $response = $this->httpRequest->get('orders', $filters);
        }

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao listar pedidos',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $orders = NuvemshopResponseFormatter::formatOrders($response->getData());

        return $this->success($orders, 'Pedidos listados com sucesso', [
            'total' => count($orders),
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Busca um pedido por ID
     */
    private function getOrder(array $params): ApiResponse
    {
        $orderId = $params['order_id'];

        $response = $this->httpRequest->get("orders/{$orderId}");

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao buscar pedido',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $order = NuvemshopResponseFormatter::formatOrder($response->getData());

        return $this->success($order, 'Pedido encontrado com sucesso');
    }

    /**
     * Atualiza um pedido
     */
    private function updateOrder(array $params): ApiResponse
    {
        $orderId = $params['order_id'];
        $orderData = $this->buildOrderData($params);

        $response = $this->httpRequest->put("orders/{$orderId}", $orderData);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao atualizar pedido',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $order = NuvemshopResponseFormatter::formatOrder($response->getData());

        return $this->success($order, 'Pedido atualizado com sucesso', [
            'order_id' => $order['id'],
        ]);
    }

    /**
     * Cancela um pedido
     */
    private function cancelOrder(array $params): ApiResponse
    {
        $orderId = $params['order_id'];

        $data = [
            'status' => 'cancelled'
        ];

        if (!empty($params['cancel_reason'])) {
            $data['cancel_reason'] = $params['cancel_reason'];
        }

        $response = $this->httpRequest->post("orders/{$orderId}/cancel", $data);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao cancelar pedido',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $order = NuvemshopResponseFormatter::formatOrder($response->getData());

        return $this->success($order, 'Pedido cancelado com sucesso', [
            'order_id' => $order['id'],
            'status' => $order['status'],
        ]);
    }

    /**
     * Marca pedido como empacotado
     */
    private function packOrder(array $params): ApiResponse
    {
        $orderId = $params['order_id'];

        $response = $this->httpRequest->post("orders/{$orderId}/pack", []);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao empacotar pedido',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $order = NuvemshopResponseFormatter::formatOrder($response->getData());

        return $this->success($order, 'Pedido marcado como empacotado', [
            'order_id' => $order['id'],
            'shipping_status' => $order['shipping_status'],
        ]);
    }

    /**
     * Marca pedido como enviado/entregue
     */
    private function fulfillOrder(array $params): ApiResponse
    {
        $orderId = $params['order_id'];

        $data = [];

        // Informações de envio
        if (!empty($params['tracking_number'])) {
            $data['tracking_number'] = $params['tracking_number'];
        }

        if (!empty($params['tracking_url'])) {
            $data['tracking_url'] = $params['tracking_url'];
        }

        if (!empty($params['notify_customer'])) {
            $data['notify_customer'] = $params['notify_customer'] === 'true';
        }

        $response = $this->httpRequest->post("orders/{$orderId}/fulfill", $data);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao finalizar envio do pedido',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $order = NuvemshopResponseFormatter::formatOrder($response->getData());

        return $this->success($order, 'Pedido marcado como enviado', [
            'order_id' => $order['id'],
            'shipping_status' => $order['shipping_status'],
            'tracking_number' => $order['tracking_number'],
        ]);
    }

    /**
     * Monta estrutura de dados do pedido
     */
    private function buildOrderData(array $params): array
    {
        $data = [];

        // Campos simples
        if (isset($params['note'])) {
            $data['note'] = $params['note'];
        }

        if (isset($params['owner_note'])) {
            $data['owner_note'] = $params['owner_note'];
        }

        // Status
        if (isset($params['status'])) {
            $data['status'] = $params['status'];
        }

        if (isset($params['payment_status'])) {
            $data['payment_status'] = $params['payment_status'];
        }

        if (isset($params['shipping_status'])) {
            $data['shipping_status'] = $params['shipping_status'];
        }

        // Tracking
        if (isset($params['shipping_tracking_number'])) {
            $data['shipping_tracking_number'] = $params['shipping_tracking_number'];
        }

        if (isset($params['shipping_tracking_url'])) {
            $data['shipping_tracking_url'] = $params['shipping_tracking_url'];
        }

        return NuvemshopResponseFormatter::removeEmptyFields($data);
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
                    'list' => 'Listar Pedidos',
                    'get' => 'Buscar Pedido',
                    'update' => 'Atualizar Pedido',
                    'cancel' => 'Cancelar Pedido',
                    'pack' => 'Empacotar Pedido',
                    'fulfill' => 'Marcar como Enviado',
                ],
                required: true
            )->withLabel('Operação')
                ->withGroup('operation'),

            // ID do pedido
            ServiceParameter::text(
                name: 'order_id',
                description: 'ID do pedido (obrigatório para get/update/cancel/pack/fulfill)'
            )->withLabel('ID do Pedido')
                ->withGroup('identification'),

            // Dados do pedido
            ServiceParameter::text(
                name: 'note',
                description: 'Observações do cliente'
            )->withLabel('Observações')
                ->withGroup('order_data'),

            ServiceParameter::text(
                name: 'owner_note',
                description: 'Observações internas (lojista)'
            )->withLabel('Obs. Internas')
                ->withGroup('order_data'),

            ServiceParameter::select(
                name: 'status',
                options: self::STATUSES
            )->withLabel('Status')
                ->withGroup('order_data'),

            ServiceParameter::select(
                name: 'payment_status',
                options: self::PAYMENT_STATUSES
            )->withLabel('Status Pagamento')
                ->withGroup('order_data'),

            ServiceParameter::select(
                name: 'shipping_status',
                options: self::SHIPPING_STATUSES
            )->withLabel('Status Envio')
                ->withGroup('order_data'),

            // Tracking
            ServiceParameter::text(
                name: 'tracking_number',
                description: 'Código de rastreio'
            )->withLabel('Código Rastreio')
                ->withGroup('shipping'),

            ServiceParameter::url(
                name: 'tracking_url',
                description: 'URL de rastreamento'
            )->withLabel('URL Rastreio')
                ->withGroup('shipping'),

            ServiceParameter::select(
                name: 'notify_customer',
                options: [
                    'true' => 'Sim',
                    'false' => 'Não',
                ],
                defaultValue: 'true',
                description: 'Notificar cliente sobre o envio'
            )->withLabel('Notificar Cliente')
                ->withGroup('shipping'),

            ServiceParameter::text(
                name: 'cancel_reason',
                description: 'Motivo do cancelamento'
            )->withLabel('Motivo Cancelamento')
                ->withGroup('order_data'),

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

            ServiceParameter::select(
                name: 'sort_by',
                options: [
                    'created-at-asc' => 'Data Criação (Crescente)',
                    'created-at-desc' => 'Data Criação (Decrescente)',
                    'updated-at-asc' => 'Data Atualização (Crescente)',
                    'updated-at-desc' => 'Data Atualização (Decrescente)',
                ],
                defaultValue: 'created-at-desc'
            )->withLabel('Ordenar Por')
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

            ServiceParameter::date(
                name: 'updated_at_min',
                description: 'Data mínima de atualização'
            )->withLabel('Atualizado Após')
                ->withGroup('filters'),

            ServiceParameter::date(
                name: 'updated_at_max',
                description: 'Data máxima de atualização'
            )->withLabel('Atualizado Antes')
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

        // Operações que requerem order_id
        $requiresOrderId = ['get', 'update', 'cancel', 'pack', 'fulfill'];

        if (in_array($operation, $requiresOrderId) && empty($params['order_id'])) {
            throw new ServiceValidationException(
                'ID do pedido obrigatório',
                ["O parâmetro 'order_id' é obrigatório para a operação '{$operation}'"]
            );
        }

        return true;
    }
}