<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('contact_name', 150)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 190)->nullable();
            $table->boolean('is_service_vendor')->default(false);
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->timestampsTz();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 20);
            $table->string('tag_prefix', 10)->nullable();
            $table->timestampsTz();

            $table->unique(['type', 'name']);
        });

        DB::statement("ALTER TABLE categories ADD CONSTRAINT categories_type_check CHECK (type IN ('asset','component','accessory','license'))");

        Schema::create('asset_models', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('model_number', 100)->nullable();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->integer('depreciation_months')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_models');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('manufacturers');
        Schema::dropIfExists('suppliers');
    }
};
