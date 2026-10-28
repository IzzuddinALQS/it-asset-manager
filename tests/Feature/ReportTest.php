<?php

use App\Enums\MaintenanceStatus;
use App\Enums\ReportType;
use App\Enums\Role;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\Location;
use App\Models\Maintenance;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\travelTo;

/**
 * Parse a streamed CSV export into rows, without the UTF-8 BOM.
 *
 * @return list<list<string>>
 */
function csvRows(string $csv): array
{
    $lines = preg_split('/\r?\n/', trim(preg_replace('/^\xEF\xBB\xBF/', '', $csv)));

    return array_map(fn (string $line): array => str_getcsv($line, escape: ''), $lines);
}

/**
 * The rows a report page shows (header, data rows and totals) as the CSV would write them.
 *
 * @param  array{columns: list<array{key: string, label: string}>, rows: list<array<string, mixed>>, totals: array<string, mixed>|null}  $report
 * @return list<list<string>>
 */
function reportAsCsvRows(array $report): array
{
    $keys = array_column($report['columns'], 'key');
    $rows = [...$report['rows'], ...($report['totals'] === null ? [] : [$report['totals']])];

    return [
        array_column($report['columns'], 'label'),
        ...array_map(fn (array $row): array => array_map(fn (string $key): string => (string) ($row[$key] ?? ''), $keys), $rows),
    ];
}

beforeEach(function (): void {
    travelTo('2026-10-28');
});

it('counts assets per status within the purchase period', function (): void {
    Asset::factory()->count(2)->create(['purchase_date' => '2026-03-01', 'purchase_cost' => 10_000_000]);
    Asset::factory()->deployed()->create(['purchase_date' => '2026-04-01', 'purchase_cost' => 5_000_000]);
    Asset::factory()->create(['purchase_date' => '2025-01-01', 'purchase_cost' => 99_000_000]);

    actingAs(userWithRole(Role::Viewer))
        ->get('/reports?report=assets-by-status&from=2026-01-01&to=2026-12-31')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Reports/Index')
            ->where('filters.from', '2026-01-01')
            ->where('report.rows.1', ['group' => 'Siap Pakai', 'asset_count' => 2, 'total_cost' => 20_000_000])
            ->where('report.rows.2', ['group' => 'Dipakaikan', 'asset_count' => 1, 'total_cost' => 5_000_000])
            ->where('report.totals.asset_count', 3)
            ->where('report.totals.total_cost', 25_000_000));
});

it('groups assets per category and location', function (): void {
    $laptop = AssetModel::factory()->for(Category::factory()->state(['name' => 'Laptop']))->create();
    $office = Location::factory()->create(['name' => 'Kantor Pusat']);
    Asset::factory()->count(2)->for($laptop)->create(['location_id' => $office->id]);
    Asset::factory()->for($laptop)->create();

    $viewer = userWithRole(Role::Viewer);

    actingAs($viewer)
        ->get('/reports?report=assets-by-category')
        ->assertInertia(fn ($page) => $page
            ->where('report.rows.0.group', 'Laptop')
            ->where('report.rows.0.asset_count', 3));

    actingAs($viewer)
        ->get('/reports?report=assets-by-location')
        ->assertInertia(fn ($page) => $page
            ->where('report.rows.0.group', 'Kantor Pusat')
            ->where('report.rows.0.asset_count', 2)
            ->where('report.rows.1.group', 'Tanpa Lokasi')
            ->where('report.rows.1.asset_count', 1));
});

it('depreciates asset value straight-line and skips retired assets', function (): void {
    $model = AssetModel::factory()->create(['depreciation_months' => 48]);
    Asset::factory()->for($model)->create(['asset_tag' => 'LT-00001', 'purchase_date' => '2024-10-28', 'purchase_cost' => 12_000_000]);
    Asset::factory()->for($model)->retired()->create();

    actingAs(userWithRole(Role::Viewer))
        ->get('/reports?report=asset-value')
        ->assertInertia(fn ($page) => $page
            ->has('report.rows', 1)
            ->where('report.rows.0.asset_tag', 'LT-00001')
            ->where('report.rows.0.book_value', 6_000_000));
});

it('reports license seat utilization', function (): void {
    $license = License::factory()->create(['name' => 'Adobe CC', 'seats_total' => 4, 'expires_at' => '2027-01-31']);
    LicenseSeat::factory()->count(3)->for($license)->create();
    LicenseSeat::factory()->for($license)->create(['released_at' => now()]);
    License::factory()->create(['expires_at' => '2028-06-01']);

    actingAs(userWithRole(Role::Viewer))
        ->get('/reports?report=license-utilization&from=2026-11-01&to=2027-12-31')
        ->assertInertia(fn ($page) => $page
            ->has('report.rows', 1)
            ->where('report.rows.0.seats_used', 3)
            ->where('report.rows.0.utilization', 75));
});

it('sums the actual cost of maintenance completed in the period', function (): void {
    Maintenance::factory()->create(['status' => MaintenanceStatus::Done, 'completed_at' => '2026-09-10 10:00', 'actual_cost' => 750_000]);
    Maintenance::factory()->create(['status' => MaintenanceStatus::Done, 'completed_at' => '2026-09-30 23:00', 'actual_cost' => 250_000]);
    Maintenance::factory()->create(['status' => MaintenanceStatus::Done, 'completed_at' => '2026-10-01 08:00', 'actual_cost' => 900_000]);
    Maintenance::factory()->create(['status' => MaintenanceStatus::InProgress, 'actual_cost' => 400_000]);

    actingAs(userWithRole(Role::Viewer))
        ->get('/reports?report=maintenance-cost&from=2026-09-01&to=2026-09-30')
        ->assertInertia(fn ($page) => $page
            ->has('report.rows', 2)
            ->where('report.totals.actual_cost', 1_000_000));
});

it('reports available stock and flags low stock', function (): void {
    $accessory = Accessory::factory()->create(['qty_total' => 6, 'min_qty' => 5, 'unit_cost' => 100_000]);
    AccessoryCheckout::factory()->for($accessory)->create(['qty' => 2]);

    actingAs(userWithRole(Role::Viewer))
        ->get('/reports?report=stock&from=2026-01-01')
        ->assertInertia(fn ($page) => $page
            ->where('filters.from', '')
            ->where('report.rows.0.qty_available', 4)
            ->where('report.rows.0.low_stock', 'Ya')
            ->where('report.rows.0.stock_value', 600_000));
});

it('exports exactly the rows of the filtered view', function (ReportType $type): void {
    Asset::factory()->count(3)->create(['purchase_date' => '2026-05-01']);
    Asset::factory()->deployed()->create(['purchase_date' => '2025-05-01']);
    LicenseSeat::factory()->for(License::factory()->state(['expires_at' => '2026-12-01']))->create();
    Maintenance::factory()->create(['status' => MaintenanceStatus::Done, 'completed_at' => '2026-06-01', 'actual_cost' => 1_500_000.5]);
    Accessory::factory()->create(['name' => 'Kabel "HDMI", 2m']);

    $query = "report={$type->value}&from=2026-01-01&to=2026-12-31";
    $viewer = userWithRole(Role::Viewer);

    $report = actingAs($viewer)->get("/reports?{$query}")->viewData('page')['props']['report'];

    $response = actingAs($viewer)->get("/reports/export?{$query}");

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertDownload("laporan-{$type->value}".($type === ReportType::Stock ? '' : '_20260101-20261231').'.csv');

    expect(csvRows($response->streamedContent()))->toBe(reportAsCsvRows($report))
        ->and(count($report['rows']))->toBeGreaterThan(0);
})->with(ReportType::cases());

it('rejects a period that ends before it starts', function (): void {
    actingAs(userWithRole(Role::Viewer))
        ->get('/reports/export?report=asset-value&from=2026-05-01&to=2026-04-01')
        ->assertSessionHasErrors(['to' => 'Tanggal akhir tidak boleh sebelum tanggal awal.']);
});
