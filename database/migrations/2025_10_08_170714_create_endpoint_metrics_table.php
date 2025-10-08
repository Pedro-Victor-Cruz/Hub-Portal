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
        Schema::create('endpoint_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('endpoint', 500);
            $table->string('method', 10);
            $table->integer('total_requests')->default(0);
            $table->integer('total_errors')->default(0);
            $table->float('avg_response_time')->default(0);
            $table->float('min_response_time')->nullable();
            $table->float('max_response_time')->nullable();
            $table->float('p50_response_time')->nullable();
            $table->float('p95_response_time')->nullable();
            $table->float('p99_response_time')->nullable();
            $table->float('avg_memory_usage')->nullable();
            $table->float('avg_cpu_usage')->nullable();
            $table->float('avg_queries_count')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->date('date')->index();
            $table->timestamps();

            // Índice único para evitar duplicatas
            $table->unique(['endpoint', 'method', 'date']);

            // Índices para queries comuns
            $table->index(['date', 'avg_response_time']);
            $table->index(['date', 'total_requests']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('endpoint_metrics');
    }
};
