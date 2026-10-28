<?php

namespace App\Actions\Maintenance;

use App\Models\Maintenance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds the next ticket number for the current year, e.g. `MT-2026-0001`.
 *
 * Must run inside a transaction: the advisory lock is held until commit, so two
 * concurrent tickets can never pick the same number.
 */
class GenerateTicketNumber
{
    private const int LOCK_KEY = 6_000_001;

    public function handle(): string
    {
        DB::select('select pg_advisory_xact_lock(?)', [self::LOCK_KEY]);

        $prefix = 'MT-'.Carbon::now()->year.'-';

        $lastNumber = Maintenance::query()
            ->where('ticket_number', 'like', $prefix.'%')
            ->orderByRaw('LENGTH(ticket_number) DESC, ticket_number DESC')
            ->value('ticket_number');

        $nextNumber = $lastNumber === null ? 1 : ((int) Str::afterLast($lastNumber, '-')) + 1;

        return sprintf('%s%04d', $prefix, $nextNumber);
    }
}
