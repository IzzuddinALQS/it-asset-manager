<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Reported = 'reported';
    case InProgress = 'in_progress';
    case WaitingParts = 'waiting_parts';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Reported => 'Dilaporkan',
            self::InProgress => 'Dikerjakan',
            self::WaitingParts => 'Menunggu Sparepart',
            self::Done => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Reported, self::InProgress, self::WaitingParts], true);
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Reported => [self::InProgress, self::WaitingParts, self::Done, self::Cancelled],
            self::InProgress => [self::WaitingParts, self::Done, self::Cancelled],
            self::WaitingParts => [self::InProgress, self::Done, self::Cancelled],
            self::Done, self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
