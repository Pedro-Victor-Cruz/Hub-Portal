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
        Schema::create('dashboard_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained()->onDelete('cascade');
            $table->uuid('token')->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('max_uses')->nullable();
            $table->integer('uses_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['token', 'active']);
            $table->index(['dashboard_id', 'active']);
            $table->index('expires_at');
        });

        // Tabela para rastrear acessos via convite (opcional, mas útil para auditoria)
        Schema::create('dashboard_invitation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitation_id')->constrained('dashboard_invitations')->onDelete('cascade');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('accessed_at');

            $table->index(['invitation_id', 'accessed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dashboard_invitations');
        Schema::dropIfExists('dashboard_invitation_logs');
    }
};
