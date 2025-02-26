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
        Schema::table('entity_properties', function (Blueprint $table) {
            $table->foreign('entity_id', 'entity_properties_entity_id_foreign')
                ->references('id')
                ->on('entities')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entity_properties', function (Blueprint $table) {
            $table->dropForeign('entity_properties_entity_id_foreign');
        });
    }
};
