<?php

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Models\Accessory;
use App\Models\AccessoryStock;
use App\Models\Asset;
use App\Models\Employee;

function writeAssetCsv(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'assets').'.csv';
    file_put_contents($path, $contents);

    return $path;
}

$csv = <<<'CSV'
NO FA,IMEI,USER,SN,NO,CABANG,ALAT,TYPE,MERK,AKSESORIS
3GABU0320261845,352673730073911,HELMI YUSUF,RRGYC088NKE,81361345334,JKT,TAB,GALAXY A11+ 5G,SAMSUNG,HARDCOVER TAB + CHARGER 25 WATT
3GABU0320261849,352673730038591,RAGATAMA ISMAWAN,RRGYC056DHK,81361344776,JKT,TAB,GALAXY A11+ 5G,SAMSUNG,HARDCOVER TAB + CHARGER 25 WATT
,,,,,,,,,
,,,,,,,,S,
CSV;

it('imports each row as a deployed asset with its holder, history and accessories', function () use ($csv): void {
    $this->artisan('assets:import', ['file' => writeAssetCsv($csv)])
        ->expectsOutput('2 inventaris diimpor, 2 baris dilewati.')
        ->assertSuccessful();

    $asset = Asset::query()->with('assetModel.manufacturer', 'assetModel.category', 'assignedEmployee', 'location')
        ->where('asset_tag', '3GABU0320261845')->sole();

    expect($asset->status)->toBe(AssetStatus::Deployed)
        ->and($asset->serial_number)->toBe('RRGYC088NKE')
        ->and($asset->imei)->toBe('352673730073911')
        ->and($asset->phone_number)->toBe('081361345334')
        ->and($asset->assetModel->name)->toBe('GALAXY A11+ 5G')
        ->and($asset->assetModel->manufacturer->name)->toBe('Samsung')
        ->and($asset->assetModel->category->name)->toBe('Tablet')
        ->and($asset->location->name)->toBe('JKT')
        ->and($asset->assignedEmployee->name)->toBe('Helmi Yusuf')
        ->and($asset->assignedEmployee->herd_id)->toBe('import:helmi-yusuf')
        ->and($asset->logs()->reorder('id')->pluck('action')->all())->toBe([AssetAction::Created, AssetAction::Checkout]);

    $charger = Accessory::query()->where('name', 'Charger 25 Watt')->sole();

    expect(Accessory::query()->pluck('name')->sort()->values()->all())->toBe(['Charger 25 Watt', 'Hardcover Tab'])
        ->and($charger->qty_total)->toBe(2)
        ->and(AccessoryStock::query()->find($charger->id)->qty_available)->toBe(0)
        ->and($asset->assignedEmployee->accessoryCheckouts()->count())->toBe(2);
});

it('skips rows already imported so the file can be re-run', function () use ($csv): void {
    $path = writeAssetCsv($csv);

    $this->artisan('assets:import', ['file' => $path])->assertSuccessful();
    $this->artisan('assets:import', ['file' => $path])
        ->expectsOutput('0 inventaris diimpor, 4 baris dilewati.')
        ->assertSuccessful();

    expect(Asset::query()->count())->toBe(2)
        ->and(Employee::query()->count())->toBe(2)
        ->and(Accessory::query()->where('name', 'Hardcover Tab')->value('qty_total'))->toBe(2);
});

it('fails when the file does not exist', function (): void {
    $this->artisan('assets:import', ['file' => 'missing.csv'])->assertFailed();
});
