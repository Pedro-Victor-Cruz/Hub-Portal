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
        Schema::table('portal_users', function (Blueprint $table) {
            $table->foreign('portal_id', 'portal_users_portal_id_foreign')->references('id')->on('portals')->onUpdate('CASCADE')->onDelete('CASCADE');
            $table->foreign('user_id', 'portal_users_user_id_foreign')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portal_users', function (Blueprint $table) {
            $table->dropForeign('portal_users_portal_id_foreign');
            $table->dropForeign('portal_users_user_id_foreign');
        });
    }
};
