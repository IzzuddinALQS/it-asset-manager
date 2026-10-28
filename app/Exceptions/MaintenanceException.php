<?php

namespace App\Exceptions;

use App\Enums\MaintenanceStatus;
use RuntimeException;

/**
 * Thrown when a maintenance ticket cannot be opened, moved or closed in its current state.
 */
class MaintenanceException extends RuntimeException
{
    public static function assetUnavailable(string $assetTag, string $statusLabel): self
    {
        return new self("Aset {$assetTag} berstatus \"{$statusLabel}\" dan tidak bisa dikirim ke maintenance.");
    }

    public static function alreadyOpen(string $assetTag, string $ticketNumber): self
    {
        return new self("Aset {$assetTag} masih punya tiket terbuka ({$ticketNumber}).");
    }

    public static function invalidTransition(MaintenanceStatus $from, MaintenanceStatus $to): self
    {
        return new self("Status tiket tidak dapat berubah dari \"{$from->label()}\" ke \"{$to->label()}\".");
    }

    public static function cannotReturnToHolder(): self
    {
        return new self('Aset tidak sedang dipakaikan sebelum maintenance, jadi hanya bisa dikembalikan ke status Siap Pakai.');
    }
}
