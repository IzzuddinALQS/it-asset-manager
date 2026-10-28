<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\HandledBy;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceType;
use App\Models\Component;
use App\Models\Employee;
use App\Models\Supplier;

/**
 * Select options for opening and closing maintenance tickets, shared by the board,
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

    /**
     * Service vendors first, since they are the usual pick for a ticket.
     *
     * @return array<int, array{id: int, name: string, is_service_vendor: bool}>
     */
    private function supplierOptions(): array
    {
        return Supplier::query()
            ->orderByDesc('is_service_vendor')
            ->orderBy('name')
            ->get(['id', 'name', 'is_service_vendor'])
            ->map(fn (Supplier $supplier): array => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'is_service_vendor' => $supplier->is_service_vendor,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, employee_number: string, department: string|null}>
     */
    private function reporterOptions(): array
    {
        return Employee::query()
            ->active()
            ->with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'employee_number', 'department_id'])
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_number' => $employee->employee_number,
                'department' => $employee->department?->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, name: string, spec: string|null, qty_available: int}>
     */
    private function sparePartOptions(): array
    {
        return Component::query()
            ->with('stock')
            ->orderBy('name')
            ->get(['id', 'name', 'spec', 'qty_total'])
            ->map(fn (Component $component): array => [
                'id' => $component->id,
                'name' => $component->name,
                'spec' => $component->spec,
                'qty_available' => $component->stock->qty_available ?? $component->qty_total,
            ])
            ->all();
    }
}
