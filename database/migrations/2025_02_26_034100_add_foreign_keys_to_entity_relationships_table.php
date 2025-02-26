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
        Schema::table('entity_relationships', function (Blueprint $table) {
            $table->foreign('parent_entity_id')->references('id')->on('entities')->onDelete('cascade');
            $table->foreign('child_entity_id')->references('id')->on('entities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entity_relationships', function (Blueprint $table) {
            $table->dropForeign(['parent_entity_id']);
            $table->dropForeign(['child_entity_id']);
        });
    }
};
