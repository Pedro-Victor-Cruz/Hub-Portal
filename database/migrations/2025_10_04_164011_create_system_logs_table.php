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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();

            // Informações principais
            $table->enum('level', ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])
                ->default('info')
                ->index();

            $table->string('action', 50)->index(); // login, logout, created, updated, deleted, viewed, etc
            $table->string('module', 50)->nullable()->index(); // user, company, product, etc

            // Relacionamento com model (polymorphic)
            $table->string('loggable_type', 100)->nullable();
            $table->unsignedBigInteger('loggable_id')->nullable();

            // Dados de auditoria
            $table->json('old_values')->nullable(); // Valores antigos (update/delete)
            $table->json('new_values')->nullable(); // Valores novos (create/update)
            $table->json('changes')->nullable(); // Apenas as mudanças (otimizado)

            // Informações da requisição
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable()->index(); // IPv6 support
            $table->text('user_agent')->nullable();
            $table->string('method', 10)->nullable(); // GET, POST, PUT, DELETE
            $table->string('url', 500)->nullable();

            // Relacionamento com usuário
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_name', 100)->nullable(); // Cache do nome para histórico

            // Agrupamento e contexto
            $table->uuid('batch_id')->nullable()->index(); // Agrupar logs relacionados
            $table->string('session_id', 100)->nullable()->index();
            $table->json('metadata')->nullable(); // Dados extras flexíveis

            // Performance e rastreamento
            $table->string('trace_id', 100)->nullable()->index(); // Para distributed tracing
            $table->unsignedInteger('response_time')->nullable(); // Em milissegundos
            $table->unsignedSmallInteger('status_code')->nullable(); // HTTP status

            $table->timestamp('created_at')->index();

            // Índices compostos para queries comuns
            $table->index(['loggable_type', 'loggable_id'], 'loggable_index');
            $table->index(['user_id', 'action', 'created_at'], 'user_action_date_index');
            $table->index(['level', 'created_at'], 'level_date_index');
            $table->index(['module', 'action', 'created_at'], 'module_action_date_index');
            $table->index(['created_at', 'level'], 'date_level_index'); // Para limpeza
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }

};
