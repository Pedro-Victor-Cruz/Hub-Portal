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
        Schema::create('permission_group_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_group_id');
            $table->unsignedBigInteger('permission_id');

            $table->foreign('permission_group_id')
                ->references('id')
                ->on('permission_groups')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('permission_id')
                ->references('id')
                ->on('permissions')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->primary(['permission_group_id', 'permission_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_group_has_permissions');
    }
};
