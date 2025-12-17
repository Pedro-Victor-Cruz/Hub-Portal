<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_dashboards_table
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboards', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique()->index();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->json('config')->nullable()->comment('Configurações gerais do dashboard');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('dashboard_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('dashboards')->onDelete('cascade');
            $table->foreignId('parent_section_id')->nullable()->constrained('dashboard_sections')->onDelete('cascade');
            $table->string('key', 100);
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('level')->default(1)->index();
            $table->integer('order')->default(0)->index();

            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['dashboard_id', 'parent_section_id']);
            $table->index(['dashboard_id', 'level']);
        });

        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('dashboard_sections')->onDelete('cascade');
            $table->foreignId('dynamic_query_id')->nullable()->constrained('dynamic_queries')->onDelete('set null');

            $table->string('key', 100);
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->string('widget_type')->index();
            $table->json('config')->nullable();
            $table->integer('order')->default(0)->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->index(['section_id', 'order']);
            $table->index(['widget_type', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
        Schema::dropIfExists('dashboard_sections');
        Schema::dropIfExists('dashboards');
    }
};