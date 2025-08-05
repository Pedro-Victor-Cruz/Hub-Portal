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
        Schema::create('parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('description');
            $table->string('category');
            $table->enum('type', ['boolean', 'integer', 'decimal', 'date', 'text', 'list']);
            $table->text('default_value')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_system')->default(false);
            $table->unsignedTinyInteger('access_level')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
