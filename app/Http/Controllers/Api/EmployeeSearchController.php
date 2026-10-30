<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Type-ahead search for the employee pickers, so dialogs never load the whole directory.
 */
class EmployeeSearchController extends Controller
{
    public const int LIMIT = 20;

    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'without_user' => ['nullable', 'boolean'],
            'id' => ['nullable', 'integer'],
        ]);

        $term = trim($validated['q'] ?? '');

        $employees = Employee::query()
            ->active()
            ->with('department:id,name')
            ->when($request->boolean('without_user'), fn ($query) => $query->whereDoesntHave('user'))
            ->when(isset($validated['id']), fn ($query) => $query->whereKey($validated['id']))
            ->when($term !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'ilike', "%{$term}%")
                ->orWhere('employee_number', 'ilike', "%{$term}%")))
            ->orderBy('name')
            ->limit(self::LIMIT)
            ->get(['id', 'name', 'employee_number', 'department_id']);

        return response()->json([
            'data' => $employees->map(fn (Employee $employee): array => [
                'value' => (string) $employee->id,
                'label' => $employee->name,
                'hint' => collect([$employee->employee_number, $employee->department?->name])->filter()->implode(' · '),
            ])->all(),
        ]);
    }
}
