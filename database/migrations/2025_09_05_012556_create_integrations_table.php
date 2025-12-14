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
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->string('integration_name'); // sankhya, totvs, calendar, etc
            $table->json('configuration'); // Configurações específicas da integração
            $table->boolean('active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_status')->nullable(); // Status da última sincronização
            $table->timestamps();

            // Indexs
            $table->index(['integration_name']);
            $table->unique(['integration_name'], 'unique_company_integration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
