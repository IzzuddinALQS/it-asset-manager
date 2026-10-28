<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('herd_id', 64)->unique();
            $table->string('employee_number', 32)->unique();
            $table->string('name', 150);
            $table->string('email', 190)->nullable();
            $table->string('job_title', 150)->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('employment_status', 20)->default('active');
            $table->date('status_effective_date')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->timestampsTz();

            $table->index('department_id', 'employees_department_idx');
            $table->index('employment_status', 'employees_status_idx');
        });

        DB::statement("ALTER TABLE employees ADD CONSTRAINT employees_employment_status_check CHECK (employment_status IN ('active','resigned','transferred','inactive'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
