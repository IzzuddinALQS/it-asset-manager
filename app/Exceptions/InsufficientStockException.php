<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an install, checkout or return does not fit the stock the views report.
 */
class InsufficientStockException extends RuntimeException
{
    public static function for(string $itemName, int $requested, int $available): self
    {
        return new self("Stok {$itemName} tidak cukup: diminta {$requested}, tersedia {$available}.");
    }

    public static function returnExceedsCheckout(int $requested, int $outstanding): self
    {
        return new self("Jumlah dikembalikan ({$requested}) melebihi yang masih dipinjam ({$outstanding}).");
    }

    public static function alreadyClosed(): self
    {
        return new self('Catatan ini sudah ditutup sebelumnya.');
    }
}
