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
        Schema::create('entity_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_entity_id'); // Entidade que possui a chave estrangeira
            $table->unsignedBigInteger('child_entity_id'); // Entidade referenciada
            $table->string('parent_field_name'); // Nome do campo na entidade pai (ex: portal_id)
            $table->string('child_field_name'); // Nome do campo na entidade filha (ex: id)
            $table->string('relationship_type')->default('belongsTo'); // Tipo de relação (ex: belongsTo, hasMany)
            $table->timestamps();

            // Índices únicos para evitar duplicação de relações
            $table->unique(['parent_entity_id', 'child_entity_id', 'parent_field_name', 'child_field_name'], 'unique_relationship');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entity_relationships');
    }
};
