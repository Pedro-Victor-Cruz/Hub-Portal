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
        Schema::table('permission_groups', function (Blueprint $table) {
            $table->unsignedBigInteger('dashboard_home_id')->nullable()->after('access_level');

            $table->foreign('dashboard_home_id')
                ->references('id')
                ->on('dashboards')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permission_groups', function (Blueprint $table) {
            $table->dropForeign(['dashboard_home_id']);
            $table->dropColumn('dashboard_home_id');
        });
    }
};
