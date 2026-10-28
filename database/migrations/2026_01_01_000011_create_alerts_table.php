<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('severity', 10)->default('warning');
            $table->string('subject_type', 50);
            $table->unsignedBigInteger('subject_id');
            $table->text('message');
            $table->date('due_date')->nullable();
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['subject_type', 'subject_id']);
        });

        DB::statement("ALTER TABLE alerts ADD CONSTRAINT alerts_type_check CHECK (type IN ('employee_offboarding','warranty_expiring','license_expiring','license_seats_full','overdue_return','low_stock','maintenance_due'))");
        DB::statement("ALTER TABLE alerts ADD CONSTRAINT alerts_severity_check CHECK (severity IN ('info','warning','critical'))");
        DB::statement('CREATE UNIQUE INDEX alerts_open_unique ON alerts(type, subject_type, subject_id) WHERE resolved_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
