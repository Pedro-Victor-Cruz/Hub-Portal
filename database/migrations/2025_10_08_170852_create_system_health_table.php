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
        Schema::create('system_health', function (Blueprint $table) {
            $table->id();

            // CPU
            $table->float('cpu_usage')->nullable();

            // Memória
            $table->bigInteger('memory_total')->nullable();
            $table->bigInteger('memory_used')->nullable();
            $table->bigInteger('memory_free')->nullable();
            $table->float('memory_percent')->nullable();

            // Disco
            $table->bigInteger('disk_total')->nullable();
            $table->bigInteger('disk_used')->nullable();
            $table->bigInteger('disk_free')->nullable();
            $table->float('disk_percent')->nullable();

            // Load Average
            $table->float('load_average_1min')->nullable();
            $table->float('load_average_5min')->nullable();
            $table->float('load_average_15min')->nullable();

            // Conexões e processos
            $table->integer('active_connections')->nullable();
            $table->integer('total_processes')->nullable();

            // Rede
            $table->bigInteger('network_in')->nullable();
            $table->bigInteger('network_out')->nullable();

            // Uptime
            $table->string('uptime')->nullable();

            // Status
            $table->enum('status', ['healthy', 'degraded', 'critical'])->default('healthy')->index();

            // Alertas
            $table->json('alerts')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_health');
    }
};
