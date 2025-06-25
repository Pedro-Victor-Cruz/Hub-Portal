<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('company_erp_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('erp_name')->nullable();
            $table->string('username')->nullable();
            $table->string('secret_key')->nullable();
            $table->string('base_url')->nullable();
            $table->string('token')->nullable();
            $table->enum('auth_type', ['token', 'session', 'oauth'])->nullable();
            $table->json('extra_config')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();

            $table->foreign('company_id', 'company_erp_settings_company_id_foreign')
                ->references('id')
                ->on('companies')
                ->onUpdate('restrict')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_erp_settings');
    }
};
