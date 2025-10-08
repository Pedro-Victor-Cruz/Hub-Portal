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
        Schema::create('query_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('performance_metric_id')->nullable()->constrained()->nullOnDelete();
            $table->text('sql_query');
            $table->string('query_hash', 64)->index(); // MD5 da query
            $table->string('query_type', 20)->index(); // SELECT, INSERT, UPDATE, DELETE
            $table->float('duration')->index(); // em milissegundos
            $table->string('table_name', 100)->nullable()->index();
            $table->string('endpoint', 500)->nullable()->index();
            $table->boolean('is_duplicate')->default(false)->index();
            $table->json('bindings')->nullable();
            $table->json('stack_trace')->nullable();
            $table->timestamps();

            // Índices compostos
            $table->index(['query_hash', 'created_at']);
            $table->index(['table_name', 'created_at']);
            $table->index(['duration', 'created_at']);
            $table->index(['is_duplicate', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_performances');
    }
};
