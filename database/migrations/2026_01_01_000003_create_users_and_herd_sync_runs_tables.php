<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('viewer');
            $table->timestampTz('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestampsTz();
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_role_check CHECK (role IN ('admin','technician','viewer'))");

        Schema::create('herd_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('started_at');
            $table->timestampTz('finished_at')->nullable();
            $table->string('status', 20);
            $table->integer('created_count')->default(0);
            $table->integer('updated_count')->default(0);
            $table->integer('offboarding_count')->default(0);
            $table->text('error_message')->nullable();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::statement("ALTER TABLE herd_sync_runs ADD CONSTRAINT herd_sync_runs_status_check CHECK (status IN ('running','success','failed'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('herd_sync_runs');
        Schema::dropIfExists('users');
    }
};
