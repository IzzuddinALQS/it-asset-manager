<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->boolean('is_factory')->default(false);
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenances')->nullOnDelete();
            $table->timestampTz('installed_at')->useCurrent();
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('removed_at')->nullable();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::statement('CREATE INDEX component_installations_active_idx ON component_installations(component_id) WHERE removed_at IS NULL');
        DB::statement('ALTER TABLE component_installations ADD CONSTRAINT component_installations_qty_check CHECK (qty > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('component_installations');
    }
};
