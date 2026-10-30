<?php

namespace App\Actions\Herd;

use App\Enums\EmploymentStatus;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Support\Carbon;

/**
 * Finds the local employee for a HERD identity (by NIK, then email) and refreshes its HERD-owned fields.
 */
class SyncEmployeeFromHerd
{
    /**
     * @param  array{herd_id: string, nik: string, name: string, email: ?string, job_title: ?string, department: ?string}  $identity
     */
    public function handle(array $identity): Employee
    {
        $employee = Employee::query()->where('employee_number', $identity['nik'])->first()
            ?? ($identity['email'] === null ? null : Employee::query()->where('email', 'ilike', $identity['email'])->first())
            ?? new Employee([
                'herd_id' => $identity['herd_id'],
                'employee_number' => $identity['nik'],
                'employment_status' => EmploymentStatus::Active,
            ]);

        $departmentId = $identity['department'] === null
            ? null
            : Department::query()->where('name', 'ilike', $identity['department'])->value('id');

        $employee->fill(array_filter([
            'name' => $identity['name'],
            'email' => $identity['email'],
            'job_title' => $identity['job_title'],
            'department_id' => $departmentId,
        ], fn (mixed $value): bool => $value !== null && $value !== ''));

        $employee->synced_at = Carbon::now();
        $employee->save();

        return $employee;
    }

    /**
     * @param  array<string, mixed>  $claims  OIDC userinfo claims
     * @return array{herd_id: string, nik: string, name: string, email: ?string, job_title: ?string, department: ?string}
     */
    public static function fromUserInfo(array $claims): array
    {
        return [
            'herd_id' => (string) $claims['sub'],
            'nik' => (string) $claims['nik'],
            'name' => (string) ($claims['name'] ?? $claims['nik']),
            'email' => $claims['email'] ?? $claims['upn'] ?? null,
            'job_title' => $claims['job_title'] ?? null,
            'department' => $claims['department'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $user  Row from GET /applications/{slug}/users
     * @return array{herd_id: string, nik: string, name: string, email: ?string, job_title: ?string, department: ?string}
     */
    public static function fromGrantedUser(array $user): array
    {
        return [
            'herd_id' => (string) $user['id'],
            'nik' => (string) $user['employeeNik'],
            'name' => (string) $user['name'],
            'email' => $user['email'] ?? $user['upn'] ?? null,
            'job_title' => $user['jobTitle'] ?? null,
            'department' => $user['department'] ?? null,
        ];
    }
}
