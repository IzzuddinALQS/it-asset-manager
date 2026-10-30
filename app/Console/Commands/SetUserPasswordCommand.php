<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:set-password {nik : NIK karyawan} {--role= : Beri/ubah peran (admin, technician, viewer)}')]
#[Description('Set a local password for an employee, creating the app user if needed (bootstrap the first admin)')]
class SetUserPasswordCommand extends Command
{
    public function handle(): int
    {
        $employee = Employee::query()->where('employee_number', $this->argument('nik'))->first();

        if ($employee === null) {
            $this->error("Karyawan dengan NIK {$this->argument('nik')} tidak ditemukan.");

            return self::FAILURE;
        }

        $role = $this->option('role') === null ? null : Role::tryFrom($this->option('role'));

        if ($this->option('role') !== null && $role === null) {
            $this->error('Peran harus admin, technician, atau viewer.');

            return self::FAILURE;
        }

        $password = $this->secret('Password baru (min. 8 karakter)');

        if (! is_string($password) || mb_strlen($password) < 8) {
            $this->error('Password minimal 8 karakter.');

            return self::FAILURE;
        }

        $user = User::query()->firstOrNew(['employee_id' => $employee->id]);
        $user->role = $role ?? $user->role ?? Role::Viewer;
        $user->password = $password;
        $user->save();

        $this->info("Password lokal {$employee->name} disimpan ({$user->role->label()}).");

        return self::SUCCESS;
    }
}
