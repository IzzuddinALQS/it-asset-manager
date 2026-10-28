<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            CREATE VIEW component_stock AS
            SELECT c.id AS component_id,
                   c.qty_total,
                   COALESCE(SUM(ci.qty) FILTER (WHERE ci.removed_at IS NULL AND NOT ci.is_factory), 0) AS qty_installed,
                   c.qty_total - COALESCE(SUM(ci.qty) FILTER (WHERE ci.removed_at IS NULL AND NOT ci.is_factory), 0) AS qty_available
            FROM components c
            LEFT JOIN component_installations ci ON ci.component_id = c.id
            GROUP BY c.id
        SQL);

        DB::statement(<<<'SQL'
            CREATE VIEW accessory_stock AS
            SELECT a.id AS accessory_id,
                   a.qty_total,
                   COALESCE(SUM(ac.qty) FILTER (WHERE ac.checked_in_at IS NULL), 0) AS qty_out,
                   a.qty_total - COALESCE(SUM(ac.qty) FILTER (WHERE ac.checked_in_at IS NULL), 0) AS qty_available
            FROM accessories a
            LEFT JOIN accessory_checkouts ac ON ac.accessory_id = a.id
            GROUP BY a.id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS accessory_stock');
        DB::statement('DROP VIEW IF EXISTS component_stock');
    }
};
