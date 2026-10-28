<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('model', 150)->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('qty_total')->default(0);
            $table->integer('min_qty')->default(0);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        Schema::create('accessory_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accessory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->integer('qty')->default(1);
            $table->timestampTz('checked_out_at')->useCurrent();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
        });

        DB::statement('CREATE INDEX accessory_checkouts_active_idx ON accessory_checkouts(accessory_id) WHERE checked_in_at IS NULL');
        DB::statement('CREATE INDEX accessory_checkouts_employee_idx ON accessory_checkouts(employee_id) WHERE checked_in_at IS NULL');
        DB::statement('ALTER TABLE accessories ADD CONSTRAINT accessories_qty_total_check CHECK (qty_total >= 0)');
        DB::statement('ALTER TABLE accessory_checkouts ADD CONSTRAINT accessory_checkouts_qty_check CHECK (qty > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('accessory_checkouts');
        Schema::dropIfExists('accessories');
    }
};
