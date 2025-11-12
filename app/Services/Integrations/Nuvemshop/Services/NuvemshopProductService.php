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
 * Serviço para gerenciar produtos na Nuvemshop
 *
 * Operações disponíveis:
 * - list: Listar produtos (com paginação)
 * - get: Buscar produto por ID
 * - create: Criar novo produto
 * - update: Atualizar produto existente
 * - delete: Deletar produto
 *
 * @see https://tiendanube.github.io/api-documentation/resources/product
 */
class NuvemshopProductService extends IntegrationService
{
    protected string $serviceName = 'Nuvemshop | Produtos';
    protected string $description = 'Gerencia produtos na plataforma Nuvemshop';
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
                'list' => $this->listProducts($validatedParams),
                'get' => $this->getProduct($validatedParams),
                'create' => $this->createProduct($validatedParams),
                'update' => $this->updateProduct($validatedParams),
                'delete' => $this->deleteProduct($validatedParams),
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
     * Lista produtos com filtros e paginação
     */
    private function listProducts(array $params): ApiResponse
    {
        $filters = [];

        // Filtros disponíveis
        if (!empty($params['published'])) {
            $filters['published'] = $params['published'] === 'true';
        }

        if (!empty($params['free_shipping'])) {
            $filters['free_shipping'] = $params['free_shipping'] === 'true';
        }

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
        $perPage = min((int)($params['per_page'] ?? 50), 200); // Máx 200

        $filters['page'] = $page;
        $filters['per_page'] = $perPage;

        // Se solicitou busca completa (todas as páginas)
        if (!empty($params['fetch_all']) && $params['fetch_all'] === 'true') {
            $response = $this->httpRequest->paginate('products', $filters);
        } else {
            $response = $this->httpRequest->get('products', $filters);
        }

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao listar produtos',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $products = NuvemshopResponseFormatter::formatProducts($response->getData());

        return $this->success($products, 'Produtos listados com sucesso', [
            'total' => count($products),
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Busca um produto por ID
     */
    private function getProduct(array $params): ApiResponse
    {
        $productId = $params['product_id'];

        $response = $this->httpRequest->get("products/{$productId}");

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao buscar produto',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $product = NuvemshopResponseFormatter::formatProduct($response->getData());

        return $this->success($product, 'Produto encontrado com sucesso');
    }

    /**
     * Cria um novo produto
     */
    private function createProduct(array $params): ApiResponse
    {
        $productData = $this->buildProductData($params);

        $response = $this->httpRequest->post('products', $productData);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao criar produto',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $product = NuvemshopResponseFormatter::formatProduct($response->getData());

        return $this->success($product, 'Produto criado com sucesso', [
            'product_id' => $product['id'],
        ]);
    }

    /**
     * Atualiza um produto existente
     */
    private function updateProduct(array $params): ApiResponse
    {
        $productId = $params['product_id'];
        $productData = $this->buildProductData($params);

        $response = $this->httpRequest->put("products/{$productId}", $productData);

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao atualizar produto',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        $product = NuvemshopResponseFormatter::formatProduct($response->getData());

        return $this->success($product, 'Produto atualizado com sucesso', [
            'product_id' => $product['id'],
        ]);
    }

    /**
     * Deleta um produto
     */
    private function deleteProduct(array $params): ApiResponse
    {
        $productId = $params['product_id'];

        $response = $this->httpRequest->delete("products/{$productId}");

        if (!$response->isSuccess()) {
            return $this->error(
                'Erro ao deletar produto',
                $response->getErrors(),
                $response->getMetadata()
            );
        }

        return $this->success(['deleted' => true], 'Produto deletado com sucesso', [
            'product_id' => $productId,
        ]);
    }

    /**
     * Monta estrutura de dados do produto
     */
    private function buildProductData(array $params): array
    {
        $data = [];

        // Campos multilíngue (sempre em pt por padrão)
        if (isset($params['name'])) {
            $data['name'] = ['pt' => $params['name']];
        }

        if (isset($params['description'])) {
            $data['description'] = ['pt' => $params['description']];
        }

        // Campos simples
        $simpleFields = [
            'handle', 'published', 'free_shipping', 'video_url',
            'seo_title', 'seo_description', 'brand', 'canonical_url'
        ];

        foreach ($simpleFields as $field) {
            if (isset($params[$field])) {
                $data[$field] = $params[$field];
            }
        }

        // Categories
        if (isset($params['categories'])) {
            $data['categories'] = is_array($params['categories'])
                ? $params['categories']
                : json_decode($params['categories'], true);
        }

        // Images
        if (isset($params['images'])) {
            $data['images'] = is_array($params['images'])
                ? $params['images']
                : json_decode($params['images'], true);
        }

        // Variants
        if (isset($params['variants'])) {
            $variants = is_array($params['variants'])
                ? $params['variants']
                : json_decode($params['variants'], true);

            $data['variants'] = $variants;
        } else {
            // Se não forneceu variants, cria uma variante padrão
            $variant = [];

            $variantFields = [
                'sku', 'barcode', 'price', 'compare_at_price',
                'cost', 'stock', 'weight', 'width', 'height', 'depth'
            ];

            foreach ($variantFields as $field) {
                if (isset($params[$field])) {
                    $variant[$field] = $params[$field];
                }
            }

            if (!empty($variant)) {
                $data['variants'] = [$variant];
            }
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
                    'list' => 'Listar Produtos',
                    'get' => 'Buscar Produto',
                    'create' => 'Criar Produto',
                    'update' => 'Atualizar Produto',
                    'delete' => 'Deletar Produto',
                ],
                required: true
            )->withLabel('Operação')
                ->withGroup('operation'),

            // ID do produto (para get, update, delete)
            ServiceParameter::text(
                name: 'product_id',
                description: 'ID do produto (obrigatório para get/update/delete)'
            )->withLabel('ID do Produto')
                ->withGroup('identification'),

            // Dados do produto
            ServiceParameter::text(
                name: 'name',
                description: 'Nome do produto'
            )->withLabel('Nome')
                ->withGroup('product_data'),

            ServiceParameter::text(
                name: 'description',
                description: 'Descrição do produto'
            )->withLabel('Descrição')
                ->withGroup('product_data'),

            ServiceParameter::text(
                name: 'sku',
                description: 'SKU do produto'
            )->withLabel('SKU')
                ->withGroup('product_data'),

            ServiceParameter::number(
                name: 'price',
                description: 'Preço de venda'
            )->withLabel('Preço')
                ->withGroup('product_data'),

            ServiceParameter::number(
                name: 'compare_at_price',
                description: 'Preço "de" (comparação)'
            )->withLabel('Preço Comparação')
                ->withGroup('product_data'),

            ServiceParameter::number(
                name: 'cost',
                description: 'Custo do produto'
            )->withLabel('Custo')
                ->withGroup('product_data'),

            ServiceParameter::number(
                name: 'stock',
                description: 'Quantidade em estoque'
            )->withLabel('Estoque')
                ->withGroup('product_data'),

            ServiceParameter::number(
                name: 'weight',
                description: 'Peso em gramas'
            )->withLabel('Peso (g)')
                ->withGroup('product_data'),

            ServiceParameter::select(
                name: 'published',
                options: [
                    'true' => 'Sim',
                    'false' => 'Não',
                ],
                defaultValue: 'true'
            )->withLabel('Publicado')
                ->withGroup('product_data'),

            ServiceParameter::select(
                name: 'free_shipping',
                options: [
                    'true' => 'Sim',
                    'false' => 'Não',
                ],
                defaultValue: 'false'
            )->withLabel('Frete Grátis')
                ->withGroup('product_data'),

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
                defaultValue: 'false',
                description: 'Buscar todas as páginas automaticamente'
            )->withLabel('Buscar Tudo')
                ->withGroup('filters'),

            // Campos JSON complexos
            ServiceParameter::object(
                name: 'categories',
                description: 'Array de IDs de categorias: [123, 456]'
            )->withLabel('Categorias')
                ->withGroup('advanced'),

            ServiceParameter::object(
                name: 'images',
                description: 'Array de imagens: [{"src": "url"}]'
            )->withLabel('Imagens')
                ->withGroup('advanced'),

            ServiceParameter::object(
                name: 'variants',
                description: 'Array de variantes do produto'
            )->withLabel('Variantes')
                ->withGroup('advanced'),
        ]);
    }

    /**
     * Valida parâmetros específicos
     */
    public function validateParams(array $params): bool
    {
        parent::validateParams($params);

        $operation = $params['operation'] ?? null;

        // Validações por operação
        switch ($operation) {
            case 'get':
            case 'update':
            case 'delete':
                if (empty($params['product_id'])) {
                    throw new ServiceValidationException(
                        'ID do produto obrigatório',
                        ["O parâmetro 'product_id' é obrigatório para a operação '{$operation}'"]
                    );
                }
                break;

            case 'create':
                if (empty($params['name'])) {
                    throw new ServiceValidationException(
                        'Nome obrigatório',
                        ["O parâmetro 'name' é obrigatório para criar um produto"]
                    );
                }
                break;
        }

        return true;
    }
}