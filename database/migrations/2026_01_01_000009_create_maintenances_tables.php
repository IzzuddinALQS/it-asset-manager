<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('interval_months');
            $table->date('next_due_date');
            $table->string('pic', 150)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        DB::statement('ALTER TABLE maintenance_plans ADD CONSTRAINT maintenance_plans_interval_check CHECK (interval_months > 0)');

        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 20)->unique();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('maintenance_plans')->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('reported');
            $table->string('priority', 10)->default('medium');
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('handled_by', 20)->default('internal');
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reported_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->decimal('estimated_cost', 15, 2)->nullable();
            $table->decimal('actual_cost', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('target_date')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->string('asset_status_before', 20)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index('status', 'maintenances_status_idx');
            $table->index('asset_id', 'maintenances_asset_idx');
        });

        DB::statement("ALTER TABLE maintenances ADD CONSTRAINT maintenances_type_check CHECK (type IN ('repair','preventive','upgrade','calibration'))");
        DB::statement("ALTER TABLE maintenances ADD CONSTRAINT maintenances_status_check CHECK (status IN ('reported','in_progress','waiting_parts','done','cancelled'))");
        DB::statement("ALTER TABLE maintenances ADD CONSTRAINT maintenances_priority_check CHECK (priority IN ('low','medium','high'))");
        DB::statement("ALTER TABLE maintenances ADD CONSTRAINT maintenances_handled_by_check CHECK (handled_by IN ('internal','vendor','warranty'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
        Schema::dropIfExists('maintenance_plans');
    }
};
