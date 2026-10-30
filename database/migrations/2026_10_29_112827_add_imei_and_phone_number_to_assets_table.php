<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('imei', 20)->nullable()->after('serial_number');
            $table->string('phone_number', 20)->nullable()->after('imei');
        });

        DB::statement('CREATE UNIQUE INDEX assets_imei_unique ON assets(imei) WHERE imei IS NOT NULL AND deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS assets_imei_unique');

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['imei', 'phone_number']);
        });
    }
};
