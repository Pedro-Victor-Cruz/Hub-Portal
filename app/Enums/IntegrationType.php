<?php

namespace App\Enums;

enum IntegrationType: string
{
    // ERP Systems
    case SANKHYA = 'sankhya';
    case TOTVS = 'totvs';
    case VTEX = 'vtex';
    case BLING = 'bling';
    case SAP = 'sap';
    case ORACLE = 'oracle';
    case NUVEMSHOP = 'nuvemshop';

    // Calendar Systems
    case GOOGLE_CALENDAR = 'google_calendar';
    case OUTLOOK_CALENDAR = 'outlook_calendar';

    // CRM Systems
    case SALESFORCE = 'salesforce';
    case HUBSPOT = 'hubspot';
    case PIPEDRIVE = 'pipedrive';

    // E-commerce
    case SHOPIFY = 'shopify';
    case MAGENTO = 'magento';
    case WOOCOMMERCE = 'woocommerce';

    // Payment Gateways
    case STRIPE = 'stripe';
    case PAYPAL = 'paypal';
    case PAGSEGURO = 'pagseguro';
    case MERCADOPAGO = 'mercadopago';

    // Communication
    case SLACK = 'slack';
    case TEAMS = 'teams';
    case WHATSAPP = 'whatsapp';

    // Other
    case CUSTOM = 'custom';

    /**
     * Retorna o nome amigável da integração
     */
    public function getDisplayName(): string
    {
        return match($this) {
            self::SANKHYA => 'Sankhya',
            self::TOTVS => 'TOTVS',
            self::BLING => 'Bling',
            self::SAP => 'SAP',
            self::ORACLE => 'Oracle',
            self::GOOGLE_CALENDAR => 'Google Calendar',
            self::OUTLOOK_CALENDAR => 'Outlook Calendar',
            self::SALESFORCE => 'Salesforce',
            self::HUBSPOT => 'HubSpot',
            self::PIPEDRIVE => 'Pipedrive',
            self::SHOPIFY => 'Shopify',
            self::MAGENTO => 'Magento',
            self::WOOCOMMERCE => 'WooCommerce',
            self::STRIPE => 'Stripe',
            self::PAYPAL => 'PayPal',
            self::PAGSEGURO => 'PagSeguro',
            self::MERCADOPAGO => 'Mercado Pago',
            self::SLACK => 'Slack',
            self::TEAMS => 'Microsoft Teams',
            self::WHATSAPP => 'WhatsApp',
            self::CUSTOM => 'Customizada',
        };
    }

    /**
     * Retorna a categoria da integração
     */
    public function getCategory(): string
    {
        return match($this) {
            self::SANKHYA, self::TOTVS, self::BLING, self::SAP, self::ORACLE => 'erp',
            self::GOOGLE_CALENDAR, self::OUTLOOK_CALENDAR => 'calendar',
            self::SALESFORCE, self::HUBSPOT, self::PIPEDRIVE => 'crm',
            self::SHOPIFY, self::MAGENTO, self::WOOCOMMERCE => 'ecommerce',
            self::STRIPE, self::PAYPAL, self::PAGSEGURO, self::MERCADOPAGO => 'payment',
            self::SLACK, self::TEAMS, self::WHATSAPP => 'communication',
            self::CUSTOM => 'other',
        };
    }

    /**
     * Retorna a descrição da categoria
     */
    public function getCategoryDescription(): string
    {
        return match($this->getCategory()) {
            'erp' => 'Sistema de Gestão Empresarial',
            'calendar' => 'Sistema de Calendário',
            'crm' => 'Sistema de CRM',
            'ecommerce' => 'E-commerce',
            'payment' => 'Gateway de Pagamento',
            'communication' => 'Comunicação',
            'other' => 'Outros',
        };
    }

    /**
     * Retorna as integrações de uma categoria específica
     */
    public static function getByCategory(string $category): array
    {
        return array_filter(self::cases(), fn($type) => $type->getCategory() === $category);
    }

    /**
     * Retorna todas as integrações agrupadas por categoria
     */
    public static function getAllGroupedByCategory(): array
    {
        $grouped = [];
        foreach (self::cases() as $type) {
            $category = $type->getCategory();
            $grouped[$category][] = $type;
        }
        return $grouped;
    }
}