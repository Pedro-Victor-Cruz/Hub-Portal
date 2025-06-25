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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: 'user.create', 'company.edit'
            $table->string('description')->nullable();
            $table->string('group')->nullable(); // Para agrupar permissões (Ex: 'users', 'companies')
            $table->string('group_description')->nullable();
            $table->unsignedTinyInteger('access_level')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
