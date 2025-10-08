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
        Schema::create('performance_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 500)->index();
            $table->string('method', 10)->index();
            $table->integer('status_code')->index();
            $table->float('response_time')->index(); // em milissegundos
            $table->bigInteger('memory_usage')->nullable(); // em bytes
            $table->bigInteger('memory_peak')->nullable(); // em bytes
            $table->float('cpu_usage')->nullable(); // percentual
            $table->integer('queries_count')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->string('session_id', 100)->nullable()->index();
            $table->timestamps();

            // Índices compostos para queries comuns
            $table->index(['endpoint', 'created_at']);
            $table->index(['status_code', 'created_at']);
            $table->index(['created_at', 'response_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_metrics');
    }
};
