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
        Schema::create('filters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dynamic_query_id')->nullable();
            $table->unsignedBigInteger('dashboard_id')->nullable();

            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('var_name', 50);

            $table->enum('type', [
                'text',
                'number',
                'boolean',
                'date',
                'select',
                'multiselect',
                'array'
            ]);

            $table->json('default_value')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->boolean('visible')->default(true);
            $table->boolean('active')->default(true);
            $table->json('options')->nullable();

            $table->timestamps();

            // Foreign Key
            $table->foreign('dynamic_query_id')
                ->references('id')
                ->on('dynamic_queries')
                ->onDelete('cascade');

            $table->foreign('dashboard_id')
                ->references('id')
                ->on('dashboards')
                ->onDelete('cascade');

            // Índices
            $table->index(['dynamic_query_id', 'dashboard_id', 'active'], 'idx_filters');
            $table->index('var_name', 'idx_var_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filters');
    }

};
