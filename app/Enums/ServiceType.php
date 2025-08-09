<?php

namespace App\Enums;

/**
 * Enumeração dos tipos de serviços disponíveis
 */
enum ServiceType: string
{
    case READ = 'read';           // Serviços de leitura (consultas, APIs)
    case WRITE = 'write';         // Serviços de escrita (cadastros, alterações)
    case DELETE = 'delete';       // Serviços de exclusão
    case INTEGRATION = 'integration'; // Serviços de integração entre sistemas
    case UTILITY = 'utility';     // Serviços utilitários/auxiliares
    case QUERY = 'query';       // Serviços de execução de consultas SQL

    /**
     * Retorna a descrição do tipo de serviço
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::READ => 'Serviços de leitura de dados',
            self::WRITE => 'Serviços de escrita/cadastro',
            self::DELETE => 'Serviços de exclusão',
            self::INTEGRATION => 'Serviços de integração',
            self::UTILITY => 'Serviços utilitários',
        };
    }
}
