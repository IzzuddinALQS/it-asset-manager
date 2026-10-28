<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag', 30)->unique();
            $table->string('serial_number', 100)->nullable();
            $table->foreignId('asset_model_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->foreignId('assigned_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('assigned_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->timestampTz('assigned_at')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('purchase_order', 50)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->date('warranty_until')->nullable();
            $table->date('last_audited_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestampTz('retired_at')->nullable();
            $table->string('retire_reason', 30)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->softDeletesTz();

            $table->index('status', 'assets_status_idx');
            $table->index('assigned_employee_id', 'assets_employee_idx');
            $table->index('warranty_until', 'assets_warranty_idx');
        });

        DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_status_check CHECK (status IN ('pending','ready','deployed','maintenance','retired'))");
        DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_retire_reason_check CHECK (retire_reason IS NULL OR retire_reason IN ('end_of_life','broken','lost','sold','donated','destroyed'))");
        DB::statement('ALTER TABLE assets ADD CONSTRAINT assets_single_assignee CHECK (NOT (assigned_employee_id IS NOT NULL AND assigned_location_id IS NOT NULL))');
        DB::statement('ALTER TABLE assets ADD CONSTRAINT assets_deployed_has_assignee CHECK (status <> \'deployed\' OR assigned_employee_id IS NOT NULL OR assigned_location_id IS NOT NULL)');
        DB::statement('CREATE UNIQUE INDEX assets_serial_unique ON assets(serial_number) WHERE serial_number IS NOT NULL AND deleted_at IS NULL');

        Schema::create('asset_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('action', 30);
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20)->nullable();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->jsonb('meta')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['asset_id', 'created_at'], 'asset_logs_asset_idx');
        });

        DB::statement("ALTER TABLE asset_logs ADD CONSTRAINT asset_logs_action_check CHECK (action IN ('created','tagged','checkout','checkin','maintenance_start','maintenance_done','component_installed','component_removed','updated','audited','retired'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_logs');
        Schema::dropIfExists('assets');
    }
};
