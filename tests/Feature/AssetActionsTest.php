<?php

use App\Actions\Assets\CheckinAsset;
use App\Actions\Assets\CheckoutAsset;
use App\Actions\Assets\CreateAsset;
use App\Actions\Assets\RetireAsset;
use App\Actions\Assets\TagAsset;
use App\Actions\Assets\UpdateAsset;
use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Enums\RetireReason;
use App\Enums\Role;
use App\Exceptions\InvalidAssetTransitionException;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Employee;
use App\Models\LicenseSeat;
use App\Models\Location;

it('runs the full lifecycle and records every step in the history', function (): void {
    $model = AssetModel::factory()->for(Category::factory()->state(['tag_prefix' => 'LT']))->create();
    $employee = Employee::factory()->create();

    $asset = app(CreateAsset::class)->handle(['asset_model_id' => $model->id, 'serial_number' => 'SN-LIFE-1'], userWithRole(Role::Admin));
    expect($asset->status)->toBe(AssetStatus::Pending)
        ->and($asset->asset_tag)->toStartWith('TMP-');

    $asset = app(TagAsset::class)->handle($asset, userWithRole(Role::Admin));
    expect($asset->status)->toBe(AssetStatus::Ready)
        ->and($asset->asset_tag)->toBe('LT-00001');

    $asset = app(CheckoutAsset::class)->handle($asset, ['assigned_employee_id' => $employee->id], userWithRole(Role::Admin));
    expect($asset->status)->toBe(AssetStatus::Deployed)
        ->and($asset->assigned_employee_id)->toBe($employee->id)
        ->and($asset->location_id)->toBe($employee->location_id);

    $asset = app(CheckinAsset::class)->handle($asset, ['to_status' => 'ready'], userWithRole(Role::Admin));
    expect($asset->status)->toBe(AssetStatus::Ready)
        ->and($asset->assigned_employee_id)->toBeNull();

    $asset = app(RetireAsset::class)->handle($asset, ['retire_reason' => 'end_of_life'], userWithRole(Role::Admin));
    expect($asset->status)->toBe(AssetStatus::Retired)
        ->and($asset->retire_reason)->toBe(RetireReason::EndOfLife)
        ->and($asset->retired_at)->not->toBeNull();

    $history = $asset->logs()->reorder('id')->get();

    expect($history->pluck('action')->all())->toBe([
        AssetAction::Created, AssetAction::Tagged, AssetAction::Checkout, AssetAction::Checkin, AssetAction::Retired,
    ])
        ->and($history->pluck('to_status')->all())->toBe([
            AssetStatus::Pending, AssetStatus::Ready, AssetStatus::Deployed, AssetStatus::Ready, AssetStatus::Retired,
        ])
        ->and($history->every(fn ($log): bool => $log->performed_by === userWithRole(Role::Admin)->id))->toBeTrue()
        ->and($history[2]->employee_id)->toBe($employee->id);
});

it('numbers tags per category prefix, counting soft-deleted assets', function (): void {
    $category = Category::factory()->create(['tag_prefix' => 'MN']);
    $model = AssetModel::factory()->for($category)->create();
    Asset::factory()->for($model)->create(['asset_tag' => 'MN-00007'])->delete();

    $pending = Asset::factory()->pending()->for($model)->create();

    expect(app(TagAsset::class)->handle($pending, userWithRole(Role::Admin))->asset_tag)->toBe('MN-00008');
});

it('checks an asset out to a location', function (): void {
    $location = Location::factory()->create();
    $asset = Asset::factory()->create();

    $asset = app(CheckoutAsset::class)->handle($asset, ['assigned_location_id' => $location->id], userWithRole(Role::Admin));

    expect($asset->assigned_location_id)->toBe($location->id)
        ->and($asset->assigned_employee_id)->toBeNull()
        ->and($asset->logs()->first()->location_id)->toBe($location->id);
});

it('returns a deployed asset straight into maintenance', function (): void {
    $asset = Asset::factory()->deployed()->create();

    $asset = app(CheckinAsset::class)->handle($asset, ['to_status' => 'maintenance', 'note' => 'Layar retak'], userWithRole(Role::Admin));

    expect($asset->status)->toBe(AssetStatus::Maintenance)
        ->and($asset->logs()->first()->note)->toBe('Layar retak');
});

it('releases license seats when an asset is retired', function (): void {
    $asset = Asset::factory()->create();
    LicenseSeat::factory()->create(['employee_id' => null, 'asset_id' => $asset->id]);

    app(RetireAsset::class)->handle($asset, ['retire_reason' => 'broken'], userWithRole(Role::Admin));

    expect($asset->licenseSeats()->whereNull('released_at')->count())->toBe(0)
        ->and($asset->logs()->first()->meta['released_license_seats'])->toBe(1);
});

it('logs which fields changed on update and skips untouched saves', function (): void {
    $asset = Asset::factory()->create(['notes' => null]);

    app(UpdateAsset::class)->handle($asset, ['notes' => 'Baterai sudah diganti', 'status' => 'retired'], userWithRole(Role::Admin));
    app(UpdateAsset::class)->handle($asset, ['notes' => 'Baterai sudah diganti'], userWithRole(Role::Admin));

    expect($asset->fresh()->status)->toBe(AssetStatus::Ready)
        ->and($asset->logs()->count())->toBe(1)
        ->and($asset->logs()->first()->meta['changed_fields'])->toBe(['notes']);
});

it('rejects transitions the lifecycle forbids', function (string $action, Closure $makeAsset, array $data): void {
    $asset = $makeAsset();
    $statusBefore = $asset->status;

    expect(fn () => match ($action) {
        'tag' => app(TagAsset::class)->handle($asset, userWithRole(Role::Admin)),
        'checkout' => app(CheckoutAsset::class)->handle($asset, $data, userWithRole(Role::Admin)),
        'checkin' => app(CheckinAsset::class)->handle($asset, $data, userWithRole(Role::Admin)),
        'retire' => app(RetireAsset::class)->handle($asset, $data, userWithRole(Role::Admin)),
        default => throw new LogicException("Unknown action {$action}"),
    })->toThrow(InvalidAssetTransitionException::class);

    expect($asset->fresh()->status)->toBe($statusBefore)
        ->and($asset->logs()->count())->toBe(0);
})->with([
    'tag a ready asset' => ['tag', fn () => Asset::factory()->create(), []],
    'checkout a pending asset' => ['checkout', fn () => Asset::factory()->pending()->create(), ['assigned_location_id' => 1]],
    'checkout a deployed asset' => ['checkout', fn () => Asset::factory()->deployed()->create(), ['assigned_location_id' => 1]],
    'checkout in maintenance' => ['checkout', fn () => Asset::factory()->maintenance()->create(), ['assigned_location_id' => 1]],
    'checkin a ready asset' => ['checkin', fn () => Asset::factory()->create(), ['to_status' => 'ready']],
    'checkin into retired' => ['checkin', fn () => Asset::factory()->deployed()->create(), ['to_status' => 'retired']],
    'checkin from maintenance' => ['checkin', fn () => Asset::factory()->maintenance()->create(['assigned_employee_id' => Employee::factory()]), ['to_status' => 'ready']],
    'retire a pending asset' => ['retire', fn () => Asset::factory()->pending()->create(), ['retire_reason' => 'lost']],
    'retire twice' => ['retire', fn () => Asset::factory()->retired()->create(), ['retire_reason' => 'lost']],
]);

it('refuses to check out without a recipient or to an inactive employee', function (array $data): void {
    $asset = Asset::factory()->create();

    expect(fn () => app(CheckoutAsset::class)->handle($asset, $data, userWithRole(Role::Admin)))
        ->toThrow(InvalidAssetTransitionException::class);

    expect($asset->fresh()->status)->toBe(AssetStatus::Ready);
})->with([
    'no recipient' => [fn () => []],
    'resigned employee' => [fn () => ['assigned_employee_id' => Employee::factory()->resigned()->create()->id]],
]);
