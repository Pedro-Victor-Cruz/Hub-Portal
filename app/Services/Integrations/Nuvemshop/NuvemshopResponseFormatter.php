<?php

namespace App\Services\Utils\ResponseFormatters;

use Carbon\Carbon;

/**
 * Formata respostas da API Nuvemshop para um formato padronizado
 *
 * Normaliza campos, datas, valores monetários e estruturas da API
 */
class NuvemshopResponseFormatter
{
    /**
     * Formata resposta de produtos
     */
    public static function formatProduct(array $product): array
    {
        return [
            'id' => $product['id'] ?? null,
            'name' => $product['name']['pt'] ?? $product['name'] ?? null,
            'description' => $product['description']['pt'] ?? $product['description'] ?? null,
            'handle' => $product['handle'] ?? null,
            'sku' => $product['variants'][0]['sku'] ?? null,
            'price' => self::formatMoney($product['variants'][0]['price'] ?? null),
            'compare_price' => self::formatMoney($product['variants'][0]['compare_at_price'] ?? null),
            'cost_price' => self::formatMoney($product['variants'][0]['cost'] ?? null),
            'stock' => $product['variants'][0]['stock'] ?? null,
            'weight' => $product['variants'][0]['weight'] ?? null,
            'categories' => self::formatCategories($product['categories'] ?? []),
            'images' => self::formatImages($product['images'] ?? []),
            'variants' => self::formatVariants($product['variants'] ?? []),
            'published' => $product['published'] ?? false,
            'free_shipping' => $product['free_shipping'] ?? false,
            'seo_title' => $product['seo_title'] ?? null,
            'seo_description' => $product['seo_description'] ?? null,
            'brand' => $product['brand'] ?? null,
            'created_at' => self::formatDate($product['created_at'] ?? null),
            'updated_at' => self::formatDate($product['updated_at'] ?? null),
        ];
    }

    /**
     * Formata lista de produtos
     */
    public static function formatProducts(array $products): array
    {
        return array_map([self::class, 'formatProduct'], $products);
    }

    /**
     * Formata resposta de variantes
     */
    public static function formatVariants(array $variants): array
    {
        return array_map(function ($variant) {
            return [
                'id' => $variant['id'] ?? null,
                'sku' => $variant['sku'] ?? null,
                'barcode' => $variant['barcode'] ?? null,
                'price' => self::formatMoney($variant['price'] ?? null),
                'compare_price' => self::formatMoney($variant['compare_at_price'] ?? null),
                'cost_price' => self::formatMoney($variant['cost'] ?? null),
                'stock' => $variant['stock'] ?? null,
                'weight' => $variant['weight'] ?? null,
                'width' => $variant['width'] ?? null,
                'height' => $variant['height'] ?? null,
                'depth' => $variant['depth'] ?? null,
                'values' => $variant['values'] ?? [],
                'created_at' => self::formatDate($variant['created_at'] ?? null),
                'updated_at' => self::formatDate($variant['updated_at'] ?? null),
            ];
        }, $variants);
    }

    /**
     * Formata resposta de cliente
     */
    public static function formatCustomer(array $customer): array
    {
        return [
            'id' => $customer['id'] ?? null,
            'email' => $customer['email'] ?? null,
            'name' => $customer['name'] ?? null,
            'phone' => $customer['phone'] ?? null,
            'identification' => $customer['identification'] ?? null,
            'default_address' => self::formatAddress($customer['default_address'] ?? []),
            'addresses' => self::formatAddresses($customer['addresses'] ?? []),
            'billing_name' => $customer['billing_name'] ?? null,
            'billing_phone' => $customer['billing_phone'] ?? null,
            'billing_address' => self::formatAddress($customer['billing_address'] ?? []),
            'total_spent' => self::formatMoney($customer['total_spent'] ?? null),
            'total_orders' => $customer['total_orders'] ?? 0,
            'accepts_marketing' => $customer['accepts_marketing'] ?? false,
            'created_at' => self::formatDate($customer['created_at'] ?? null),
            'updated_at' => self::formatDate($customer['updated_at'] ?? null),
        ];
    }

    /**
     * Formata lista de clientes
     */
    public static function formatCustomers(array $customers): array
    {
        return array_map([self::class, 'formatCustomer'], $customers);
    }

    /**
     * Formata resposta de pedido
     */
    public static function formatOrder(array $order): array
    {
        return [
            'id' => $order['id'] ?? null,
            'number' => $order['number'] ?? null,
            'token' => $order['token'] ?? null,
            'status' => $order['status'] ?? null,
            'payment_status' => $order['payment_status'] ?? null,
            'shipping_status' => $order['shipping_status'] ?? null,
            'customer' => self::formatCustomer($order['customer'] ?? []),
            'products' => self::formatOrderProducts($order['products'] ?? []),
            'shipping_address' => self::formatAddress($order['shipping_address'] ?? []),
            'billing_address' => self::formatAddress($order['billing_address'] ?? []),
            'subtotal' => self::formatMoney($order['subtotal'] ?? null),
            'discount' => self::formatMoney($order['discount'] ?? null),
            'shipping' => self::formatMoney($order['shipping'] ?? null),
            'total' => self::formatMoney($order['total'] ?? null),
            'currency' => $order['currency'] ?? 'BRL',
            'payment_method' => $order['payment_details']['method'] ?? null,
            'shipping_carrier' => $order['shipping_carrier_name'] ?? null,
            'tracking_number' => $order['shipping_tracking_number'] ?? null,
            'note' => $order['note'] ?? null,
            'created_at' => self::formatDate($order['created_at'] ?? null),
            'updated_at' => self::formatDate($order['updated_at'] ?? null),
            'completed_at' => self::formatDate($order['completed_at']['date'] ?? null),
        ];
    }

    /**
     * Formata lista de pedidos
     */
    public static function formatOrders(array $orders): array
    {
        return array_map([self::class, 'formatOrder'], $orders);
    }

    /**
     * Formata produtos de um pedido
     */
    private static function formatOrderProducts(array $products): array
    {
        return array_map(function ($product) {
            return [
                'id' => $product['id'] ?? null,
                'variant_id' => $product['variant_id'] ?? null,
                'product_id' => $product['product_id'] ?? null,
                'name' => $product['name'] ?? null,
                'sku' => $product['sku'] ?? null,
                'quantity' => $product['quantity'] ?? 0,
                'price' => self::formatMoney($product['price'] ?? null),
                'weight' => $product['weight'] ?? null,
                'image' => $product['image']['src'] ?? null,
            ];
        }, $products);
    }

    /**
     * Formata endereço
     */
    private static function formatAddress(array $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        return [
            'id' => $address['id'] ?? null,
            'address' => $address['address'] ?? null,
            'number' => $address['number'] ?? null,
            'complement' => $address['complement'] ?? null,
            'neighborhood' => $address['neighborhood'] ?? null,
            'city' => $address['city'] ?? null,
            'province' => $address['province'] ?? null,
            'zipcode' => $address['zipcode'] ?? null,
            'country' => $address['country'] ?? 'BR',
            'phone' => $address['phone'] ?? null,
        ];
    }

    /**
     * Formata lista de endereços
     */
    private static function formatAddresses(array $addresses): array
    {
        return array_map([self::class, 'formatAddress'], array_filter($addresses));
    }

    /**
     * Formata categorias
     */
    private static function formatCategories(array $categories): array
    {
        return array_map(function ($category) {
            return [
                'id' => $category['id'] ?? null,
                'name' => $category['name']['pt'] ?? $category['name'] ?? null,
                'parent_id' => $category['parent'] ?? null,
            ];
        }, $categories);
    }

    /**
     * Formata imagens
     */
    private static function formatImages(array $images): array
    {
        return array_map(function ($image) {
            return [
                'id' => $image['id'] ?? null,
                'src' => $image['src'] ?? null,
                'position' => $image['position'] ?? 0,
            ];
        }, $images);
    }

    /**
     * Formata valor monetário
     */
    private static function formatMoney(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    /**
     * Formata data para ISO 8601
     */
    private static function formatDate(?string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        try {
            return Carbon::parse($date)->toIso8601String();
        } catch (\Exception $e) {
            return $date;
        }
    }

    /**
     * Remove campos vazios recursivamente
     */
    public static function removeEmptyFields(array $data): array
    {
        return array_filter($data, function ($value) {
            if (is_array($value)) {
                return !empty(self::removeEmptyFields($value));
            }
            return $value !== null && $value !== '';
        });
    }

    /**
     * Formata resposta de webhook
     */
    public static function formatWebhook(array $webhook): array
    {
        return [
            'id' => $webhook['id'] ?? null,
            'url' => $webhook['url'] ?? null,
            'event' => $webhook['event'] ?? null,
            'created_at' => self::formatDate($webhook['created_at'] ?? null),
            'updated_at' => self::formatDate($webhook['updated_at'] ?? null),
        ];
    }
}