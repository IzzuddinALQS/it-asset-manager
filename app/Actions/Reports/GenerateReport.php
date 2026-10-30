<?php

namespace App\Actions\Reports;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ReportType;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Component;
use App\Models\License;
use App\Models\Maintenance;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Builds a report's columns, rows and totals. The report page and the CSV export
 * both render this exact output, so an export always matches the filtered view.
 *
 * @phpstan-type Column array{key: string, label: string, type: 'text'|'number'|'currency'|'percent'|'date'}
 * @phpstan-type Row array<string, string|int|float|null>
 * @phpstan-type ReportData array{columns: list<Column>, rows: list<Row>, totals: Row|null}
 */
class GenerateReport
{
    /**
     * @return ReportData
     */
    public function handle(ReportType $type, ?Carbon $from = null, ?Carbon $to = null): array
    {
        return match ($type) {
            ReportType::AssetsByStatus => $this->assetsByStatus($from, $to),
            ReportType::AssetsByCategory => $this->assetsGroupedBy(
                $from,
                $to,
                'Kategori',
                fn (Builder $query) => $query
                    ->join('asset_models', 'asset_models.id', '=', 'assets.asset_model_id')
                    ->join('categories', 'categories.id', '=', 'asset_models.category_id'),
                'categories.name',
            ),
            ReportType::AssetsByLocation => $this->assetsGroupedBy(
                $from,
                $to,
                'Lokasi',
                fn (Builder $query) => $query->leftJoin('locations', 'locations.id', '=', 'assets.location_id'),
                'locations.name',
            ),
            ReportType::AssetValue => $this->assetValue($from, $to),
            ReportType::LicenseUtilization => $this->licenseUtilization($from, $to),
            ReportType::MaintenanceCost => $this->maintenanceCost($from, $to),
            ReportType::Stock => $this->stock(),
        };
    }

    /**
     * @return ReportData
     */
    private function assetsByStatus(?Carbon $from, ?Carbon $to): array
    {
        $groups = $this->assetQuery($from, $to)
            ->toBase()
            ->selectRaw('status as grp, count(*) as asset_count, coalesce(sum(purchase_cost), 0) as total_cost')
            ->groupBy('status')
            ->get()
            ->keyBy('grp');

        $rows = array_map(fn (AssetStatus $status): array => [
            'group' => $status->label(),
            'asset_count' => (int) ($groups[$status->value]->asset_count ?? 0),
            'total_cost' => round((float) ($groups[$status->value]->total_cost ?? 0), 2),
        ], AssetStatus::cases());

        return $this->groupedReport('Status', $rows);
    }

    /**
     * @param  callable(Builder<Asset>): Builder<Asset>  $join
     * @return ReportData
     */
    private function assetsGroupedBy(?Carbon $from, ?Carbon $to, string $label, callable $join, string $nameColumn): array
    {
        $rows = $join($this->assetQuery($from, $to))
            ->toBase()
            ->selectRaw("{$nameColumn} as grp, count(*) as asset_count, coalesce(sum(assets.purchase_cost), 0) as total_cost")
            ->groupBy($nameColumn)
            ->orderByRaw("{$nameColumn} is null, {$nameColumn}")
            ->get()
            ->map(fn (object $group): array => [
                'group' => $group->grp ?? "Tanpa {$label}",
                'asset_count' => (int) $group->asset_count,
                'total_cost' => round((float) $group->total_cost, 2),
            ])
            ->all();

        return $this->groupedReport($label, $rows);
    }

    /**
     * @param  list<Row>  $rows
     * @return ReportData
     */
    private function groupedReport(string $label, array $rows): array
    {
        return [
            'columns' => [
                ['key' => 'group', 'label' => $label, 'type' => 'text'],
                ['key' => 'asset_count', 'label' => 'Jumlah inventaris', 'type' => 'number'],
                ['key' => 'total_cost', 'label' => 'Nilai pembelian', 'type' => 'currency'],
            ],
            'rows' => $rows,
            'totals' => [
                'group' => 'Total',
                'asset_count' => array_sum(array_column($rows, 'asset_count')),
                'total_cost' => round(array_sum(array_column($rows, 'total_cost')), 2),
            ],
        ];
    }

    /**
     * Straight-line depreciation over the model's lifetime, valued at the end of the period.
     *
     * @return ReportData
     */
    private function assetValue(?Carbon $from, ?Carbon $to): array
    {
        $valuedAt = ($to ?? Carbon::today())->copy()->endOfDay();

        $rows = $this->assetQuery($from, $to)
            ->whereNot('status', AssetStatus::Retired)
            ->with('assetModel.category:id,name')
            ->orderBy('asset_tag')
            ->get()
            ->map(function (Asset $asset) use ($valuedAt): array {
                $cost = (float) ($asset->purchase_cost ?? 0);
                $lifetime = $asset->assetModel?->depreciation_months;
                $bookValue = $cost;

                if ($lifetime !== null && $lifetime > 0 && $asset->purchase_date !== null) {
                    $elapsed = max(0, (int) floor($asset->purchase_date->diffInMonths($valuedAt)));
                    $bookValue = $cost * max(0, $lifetime - $elapsed) / $lifetime;
                }

                return [
                    'asset_tag' => $asset->asset_tag,
                    'model' => $asset->assetModel?->name,
                    'category' => $asset->category_name,
                    'status' => $asset->status->label(),
                    'purchase_date' => $asset->purchase_date?->toDateString(),
                    'depreciation_months' => $lifetime,
                    'purchase_cost' => round($cost, 2),
                    'book_value' => round($bookValue, 2),
                ];
            })
            ->all();

        return [
            'columns' => [
                ['key' => 'asset_tag', 'label' => 'Tag inventaris', 'type' => 'text'],
                ['key' => 'model', 'label' => 'Model', 'type' => 'text'],
                ['key' => 'category', 'label' => 'Kategori', 'type' => 'text'],
                ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
                ['key' => 'purchase_date', 'label' => 'Tanggal beli', 'type' => 'date'],
                ['key' => 'depreciation_months', 'label' => 'Umur (bulan)', 'type' => 'number'],
                ['key' => 'purchase_cost', 'label' => 'Harga beli', 'type' => 'currency'],
                ['key' => 'book_value', 'label' => 'Nilai buku', 'type' => 'currency'],
            ],
            'rows' => $rows,
            'totals' => [
                'asset_tag' => 'Total',
                'model' => null,
                'category' => null,
                'status' => null,
                'purchase_date' => null,
                'depreciation_months' => null,
                'purchase_cost' => round(array_sum(array_column($rows, 'purchase_cost')), 2),
                'book_value' => round(array_sum(array_column($rows, 'book_value')), 2),
            ],
        ];
    }

    /**
     * @return ReportData
     */
    private function licenseUtilization(?Carbon $from, ?Carbon $to): array
    {
        $rows = License::query()
            ->with('manufacturer:id,name')
            ->withCount(['seats as seats_used' => fn (Builder $query) => $query->whereNull('released_at')])
            ->when($from !== null, fn (Builder $query) => $query->whereDate('expires_at', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('expires_at', '<=', $to))
            ->orderBy('name')
            ->get()
            ->map(function (License $license): array {
                $used = (int) $license->getAttribute('seats_used');

                return [
                    'name' => $license->name,
                    'manufacturer' => $license->manufacturer?->name,
                    'license_type' => $license->license_type->label(),
                    'seats_total' => $license->seats_total,
                    'seats_used' => $used,
                    'utilization' => $license->seats_total > 0 ? round($used / $license->seats_total * 100, 1) : 0.0,
                    'annual_cost' => $license->annual_cost === null ? null : round((float) $license->annual_cost, 2),
                    'expires_at' => $license->expires_at?->toDateString(),
                ];
            })
            ->all();

        $seatsTotal = array_sum(array_column($rows, 'seats_total'));
        $seatsUsed = array_sum(array_column($rows, 'seats_used'));

        return [
            'columns' => [
                ['key' => 'name', 'label' => 'Lisensi', 'type' => 'text'],
                ['key' => 'manufacturer', 'label' => 'Manufaktur', 'type' => 'text'],
                ['key' => 'license_type', 'label' => 'Jenis', 'type' => 'text'],
                ['key' => 'seats_total', 'label' => 'Total kuota', 'type' => 'number'],
                ['key' => 'seats_used', 'label' => 'Kuota terpakai', 'type' => 'number'],
                ['key' => 'utilization', 'label' => 'Utilisasi (%)', 'type' => 'percent'],
                ['key' => 'annual_cost', 'label' => 'Biaya/tahun', 'type' => 'currency'],
                ['key' => 'expires_at', 'label' => 'Kedaluwarsa', 'type' => 'date'],
            ],
            'rows' => $rows,
            'totals' => [
                'name' => 'Total',
                'manufacturer' => null,
                'license_type' => null,
                'seats_total' => $seatsTotal,
                'seats_used' => $seatsUsed,
                'utilization' => $seatsTotal > 0 ? round($seatsUsed / $seatsTotal * 100, 1) : 0.0,
                'annual_cost' => round(array_sum(array_column($rows, 'annual_cost')), 2),
                'expires_at' => null,
            ],
        ];
    }

    /**
     * @return ReportData
     */
    private function maintenanceCost(?Carbon $from, ?Carbon $to): array
    {
        $rows = Maintenance::query()
            ->where('status', MaintenanceStatus::Done)
            ->with(['asset:id,asset_tag', 'supplier:id,name'])
            ->when($from !== null, fn (Builder $query) => $query->where('completed_at', '>=', $from->copy()->startOfDay()))
            ->when($to !== null, fn (Builder $query) => $query->where('completed_at', '<=', $to->copy()->endOfDay()))
            ->orderBy('completed_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Maintenance $maintenance): array => [
                'ticket_number' => $maintenance->ticket_number,
                'asset_tag' => $maintenance->asset?->asset_tag,
                'title' => $maintenance->title,
                'type' => $maintenance->type->label(),
                'handled_by' => $maintenance->handled_by->label(),
                'supplier' => $maintenance->supplier?->name,
                'completed_at' => $maintenance->completed_at?->toDateString(),
                'actual_cost' => round((float) ($maintenance->actual_cost ?? 0), 2),
            ])
            ->all();

        return [
            'columns' => [
                ['key' => 'ticket_number', 'label' => 'Tiket', 'type' => 'text'],
                ['key' => 'asset_tag', 'label' => 'Tag inventaris', 'type' => 'text'],
                ['key' => 'title', 'label' => 'Judul', 'type' => 'text'],
                ['key' => 'type', 'label' => 'Jenis', 'type' => 'text'],
                ['key' => 'handled_by', 'label' => 'Ditangani', 'type' => 'text'],
                ['key' => 'supplier', 'label' => 'Vendor', 'type' => 'text'],
                ['key' => 'completed_at', 'label' => 'Selesai', 'type' => 'date'],
                ['key' => 'actual_cost', 'label' => 'Biaya aktual', 'type' => 'currency'],
            ],
            'rows' => $rows,
            'totals' => [
                'ticket_number' => 'Total',
                'asset_tag' => null,
                'title' => null,
                'type' => null,
                'handled_by' => null,
                'supplier' => null,
                'completed_at' => null,
                'actual_cost' => round(array_sum(array_column($rows, 'actual_cost')), 2),
            ],
        ];
    }

    /**
     * @return ReportData
     */
    private function stock(): array
    {
        $row = fn (string $kind, Component|Accessory $item, int $used, int $available): array => [
            'kind' => $kind,
            'name' => $item->name,
            'category' => $item->category?->name,
            'location' => $item->location?->name,
            'qty_total' => $item->qty_total,
            'qty_used' => $used,
            'qty_available' => $available,
            'min_qty' => $item->min_qty,
            'low_stock' => $available <= $item->min_qty ? 'Ya' : 'Tidak',
            'stock_value' => round($item->qty_total * (float) ($item->unit_cost ?? 0), 2),
        ];

        $components = Component::query()
            ->with(['stock', 'category:id,name', 'location:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Component $component): array => $row(
                'Komponen',
                $component,
                (int) ($component->stock->qty_installed ?? 0),
                (int) ($component->stock->qty_available ?? $component->qty_total),
            ));

        $accessories = Accessory::query()
            ->with(['stock', 'category:id,name', 'location:id,name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Accessory $accessory): array => $row(
                'Aksesori',
                $accessory,
                (int) ($accessory->stock->qty_out ?? 0),
                (int) ($accessory->stock->qty_available ?? $accessory->qty_total),
            ));

        $rows = [...$components->all(), ...$accessories->all()];

        return [
            'columns' => [
                ['key' => 'kind', 'label' => 'Jenis', 'type' => 'text'],
                ['key' => 'name', 'label' => 'Nama', 'type' => 'text'],
                ['key' => 'category', 'label' => 'Kategori', 'type' => 'text'],
                ['key' => 'location', 'label' => 'Lokasi', 'type' => 'text'],
                ['key' => 'qty_total', 'label' => 'Total', 'type' => 'number'],
                ['key' => 'qty_used', 'label' => 'Terpakai', 'type' => 'number'],
                ['key' => 'qty_available', 'label' => 'Tersedia', 'type' => 'number'],
                ['key' => 'min_qty', 'label' => 'Minimum', 'type' => 'number'],
                ['key' => 'low_stock', 'label' => 'Stok menipis', 'type' => 'text'],
                ['key' => 'stock_value', 'label' => 'Nilai stok', 'type' => 'currency'],
            ],
            'rows' => $rows,
            'totals' => [
                'kind' => 'Total',
                'name' => null,
                'category' => null,
                'location' => null,
                'qty_total' => array_sum(array_column($rows, 'qty_total')),
                'qty_used' => array_sum(array_column($rows, 'qty_used')),
                'qty_available' => array_sum(array_column($rows, 'qty_available')),
                'min_qty' => null,
                'low_stock' => null,
                'stock_value' => round(array_sum(array_column($rows, 'stock_value')), 2),
            ],
        ];
    }

    /**
     * Assets purchased within the period; the period filter ignores assets without a purchase date.
     *
     * @return Builder<Asset>
     */
    private function assetQuery(?Carbon $from, ?Carbon $to): Builder
    {
        return Asset::query()
            ->when($from !== null, fn (Builder $query) => $query->whereDate('assets.purchase_date', '>=', $from))
            ->when($to !== null, fn (Builder $query) => $query->whereDate('assets.purchase_date', '<=', $to));
    }
}
