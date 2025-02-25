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
        Schema::create('users_refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token');
            $table->dateTime('last_used_at')->nullable()->useCurrent();
            $table->string('last_ip', 70)->nullable();
            $table->enum('login_method', ['phone', 'google', 'email', 'whatsapp'])->nullable()->default('email');
            $table->string('operating_system', 250)->nullable();
            $table->string('os_version', 250)->nullable();
            $table->string('platform', 250)->nullable();
            $table->string('model', 250)->nullable();
            $table->string('device_name', 250)->nullable();
            $table->string('manufacturer', 250)->nullable();
            $table->string('latitude', 250)->nullable();
            $table->string('longitude', 250)->nullable();
            $table->dateTime('expires_at');
            $table->boolean('revoked')->nullable()->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users_refresh_tokens');
    }
};
