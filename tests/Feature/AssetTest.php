<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;

it('creates an asset with its model, category and manufacturer', function (): void {
    $asset = Asset::factory()->create();

    expect($asset->assetModel)->toBeInstanceOf(AssetModel::class)
        ->and($asset->assetModel->category)->toBeInstanceOf(Category::class)
        ->and($asset->status)->toBe(AssetStatus::Ready);
});

it('filters available and deployed assets', function (): void {
    Asset::factory()->count(3)->create();
    Asset::factory()->deployed()->count(2)->create();

    expect(Asset::available()->count())->toBe(3)
        ->and(Asset::deployed()->count())->toBe(2);
});

it('assigns a deployed asset to exactly one holder', function (): void {
    $employee = Employee::factory()->create();
    $location = Location::factory()->create();

    $asset = Asset::factory()->create([
        'status' => AssetStatus::Deployed,
        'assigned_employee_id' => $employee->id,
    ]);

    expect($asset->assignedEmployee->is($employee))->toBeTrue()
        ->and($asset->assignedLocation)->toBeNull();
});

it('soft deletes assets and keeps the serial unique only for active rows', function (): void {
    $first = Asset::factory()->create(['serial_number' => 'SN-DUPLICATE']);

    $first->delete();

    $second = Asset::factory()->create(['serial_number' => 'SN-DUPLICATE']);

    expect($second->exists)->toBeTrue()
        ->and(Asset::withTrashed()->count())->toBe(2);
});

it('scopes active employees only', function (): void {
    Employee::factory()->count(2)->create();
    Employee::factory()->resigned()->create();

    expect(Employee::active()->count())->toBe(2)
        ->and(Employee::needsOffboarding()->count())->toBe(1);
});
