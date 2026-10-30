<?php

use App\Enums\Role;
use App\Models\Asset;
use App\Models\AssetModel;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

it('stores, shows and updates the IMEI and phone number', function (): void {
    $admin = userWithRole(Role::Admin);

    actingAs($admin)->post('/assets', [
        'asset_model_id' => AssetModel::factory()->create()->id,
        'imei' => '352673730073911',
        'phone_number' => '081361345334',
    ])->assertRedirect();

    $asset = Asset::query()->latest('id')->sole();

    expect($asset->imei)->toBe('352673730073911')
        ->and($asset->phone_number)->toBe('081361345334');

    actingAs($admin)->get("/assets/{$asset->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('asset.imei', '352673730073911')
            ->where('asset.phone_number', '081361345334'));

    actingAs($admin)->put("/assets/{$asset->id}", [
        'asset_model_id' => $asset->asset_model_id,
        'imei' => '352673730038591',
        'phone_number' => '081361344776',
    ])->assertRedirect();

    expect($asset->fresh())
        ->imei->toBe('352673730038591')
        ->phone_number->toBe('081361344776');
});

it('rejects malformed or duplicate device numbers', function (array $payload, string $field): void {
    Asset::factory()->create(['imei' => '352673730073911']);

    actingAs(userWithRole(Role::Admin))
        ->post('/assets', ['asset_model_id' => AssetModel::factory()->create()->id, ...$payload])
        ->assertSessionHasErrors($field);
})->with([
    'imei too short' => [['imei' => '35267373'], 'imei'],
    'imei with letters' => [['imei' => '35267373007391A'], 'imei'],
    'duplicate imei' => [['imei' => '352673730073911'], 'imei'],
    'phone without leading zero' => [['phone_number' => '81361345334'], 'phone_number'],
]);

it('finds assets by IMEI or phone number from the list search', function (): void {
    $asset = Asset::factory()->create(['imei' => '352673730047386', 'phone_number' => '081361344767']);
    Asset::factory()->create();

    foreach (['730047386', '081361344767'] as $term) {
        actingAs(userWithRole(Role::Viewer))->get('/assets?search='.$term)
            ->assertInertia(fn (Assert $page) => $page
                ->has('assets.data', 1)
                ->where('assets.data.0.id', $asset->id));
    }
});
