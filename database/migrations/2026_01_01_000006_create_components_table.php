<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('spec', 150)->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('qty_total')->default(0);
            $table->integer('min_qty')->default(0);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        DB::statement('ALTER TABLE components ADD CONSTRAINT components_qty_total_check CHECK (qty_total >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};
