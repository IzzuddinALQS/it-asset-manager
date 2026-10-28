<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('manufacturer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('license_type', 20);
            $table->text('product_key')->nullable();
            $table->integer('seats_total');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purchase_order', 50)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('annual_cost', 15, 2)->nullable();
            $table->date('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('expires_at', 'licenses_expires_idx');
        });

        DB::statement("ALTER TABLE licenses ADD CONSTRAINT licenses_type_check CHECK (license_type IN ('subscription','perpetual','oem','concurrent'))");
        DB::statement('ALTER TABLE licenses ADD CONSTRAINT licenses_seats_total_check CHECK (seats_total > 0)');

        Schema::create('license_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->timestampTz('assigned_at')->useCurrent();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();

            // Partial index below: only active seats count.
        });

        DB::statement('CREATE INDEX license_seats_active_idx ON license_seats(license_id) WHERE released_at IS NULL');

        DB::statement('ALTER TABLE license_seats ADD CONSTRAINT license_seats_target CHECK (employee_id IS NOT NULL OR asset_id IS NOT NULL)');
    }

    public function down(): void
    {
        Schema::dropIfExists('license_seats');
        Schema::dropIfExists('licenses');
    }
};
