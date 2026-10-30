<?php

namespace App\Actions\Assets;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Enums\CategoryType;
use App\Enums\EmploymentStatus;
use App\Models\Accessory;
use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Manufacturer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Imports the field-device spreadsheet (NO FA, IMEI, USER, SN, NO, CABANG, ALAT, TYPE, MERK, AKSESORIS).
 * Each row becomes a deployed asset; rows whose NO FA already exists are skipped so re-runs are safe.
 */
class ImportAssetsFromCsv
{
    /**
     * Spreadsheet device codes mapped to readable category names.
     *
     * @var array<string, string>
     */
    private const CATEGORY_NAMES = [
        'TAB' => 'Tablet',
        'HP' => 'Handphone',
        'LAPTOP' => 'Laptop',
    ];

    public function __construct(private readonly RecordAssetLog $recordLog) {}

    /**
     * @return array{imported: int, skipped: int}
     */
    public function handle(string $path, ?User $actor = null): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException("File {$path} tidak bisa dibuka.");
        }

        $header = array_map(fn (string $column): string => Str::upper(trim($column)), fgetcsv($handle, escape: '') ?: []);
        $result = ['imported' => 0, 'skipped' => 0];

        while (($values = fgetcsv($handle, escape: '')) !== false) {
            $row = array_combine($header, array_pad(array_map('trim', $values), count($header), ''));

            // Trailing blank or junk rows in the spreadsheet have no fixed-asset number.
            if (($row['NO FA'] ?? '') === '' || Asset::withTrashed()->where('asset_tag', $row['NO FA'])->exists()) {
                $result['skipped']++;

                continue;
            }

            DB::transaction(fn () => $this->importRow($row, $actor));
            $result['imported']++;
        }

        fclose($handle);

        return $result;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function importRow(array $row, ?User $actor): void
    {
        $code = Str::upper($row['ALAT']);
        $manufacturer = Manufacturer::query()->firstOrCreate(['name' => Str::title($row['MERK'])]);
        $category = Category::query()->firstOrCreate(
            ['type' => CategoryType::Asset, 'name' => self::CATEGORY_NAMES[$code] ?? Str::title($code)],
            ['tag_prefix' => $code],
        );
        $assetModel = AssetModel::query()->firstOrCreate(
            ['name' => $row['TYPE'], 'manufacturer_id' => $manufacturer->id],
            ['category_id' => $category->id],
        );
        $location = Location::query()->firstOrCreate(['name' => $row['CABANG']]);
        $employee = $this->findOrCreateEmployee($row['USER'], $location);

        $asset = Asset::create([
            'asset_tag' => $row['NO FA'],
            'serial_number' => $row['SN'] ?: null,
            'imei' => $row['IMEI'] ?: null,
            'phone_number' => $this->normalizePhoneNumber($row['NO']),
            'asset_model_id' => $assetModel->id,
            'status' => AssetStatus::Deployed,
            'assigned_employee_id' => $employee->id,
            'assigned_at' => Carbon::now(),
            'location_id' => $location->id,
            'created_by' => $actor?->id,
        ]);

        $this->recordLog->handle($asset, AssetAction::Created, null, AssetStatus::Ready, $actor, [
            'note' => 'Diimpor dari spreadsheet',
        ]);
        $this->recordLog->handle($asset, AssetAction::Checkout, AssetStatus::Ready, AssetStatus::Deployed, $actor, [
            'employee_id' => $employee->id,
        ]);

        $this->checkoutAccessories($row['AKSESORIS'], $employee, $location, $actor);
    }

    /**
     * Employees not yet synced from HERD get a placeholder identity derived from their name,
     * so re-imports reuse the same record until the real HERD data replaces it.
     */
    private function findOrCreateEmployee(string $name, Location $location): Employee
    {
        $name = Str::title($name);
        $slug = Str::slug($name);

        return Employee::query()->firstOrCreate(
            ['herd_id' => Str::limit('import:'.$slug, 64, '')],
            [
                'employee_number' => 'TMP-'.Str::upper(substr(md5($slug), 0, 8)),
                'name' => $name,
                'location_id' => $location->id,
                'employment_status' => EmploymentStatus::Active,
            ],
        );
    }

    /**
     * The spreadsheet stored numbers as integers, dropping the leading zero (81361345334 → 081361345334).
     */
    private function normalizePhoneNumber(string $number): ?string
    {
        $digits = preg_replace('/\D/', '', $number);

        if ($digits === '') {
            return null;
        }

        return str_starts_with($digits, '8') ? '0'.$digits : $digits;
    }

    /**
     * Accessories ship with the device, so each one adds a unit to stock and hands it straight out.
     */
    private function checkoutAccessories(string $accessories, Employee $employee, Location $location, ?User $actor): void
    {
        $category = Category::query()->firstOrCreate(['type' => CategoryType::Accessory, 'name' => 'Aksesori Perangkat']);

        foreach (array_filter(array_map('trim', explode('+', $accessories))) as $name) {
            $accessory = Accessory::query()->firstOrCreate(
                ['name' => Str::title($name)],
                ['category_id' => $category->id, 'location_id' => $location->id],
            );
            $accessory->increment('qty_total');

            $accessory->checkouts()->create([
                'employee_id' => $employee->id,
                'qty' => 1,
                'checked_out_at' => Carbon::now(),
                'checked_out_by' => $actor?->id,
                'note' => 'Diimpor bersama inventaris',
            ]);
        }
    }
}
