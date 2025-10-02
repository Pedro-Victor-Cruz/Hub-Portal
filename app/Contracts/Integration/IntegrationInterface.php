<?php

namespace App\Contracts\Integration;

use App\Models\Integration;
use App\Services\Parameter\ServiceParameterManager;
use App\Services\Core\ApiResponse;

/**
 * Interface para todas as integrações
 * Define os métodos que devem ser implementados pelas classes de integração
 */
interface IntegrationInterface
{
    /**
     * Construtor da integração
     */
    public function __construct(Integration $integration);

    /**
     * Obtém o nome da integração
     */
    public function getName(): string;

    /**
     * Obtém a descrição da integração
     */
    public function getDescription(): string;

    /**
     * Imagem | Link do logo da integração
     */
    public function getImage(): string;

    /**
     * Obtém a versão da integração
     */
    public function getVersion(): string;

    /**
     * Obtém o gerenciador de parâmetros
     */
    public function getParameterManager(): ServiceParameterManager;

    /**
     * Obtém todos os parâmetros configurados
     */
    public function getParameters(): array;

    /**
     * Valida as configurações da integração
     */
    public function validateConfiguration(array $config = null): array;

    /**
     * Testa a conexão com a integração
     */
    public function testConnection(): ApiResponse;

    /**
     * Sincroniza dados com a integração
     */
    public function syncData(array $options = []): ApiResponse;

    /**
     * Obtém informações gerais da integração
     */
    public function getInfo(): array;

    /**
     * Configura os parâmetros específicos da integração
     * Deve ser implementado pelas classes concretas
     */
    public function configureParameters(): void;

    /**
     * Executa o teste de conexão específico da integração
     * Deve ser implementado pelas classes concretas
     */
    public function performConnectionTest(): array;

    /**
     * Executa a sincronização específica da integração
     * Deve ser implementado pelas classes concretas se suportar sincronização
     */
    public function performSync(array $options = []): ApiResponse;
}