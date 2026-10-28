<?php

use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Asset;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

it('computes component stock from active installations', function (): void {
    $component = Component::factory()->create(['qty_total' => 10]);
    $employee = Employee::factory()->create();
    $asset = Asset::factory()->create();

    ComponentInstallation::create([
        'component_id' => $component->id,
        'asset_id' => $asset->id,
        'qty' => 3,
        'is_factory' => false,
        'installed_at' => now(),
    ]);

    ComponentInstallation::create([
        'component_id' => $component->id,
        'asset_id' => $asset->id,
        'qty' => 2,
        'is_factory' => true,
        'installed_at' => now(),
    ]);

    $stock = DB::table('component_stock')->where('component_id', $component->id)->first();

    expect((int) $stock->qty_installed)->toBe(3)
        ->and((int) $stock->qty_available)->toBe(7);
});

it('computes accessory stock from active checkouts', function (): void {
    $accessory = Accessory::factory()->create(['qty_total' => 20]);
    $employee = Employee::factory()->create();

    AccessoryCheckout::create([
        'accessory_id' => $accessory->id,
        'employee_id' => $employee->id,
        'qty' => 4,
        'checked_out_at' => now(),
    ]);

    $returned = AccessoryCheckout::create([
        'accessory_id' => $accessory->id,
        'employee_id' => $employee->id,
        'qty' => 2,
        'checked_out_at' => now()->subDay(),
        'checked_in_at' => now(),
    ]);

    expect($returned->isActive())->toBeFalse();

    $stock = DB::table('accessory_stock')->where('accessory_id', $accessory->id)->first();

    expect((int) $stock->qty_out)->toBe(4)
        ->and((int) $stock->qty_available)->toBe(16);
});
