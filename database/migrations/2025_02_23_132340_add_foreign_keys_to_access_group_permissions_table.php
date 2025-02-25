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
        Schema::table('access_group_permissions', function (Blueprint $table) {
            $table->foreign('access_group_id')->references('id')->on('access_groups')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('permission_id')->references('id')->on('permissions')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_group_permissions', function (Blueprint $table) {
            $table->dropForeign(['access_group_id']);
            $table->dropForeign(['permission_id']);
        });
    }
};
