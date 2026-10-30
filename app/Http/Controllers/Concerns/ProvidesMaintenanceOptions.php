<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\HandledBy;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;

/**
 * Enum options for opening maintenance tickets, shared by the board,
 * the ticket page and the asset page.
 */
trait ProvidesMaintenanceOptions
{
    /**
     * @return array{types: array<int, array{value: string, label: string}>, priorities: array<int, array{value: string, label: string}>, handledBy: array<int, array{value: string, label: string}>}
     */
    private function maintenanceEnumOptions(): array
    {
        return [
            'types' => array_map(fn (MaintenanceType $type): array => ['value' => $type->value, 'label' => $type->label()], MaintenanceType::cases()),
            'priorities' => array_map(fn (MaintenancePriority $priority): array => ['value' => $priority->value, 'label' => $priority->label()], MaintenancePriority::cases()),
            'handledBy' => array_map(fn (HandledBy $handledBy): array => ['value' => $handledBy->value, 'label' => $handledBy->label()], HandledBy::cases()),
        ];
    }
}
