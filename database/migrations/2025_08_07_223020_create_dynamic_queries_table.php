<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('dynamic_queries', function (Blueprint $table) {
            $table->id();
            $table->string('key'); // Ex: 'produtos', 'clientes', 'pedidos'
            $table->string('name'); // Nome amigável da consulta
            $table->text('description')->nullable();

            // Configuração do serviço
            $table->string('service_slug'); // Slug do serviço a ser utilizado
            $table->json('service_params')->nullable(); // Parâmetros específicos do serviço

            // Configuração da query
            $table->text('query_config')->nullable(); // SQL, endpoint, etc.

            // Metadata e formatação
            $table->json('fields_metadata')->nullable(); // Configuração dos campos
            $table->json('response_format')->nullable(); // Como formatar a resposta

            // Controle
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Índices
            $table->unique(['key']);
            $table->index(['key']);
            $table->index(['active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_queries');
    }
};
