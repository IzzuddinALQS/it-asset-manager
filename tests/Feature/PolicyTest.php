<?php

use App\Enums\Role;
use App\Models\Accessory;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Employee;
use App\Models\License;
use App\Models\Maintenance;
use App\Models\User;

const EVERYONE = ['admin', 'technician', 'viewer'];
const OPERATORS = ['admin', 'technician'];
const ADMINS = ['admin'];

dataset('abilities', [
    'asset viewAny' => [Asset::class, 'viewAny', EVERYONE],
    'asset view' => [Asset::class, 'view', EVERYONE],
    'asset create' => [Asset::class, 'create', ADMINS],
    'asset update' => [Asset::class, 'update', ADMINS],
    'asset delete' => [Asset::class, 'delete', ADMINS],
    'asset tag' => [Asset::class, 'tag', OPERATORS],
    'asset checkout' => [Asset::class, 'checkout', OPERATORS],
    'asset checkin' => [Asset::class, 'checkin', OPERATORS],
    'asset retire' => [Asset::class, 'retire', ADMINS],
    'license viewAny' => [License::class, 'viewAny', EVERYONE],
    'license view' => [License::class, 'view', EVERYONE],
    'license create' => [License::class, 'create', ADMINS],
    'license update' => [License::class, 'update', ADMINS],
    'license delete' => [License::class, 'delete', ADMINS],
    'license assignSeat' => [License::class, 'assignSeat', OPERATORS],
    'component viewAny' => [Component::class, 'viewAny', EVERYONE],
    'component view' => [Component::class, 'view', EVERYONE],
    'component create' => [Component::class, 'create', ADMINS],
    'component update' => [Component::class, 'update', ADMINS],
    'component delete' => [Component::class, 'delete', ADMINS],
    'component install' => [Component::class, 'install', OPERATORS],
    'accessory viewAny' => [Accessory::class, 'viewAny', EVERYONE],
    'accessory view' => [Accessory::class, 'view', EVERYONE],
    'accessory create' => [Accessory::class, 'create', ADMINS],
    'accessory update' => [Accessory::class, 'update', ADMINS],
    'accessory delete' => [Accessory::class, 'delete', ADMINS],
    'accessory checkout' => [Accessory::class, 'checkout', OPERATORS],
    'maintenance viewAny' => [Maintenance::class, 'viewAny', EVERYONE],
    'maintenance view' => [Maintenance::class, 'view', EVERYONE],
    'maintenance create' => [Maintenance::class, 'create', OPERATORS],
    'maintenance update' => [Maintenance::class, 'update', OPERATORS],
    'maintenance delete' => [Maintenance::class, 'delete', ADMINS],
    'alert viewAny' => [Alert::class, 'viewAny', EVERYONE],
    'alert view' => [Alert::class, 'view', EVERYONE],
    'alert resolve' => [Alert::class, 'resolve', OPERATORS],
    'employee viewAny' => [Employee::class, 'viewAny', EVERYONE],
    'employee view' => [Employee::class, 'view', EVERYONE],
    'employee update' => [Employee::class, 'update', []],
    'employee delete' => [Employee::class, 'delete', []],
]);

it('grants each ability only to the expected roles', function (string $model, string $ability, array $allowedRoles): void {
    $subject = in_array($ability, ['viewAny', 'create'], true) ? $model : new $model;

    foreach (Role::cases() as $role) {
        $user = User::factory()->make(['role' => $role]);

        expect($user->can($ability, $subject))->toBe(
            in_array($role->value, $allowedRoles, true),
            "{$role->value} → {$ability} ".class_basename($model),
        );
    }
})->with('abilities');
