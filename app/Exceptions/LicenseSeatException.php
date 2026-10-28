<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a seat assignment or release does not fit the license's current seats.
 */
class LicenseSeatException extends RuntimeException
{
    public static function seatsFull(string $licenseName, int $seatsTotal): self
    {
        return new self("Seat {$licenseName} sudah penuh ({$seatsTotal} dari {$seatsTotal} terpakai).");
    }

    public static function alreadyAssigned(string $holder): self
    {
        return new self("{$holder} sudah memegang seat lisensi ini.");
    }

    public static function alreadyReleased(): self
    {
        return new self('Seat ini sudah dilepas sebelumnya.');
    }
}
