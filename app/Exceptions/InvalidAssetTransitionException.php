<?php

namespace App\Exceptions;

use App\Enums\AssetStatus;
use App\Models\Employee;
use RuntimeException;

/**
 * Thrown when an asset is asked to move to a status that the lifecycle forbids.
 */
class InvalidAssetTransitionException extends RuntimeException
{
    public static function between(AssetStatus $from, AssetStatus $to): self
    {
        return new self("Status inventaris tidak dapat berubah dari \"{$from->label()}\" ke \"{$to->label()}\".");
    }

    public static function withoutHolder(): self
    {
        return new self('Inventaris ini tidak sedang dipegang siapa pun, jadi tidak bisa dikembalikan.');
    }

    public static function withoutRecipient(): self
    {
        return new self('Pilih karyawan atau lokasi yang akan menerima inventaris.');
    }

    public static function inactiveRecipient(Employee $employee): self
    {
        return new self("{$employee->name} sudah tidak aktif dan tidak bisa menerima inventaris.");
    }
}
