<?php

namespace Database\Seeders;

use App\Enums\AssetAction;
use App\Enums\AssetStatus;
use App\Enums\CategoryType;
use App\Enums\EmploymentStatus;
use App\Enums\HandledBy;
use App\Enums\LicenseType;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Enums\RetireReason;
use App\Enums\Role;
use App\Jobs\GenerateAlertsJob;
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\AssetModel;
use App\Models\Category;
use App\Models\Component;
use App\Models\ComponentInstallation;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HerdSyncRun;
use App\Models\License;
use App\Models\LicenseSeat;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\MaintenancePlan;
use App\Models\Manufacturer;
use App\Models\Supplier;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Deterministic demo data: 3 locations, 6 departments, 40 employees, 3 users,
 * 25 asset models, 60 assets, 8 licenses, 5 components, 5 accessories,
 * 6 maintenance tickets and alerts derived from that data.
 */
class DemoDataSeeder extends Seeder
{
    private User $admin;

    private User $technician;

    private int $bastCounter = 0;

    public function run(): void
    {
        fake()->seed(2026);

        DB::transaction(function (): void {
            $this->seedOrganization();
            $this->seedMasterData();
            $this->seedAssets();
            $this->seedLicenses();
            $this->seedInventory();
            $this->seedMaintenances();
            $this->seedAlerts();
        });
    }

    private function seedOrganization(): void
    {
        $locations = collect([
            ['LOC-JKT', 'Kantor Pusat Jakarta', 'Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan'],
            ['LOC-CKR', 'Plant Cikarang', 'Kawasan Industri Jababeka, Cikarang Barat'],
            ['LOC-SBY', 'Cabang Surabaya', 'Jl. Mayjen Sungkono No. 89, Surabaya'],
        ])->mapWithKeys(fn (array $location): array => [
            $location[1] => Location::create(['herd_id' => $location[0], 'name' => $location[1], 'address' => $location[2]]),
        ]);

        $departments = collect([
            'Information Technology',
            'Finance & Accounting',
            'Human Resources',
            'Produksi',
            'Logistik',
            'Marketing',
        ])->mapWithKeys(fn (string $name, int $index): array => [
            $name => Department::create([
                'herd_id' => 'DEPT-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'name' => $name,
                'synced_at' => now(),
            ]),
        ]);

        // Orang pertama pada tiap departemen adalah atasan anggota departemen tersebut.
        $people = [
            'Information Technology' => [
                ['Budi Santoso', 'IT Manager', 'Kantor Pusat Jakarta'],
                ['Rina Kartika', 'IT Support Specialist', 'Kantor Pusat Jakarta'],
                ['Agus Prasetyo', 'Network Administrator', 'Kantor Pusat Jakarta'],
                ['Taufik Hidayat', 'IT Support', 'Cabang Surabaya'],
                ['Reza Fahlevi', 'Data Analyst', 'Kantor Pusat Jakarta'],
                ['Firman Ramadhan', 'Network Engineer', 'Kantor Pusat Jakarta'],
                ['Dimas Aditya', 'Software Engineer', 'Kantor Pusat Jakarta'],
                ['Nadia Safitri', 'UX Researcher', 'Kantor Pusat Jakarta'],
            ],
            'Finance & Accounting' => [
                ['Dewi Lestari', 'Finance Manager', 'Kantor Pusat Jakarta'],
                ['Hendra Wijaya', 'Accounting Staff', 'Kantor Pusat Jakarta'],
                ['Wulan Sari', 'Finance Staff', 'Kantor Pusat Jakarta'],
                ['Ika Puspitasari', 'Payroll Staff', 'Kantor Pusat Jakarta'],
                ['Lina Marlina', 'Admin Cabang', 'Cabang Surabaya'],
                ['Rahmat Hidayat', 'Tax Officer', 'Kantor Pusat Jakarta'],
            ],
            'Human Resources' => [
                ['Siti Rahmawati', 'HR Manager', 'Kantor Pusat Jakarta'],
                ['Andi Saputra', 'Recruitment Officer', 'Kantor Pusat Jakarta'],
                ['Ayu Permata', 'HR Staff', 'Plant Cikarang'],
                ['Citra Dewi', 'Legal Officer', 'Kantor Pusat Jakarta'],
                ['Rani Oktaviani', 'Secretary', 'Kantor Pusat Jakarta'],
                ['Novi Andriani', 'Training Officer', 'Kantor Pusat Jakarta'],
            ],
            'Produksi' => [
                ['Joko Susilo', 'Production Supervisor', 'Plant Cikarang'],
                ['Sri Wahyuni', 'Quality Control', 'Plant Cikarang'],
                ['Bambang Sudarso', 'Operator Produksi', 'Plant Cikarang'],
                ['Eko Purnomo', 'Maintenance Technician', 'Plant Cikarang'],
                ['Hadi Susanto', 'Security', 'Plant Cikarang'],
                ['Indra Gunawan', 'Mechanical Engineer', 'Plant Cikarang'],
                ['Putri Ayu', 'Electrical Engineer', 'Plant Cikarang'],
                ['Wahyu Setiadi', 'Process Engineer', 'Plant Cikarang'],
                ['Dian Puspita', 'PPIC Staff', 'Plant Cikarang'],
            ],
            'Logistik' => [
                ['Nur Aisyah', 'Warehouse Supervisor', 'Plant Cikarang'],
                ['Rudi Hartono', 'Logistics Staff', 'Plant Cikarang'],
                ['Arif Setiawan', 'Procurement Staff', 'Kantor Pusat Jakarta'],
                ['Bayu Kurniawan', 'Driver', 'Plant Cikarang'],
                ['Sari Indah', 'Purchasing Staff', 'Kantor Pusat Jakarta'],
            ],
            'Marketing' => [
                ['Maya Anggraini', 'Marketing Manager', 'Kantor Pusat Jakarta'],
                ['Fajar Nugroho', 'Content Specialist', 'Cabang Surabaya'],
                ['Yusuf Maulana', 'Sales Executive', 'Cabang Surabaya'],
                ['Mega Puspa', 'Customer Support', 'Cabang Surabaya'],
                ['Gita Nirmala', 'Product Manager', 'Kantor Pusat Jakarta'],
                ['Ahmad Fauzi', 'Field Sales', 'Cabang Surabaya'],
            ],
        ];

        $sequence = 0;
        foreach ($people as $department => $members) {
            $head = null;

            foreach ($members as [$name, $title, $location]) {
                $sequence++;

                $employee = Employee::create([
                    'herd_id' => 'HERD-'.str_pad((string) (1000 + $sequence), 6, '0', STR_PAD_LEFT),
                    'employee_number' => 'NIK'.(24000 + $sequence),
                    'name' => $name,
                    'email' => strtolower(str_replace(' ', '.', $name)).'@perusahaan.co.id',
                    'job_title' => $title,
                    'department_id' => $departments[$department]->id,
                    'location_id' => $locations[$location]->id,
                    'manager_id' => $head?->id,
                    'employment_status' => EmploymentStatus::Active,
                    'synced_at' => now(),
                ]);

                $head ??= $employee;
            }
        }

        // Dua karyawan resign dan satu mutasi; ketiganya masih memegang barang perusahaan.
        $offboarding = [
            'Bambang Sudarso' => [EmploymentStatus::Resigned, 10],
            'Bayu Kurniawan' => [EmploymentStatus::Resigned, 3],
            'Fajar Nugroho' => [EmploymentStatus::Transferred, 5],
        ];

        foreach ($offboarding as $name => [$status, $daysAgo]) {
            Employee::where('name', $name)->firstOrFail()->update([
                'employment_status' => $status,
                'status_effective_date' => now()->subDays($daysAgo)->toDateString(),
            ]);
        }

        $this->admin = User::create([
            'employee_id' => Employee::where('name', 'Budi Santoso')->value('id'),
            'role' => Role::Admin,
            'last_login_at' => now()->subHours(2),
        ]);
        $this->technician = User::create([
            'employee_id' => Employee::where('name', 'Rina Kartika')->value('id'),
            'role' => Role::Technician,
            'last_login_at' => now()->subHours(5),
        ]);
        User::create([
            'employee_id' => Employee::where('name', 'Dewi Lestari')->value('id'),
            'role' => Role::Viewer,
            'last_login_at' => now()->subDay(),
        ]);

        HerdSyncRun::create([
            'started_at' => now()->subDay()->setTime(2, 0),
            'finished_at' => now()->subDay()->setTime(2, 1, 12),
            'status' => 'success',
            'created_count' => 0,
            'updated_count' => 40,
            'offboarding_count' => 1,
        ]);
        HerdSyncRun::create([
            'started_at' => now()->subMinutes(4),
            'finished_at' => now()->subMinutes(3),
            'status' => 'success',
            'created_count' => 0,
            'updated_count' => 40,
            'offboarding_count' => 3,
            'triggered_by' => $this->admin->id,
        ]);
    }

    private function seedMasterData(): void
    {
        $manufacturers = collect([
            'Acer', 'Adobe', 'Apple', 'Asus', 'Autodesk', 'Canon', 'Cisco', 'Dell', 'Epson', 'HP',
            'Kaspersky', 'Kingston', 'Lenovo', 'Logitech', 'MathWorks', 'Microsoft', 'Samsung',
            'Seasonic', 'TP-Link', 'Ubiquiti', 'Zoom',
        ])->mapWithKeys(fn (string $name): array => [$name => Manufacturer::create(['name' => $name])]);

        $assetCategories = collect([
            ['Laptop', 'LT'],
            ['Desktop', 'DT'],
            ['Monitor', 'MN'],
            ['Printer', 'PR'],
            ['Perangkat Jaringan', 'NW'],
            ['Telepon', 'PH'],
        ])->mapWithKeys(fn (array $category): array => [
            $category[0] => Category::create([
                'name' => $category[0],
                'type' => CategoryType::Asset,
                'tag_prefix' => $category[1],
            ]),
        ]);

        foreach (['RAM', 'Penyimpanan', 'Baterai', 'Power Supply'] as $name) {
            Category::create(['name' => $name, 'type' => CategoryType::Component]);
        }

        foreach (['Mouse', 'Keyboard', 'Headset', 'Docking Station', 'Webcam'] as $name) {
            Category::create(['name' => $name, 'type' => CategoryType::Accessory]);
        }

        Category::create(['name' => 'Software', 'type' => CategoryType::License]);

        $models = [
            ['Dell Latitude 5440', 'L5440', 'Dell', 'Laptop', 48],
            ['Dell Latitude 7440', 'L7440', 'Dell', 'Laptop', 48],
            ['Lenovo ThinkPad T14 Gen 4', 'T14G4', 'Lenovo', 'Laptop', 48],
            ['Lenovo ThinkPad X1 Carbon Gen 11', 'X1C11', 'Lenovo', 'Laptop', 48],
            ['HP EliteBook 840 G10', 'E840G10', 'HP', 'Laptop', 48],
            ['HP ProBook 450 G10', 'P450G10', 'HP', 'Laptop', 48],
            ['Apple MacBook Pro 14 M3', 'MBP14M3', 'Apple', 'Laptop', 48],
            ['Apple MacBook Air 13 M2', 'MBA13M2', 'Apple', 'Laptop', 48],
            ['Asus ExpertBook B9', 'B9403', 'Asus', 'Laptop', 48],
            ['Dell OptiPlex 7010', 'O7010', 'Dell', 'Desktop', 60],
            ['Lenovo ThinkCentre M70q', 'M70Q', 'Lenovo', 'Desktop', 60],
            ['HP ProDesk 400 G9', 'P400G9', 'HP', 'Desktop', 60],
            ['Acer Veriton X4690G', 'X4690G', 'Acer', 'Desktop', 60],
            ['Dell UltraSharp U2723QE', 'U2723QE', 'Dell', 'Monitor', 60],
            ['Dell P2422H', 'P2422H', 'Dell', 'Monitor', 60],
            ['Samsung Odyssey G5 27"', 'G5-27', 'Samsung', 'Monitor', 60],
            ['Lenovo ThinkVision T24i-30', 'T24I30', 'Lenovo', 'Monitor', 60],
            ['Epson EcoTank L3250', 'L3250', 'Epson', 'Printer', 60],
            ['Canon imageRUNNER 2630i', 'IR2630I', 'Canon', 'Printer', 60],
            ['HP LaserJet Pro M404dn', 'M404DN', 'HP', 'Printer', 60],
            ['Cisco Catalyst 1000-24T', 'C1000-24T', 'Cisco', 'Perangkat Jaringan', 60],
            ['TP-Link Omada EAP225', 'EAP225', 'TP-Link', 'Perangkat Jaringan', 48],
            ['Ubiquiti UniFi U6 Pro', 'U6-PRO', 'Ubiquiti', 'Perangkat Jaringan', 48],
            ['Apple iPhone 14', 'IP14', 'Apple', 'Telepon', 24],
            ['Samsung Galaxy A54', 'A54', 'Samsung', 'Telepon', 24],
        ];

        foreach ($models as [$name, $number, $manufacturer, $category, $depreciation]) {
            AssetModel::create([
                'name' => $name,
                'model_number' => $number,
                'manufacturer_id' => $manufacturers[$manufacturer]->id,
                'category_id' => $assetCategories[$category]->id,
                'depreciation_months' => $depreciation,
            ]);
        }

        $suppliers = [
            ['PT Sinar Komputindo', 'Andi Kusuma', '021-5550101', 'sales@sinarkom.co.id', false],
            ['PT Global Teknologi', 'Rina Wijaya', '021-5550202', 'procurement@globaltek.co.id', false],
            ['CV Mitra Service', 'Dedi Kurnia', '021-5550303', 'service@mitraservice.co.id', true],
            ['PT Solusi Jaringan', 'Fajar Hidayat', '021-5550404', 'info@solusijaringan.co.id', true],
            ['PT Digital Lisensi', 'Maya Sari', '021-5550505', 'sales@digitallisensi.co.id', false],
            ['PT Prima Printer', 'Yudi Pratama', '021-5550606', 'support@primaprinter.co.id', true],
        ];

        foreach ($suppliers as [$name, $contact, $phone, $email, $isService]) {
            Supplier::create([
                'name' => $name,
                'contact_name' => $contact,
                'phone' => $phone,
                'email' => $email,
                'is_service_vendor' => $isService,
            ]);
        }
    }

    private function seedAssets(): void
    {
        $models = AssetModel::with('category')->orderBy('id')->get();
        $endUserModels = $models->filter(fn (AssetModel $model): bool => in_array($model->category->tag_prefix, ['LT', 'DT', 'MN', 'PH'], true))->values();
        $infrastructureModels = $models->filter(fn (AssetModel $model): bool => in_array($model->category->tag_prefix, ['PR', 'NW'], true))->values();
        $locations = Location::orderBy('id')->get();
        $suppliers = Supplier::where('is_service_vendor', false)->whereNot('name', 'PT Digital Lisensi')->get();

        // Tiga aset pertama yang digunakan jatuh ke karyawan yang sedang offboarding.
        $holders = Employee::needsOffboarding()->orderBy('id')->get()
            ->concat(Employee::active()->orderBy('id')->get())
            ->values();
        $holderCursor = 0;

        $plan = [];
        foreach (range(1, 5) as $i) {
            $plan[] = ['status' => AssetStatus::Pending, 'models' => $models];
        }
        foreach (range(1, 17) as $i) {
            $plan[] = ['status' => AssetStatus::Ready, 'models' => $models, 'returned' => $i % 6 === 0];
        }
        foreach (range(1, 25) as $i) {
            $plan[] = [
                'status' => AssetStatus::Deployed,
                'models' => $endUserModels,
                'holder' => 'employee',
                'warranty_days' => [6 => 9, 12 => 17, 20 => 26][$i] ?? null,
                'expected_return_days' => [9 => -4, 15 => 10][$i] ?? null,
            ];
        }
        foreach (range(1, 5) as $i) {
            $plan[] = ['status' => AssetStatus::Deployed, 'models' => $infrastructureModels, 'holder' => 'location'];
        }
        foreach (range(1, 4) as $i) {
            $plan[] = ['status' => AssetStatus::Maintenance, 'models' => $endUserModels, 'holder' => 'employee'];
        }
        foreach ([RetireReason::EndOfLife, RetireReason::EndOfLife, RetireReason::Broken, RetireReason::Lost] as $reason) {
            $plan[] = ['status' => AssetStatus::Retired, 'models' => $models, 'retire_reason' => $reason];
        }

        $tagCounters = [];

        foreach ($plan as $spec) {
            $status = $spec['status'];
            /** @var AssetModel $model */
            $model = fake()->randomElement($spec['models']->all());
            $prefix = $model->category->tag_prefix ?? 'AS';
            $tagCounters[$status === AssetStatus::Pending ? 'TMP' : $prefix] = ($tagCounters[$status === AssetStatus::Pending ? 'TMP' : $prefix] ?? 0) + 1;

            $assetTag = $status === AssetStatus::Pending
                ? 'TMP-'.str_pad((string) $tagCounters['TMP'], 4, '0', STR_PAD_LEFT)
                : $prefix.'-'.str_pad((string) $tagCounters[$prefix], 5, '0', STR_PAD_LEFT);

            $purchaseDate = (match ($status) {
                AssetStatus::Pending => now()->subDays(fake()->numberBetween(5, 20)),
                AssetStatus::Retired => now()->subMonths(fake()->numberBetween(42, 60)),
                default => now()->subMonths(fake()->numberBetween(3, 34)),
            })->startOfDay();

            $receivedAt = $purchaseDate->copy()->addDays(3)->setTime(9, 0);
            $taggedAt = $receivedAt->copy()->addDay()->setTime(14, 0);
            $assignedAt = $taggedAt->copy()->addDays(fake()->numberBetween(1, 14))->setTime(10, 30);

            $employee = null;
            $location = null;
            if (($spec['holder'] ?? null) === 'employee') {
                $employee = $holders[$holderCursor % $holders->count()];
                $holderCursor++;
            } elseif (($spec['holder'] ?? null) === 'location') {
                $location = fake()->randomElement($locations->all());
            }

            $warrantyUntil = isset($spec['warranty_days'])
                ? now()->addDays($spec['warranty_days'])
                : $purchaseDate->copy()->addMonths($model->category->tag_prefix === 'PH' ? 12 : 36);

            $asset = new Asset([
                'asset_tag' => $assetTag,
                'serial_number' => strtoupper(fake()->unique()->bothify('??########')),
                // Same shape as the real spreadsheet: every device carries an IMEI and a SIM number.
                'imei' => fake()->unique()->imei(),
                'phone_number' => '08'.fake()->unique()->numerify('##########'),
                'asset_model_id' => $model->id,
                'status' => $status,
                'assigned_employee_id' => $employee?->id,
                'assigned_location_id' => $location?->id,
                'assigned_at' => $employee !== null || $location !== null ? $assignedAt : null,
                'expected_return_date' => isset($spec['expected_return_days']) ? now()->addDays($spec['expected_return_days'])->toDateString() : null,
                'location_id' => $location->id ?? $employee->location_id ?? $locations->first()?->id,
                'supplier_id' => fake()->randomElement($suppliers->all())->id,
                'purchase_order' => 'PO-'.$purchaseDate->year.'-'.str_pad((string) fake()->numberBetween(1, 999), 4, '0', STR_PAD_LEFT),
                'purchase_date' => $purchaseDate->toDateString(),
                'purchase_cost' => fake()->numberBetween(55, 320) * 100_000,
                'warranty_until' => $warrantyUntil->toDateString(),
                // Pending assets have not been through a physical audit yet.
                'last_audited_at' => $status !== AssetStatus::Pending ? now()->subMonths(fake()->numberBetween(1, 8))->toDateString() : null,
                'notes' => fake()->randomElement([
                    'Kelengkapan: unit, charger, dan dus.',
                    'Kondisi fisik baik, tanpa goresan.',
                    'Diterima lengkap dengan BAST.',
                    'Sudah terpasang aplikasi standar kantor.',
                    'Garansi resmi distributor.',
                ]),
                'retire_reason' => $spec['retire_reason'] ?? null,
                'created_by' => $this->admin->id,
            ]);
            $asset->created_at = $receivedAt;
            $asset->updated_at = $receivedAt;
            $asset->save();

            $this->log($asset, AssetAction::Created, $receivedAt, [
                'to_status' => AssetStatus::Pending,
                'note' => 'Diterima dari pengadaan '.$asset->purchase_order,
            ]);

            if ($status === AssetStatus::Pending) {
                continue;
            }

            $this->log($asset, AssetAction::Tagged, $taggedAt, [
                'from_status' => AssetStatus::Pending,
                'to_status' => AssetStatus::Ready,
                'note' => 'Label QR '.$asset->asset_tag.' dicetak dan ditempel',
            ]);

            if (($spec['returned'] ?? false) === true) {
                /** @var Employee $borrower */
                $borrower = fake()->randomElement($holders->filter(fn (Employee $holder): bool => $holder->isActive())->all());
                $this->logCheckout($asset, $borrower, null, $assignedAt);
                $this->log($asset, AssetAction::Checkin, $assignedAt->copy()->addDays(fake()->numberBetween(14, 45)), [
                    'from_status' => AssetStatus::Deployed,
                    'to_status' => AssetStatus::Ready,
                    'employee_id' => $borrower->id,
                    'note' => 'Dikembalikan dalam kondisi baik',
                    'meta' => ['condition' => 'baik'],
                ]);
            }

            if ($employee !== null || $location !== null) {
                $this->logCheckout($asset, $employee, $location, $assignedAt);
            }

            if ($status === AssetStatus::Maintenance) {
                $this->log($asset, AssetAction::MaintenanceStart, now()->subDays(fake()->numberBetween(2, 8))->setTime(13, 0), [
                    'from_status' => AssetStatus::Deployed,
                    'to_status' => AssetStatus::Maintenance,
                    'employee_id' => $employee?->id,
                    'performed_by' => $this->technician->id,
                    'note' => 'Dilaporkan pengguna, unit ditarik ke meja IT',
                ]);
            }

            if ($status === AssetStatus::Retired) {
                $retiredAt = $purchaseDate->copy()->addMonths(fake()->numberBetween(36, 40))->setTime(15, 0);
                $asset->forceFill(['retired_at' => $retiredAt])->save();

                $this->log($asset, AssetAction::Retired, $retiredAt, [
                    'from_status' => AssetStatus::Ready,
                    'to_status' => AssetStatus::Retired,
                    'note' => 'Dipensiunkan: '.$asset->retire_reason?->label(),
                    'meta' => ['reason' => $asset->retire_reason?->value],
                ]);
            }
        }
    }

    private function seedLicenses(): void
    {
        $software = Category::where('type', CategoryType::License)->firstOrFail();
        $supplier = Supplier::where('name', 'PT Digital Lisensi')->firstOrFail();
        $employees = Employee::active()->orderBy('id')->get();
        $computers = Asset::whereIn('status', [AssetStatus::Ready, AssetStatus::Deployed, AssetStatus::Maintenance])
            ->whereHas('assetModel.category', fn (Builder $query) => $query->whereIn('tag_prefix', ['LT', 'DT']))
            ->orderBy('id')
            ->get();

        // [nama, manufacturer, tipe, total seat, seat terpakai, biaya tahunan, kedaluwarsa (hari), target seat]
        $licenses = [
            ['Microsoft 365 Business Premium', 'Microsoft', LicenseType::Subscription, 30, 30, 1_450_000, 21, 'employee'],
            ['Adobe Creative Cloud All Apps', 'Adobe', LicenseType::Subscription, 10, 6, 8_900_000, 75, 'employee'],
            ['AutoCAD 2026', 'Autodesk', LicenseType::Subscription, 8, 5, 24_000_000, 200, 'employee'],
            ['Zoom Workplace Business', 'Zoom', LicenseType::Subscription, 15, 9, 3_100_000, 120, 'employee'],
            ['Microsoft Project Professional 2021', 'Microsoft', LicenseType::Perpetual, 5, 3, null, null, 'employee'],
            ['MATLAB Concurrent', 'MathWorks', LicenseType::Concurrent, 5, 2, 42_000_000, 300, 'employee'],
            ['Windows 11 Pro OEM', 'Microsoft', LicenseType::Oem, 40, 30, null, null, 'asset'],
            ['Kaspersky Endpoint Security', 'Kaspersky', LicenseType::Subscription, 60, 60, 320_000, 12, 'asset'],
        ];

        foreach ($licenses as $index => [$name, $manufacturer, $type, $seats, $used, $cost, $expiresInDays, $target]) {
            $license = License::create([
                'name' => $name,
                'manufacturer_id' => Manufacturer::where('name', $manufacturer)->value('id'),
                'category_id' => $software->id,
                'license_type' => $type,
                'product_key' => strtoupper(fake()->bothify('?????-#####-?????-#####-?????')),
                'seats_total' => $seats,
                'supplier_id' => $supplier->id,
                'purchase_order' => 'PO-2026-'.str_pad((string) (300 + $index), 4, '0', STR_PAD_LEFT),
                'purchase_date' => now()->subMonths(8)->toDateString(),
                'annual_cost' => $cost,
                'expires_at' => $expiresInDays === null ? null : now()->addDays($expiresInDays)->toDateString(),
                'notes' => null,
            ]);

            $holders = $target === 'employee' ? $employees : $computers;

            foreach ($holders->take(min($used, $seats)) as $holder) {
                LicenseSeat::create([
                    'license_id' => $license->id,
                    'employee_id' => $holder instanceof Employee ? $holder->id : null,
                    'asset_id' => $holder instanceof Asset ? $holder->id : null,
                    'assigned_at' => now()->subMonths(fake()->numberBetween(1, 6)),
                    'assigned_by' => $this->admin->id,
                ]);
            }
        }

        // Contoh seat yang sudah dilepas: tidak dihitung sebagai seat terpakai.
        LicenseSeat::create([
            'license_id' => License::where('name', 'Adobe Creative Cloud All Apps')->value('id'),
            'employee_id' => $employees->last()?->id,
            'assigned_at' => now()->subMonths(6),
            'assigned_by' => $this->admin->id,
            'released_at' => now()->subMonths(2),
            'released_by' => $this->admin->id,
        ]);
    }

    private function seedInventory(): void
    {
        $storage = Location::where('name', 'Kantor Pusat Jakarta')->firstOrFail();
        $laptops = Asset::whereIn('status', [AssetStatus::Deployed, AssetStatus::Maintenance])
            ->whereHas('assetModel.category', fn (Builder $query) => $query->where('tag_prefix', 'LT'))
            ->orderBy('id')
            ->get();

        // [nama, spesifikasi, kategori, manufacturer, qty total, qty minimum, harga satuan, pemasangan [qty, bawaan pabrik, dilepas]]
        $components = [
            ['RAM DDR4 8 GB', 'SODIMM 3200 MHz', 'RAM', 'Kingston', 40, 8, 450_000, [[2, false, false], [1, false, false], [1, false, true]]],
            ['SSD NVMe 512 GB', 'PCIe Gen4 M.2 2280', 'Penyimpanan', 'Samsung', 25, 5, 850_000, [[1, true, false], [1, false, false]]],
            ['Baterai Laptop 42Wh', '3-cell Li-ion', 'Baterai', 'Dell', 6, 3, 720_000, [[1, false, false], [1, false, false]]],
            ['RAM DDR5 16 GB', 'SODIMM 4800 MHz', 'RAM', 'Kingston', 16, 4, 780_000, [[1, false, false]]],
            ['Power Supply 500W', '80+ Bronze ATX', 'Power Supply', 'Seasonic', 8, 2, 650_000, []],
        ];

        $laptopCursor = 0;

        foreach ($components as [$name, $spec, $category, $manufacturer, $qty, $min, $cost, $installations]) {
            $component = Component::create([
                'name' => $name,
                'spec' => $spec,
                'category_id' => Category::where('type', CategoryType::Component)->where('name', $category)->value('id'),
                'manufacturer_id' => Manufacturer::where('name', $manufacturer)->value('id'),
                'qty_total' => $qty,
                'min_qty' => $min,
                'unit_cost' => $cost,
                'location_id' => $storage->id,
            ]);

            foreach ($installations as [$installQty, $isFactory, $isRemoved]) {
                /** @var Asset $asset */
                $asset = $laptops[$laptopCursor % $laptops->count()];
                $laptopCursor++;

                $installedAt = $isFactory
                    ? ($asset->created_at ?? now())
                    : now()->subDays(fake()->numberBetween(15, 35))->setTime(11, 0);

                $installation = ComponentInstallation::create([
                    'component_id' => $component->id,
                    'asset_id' => $asset->id,
                    'qty' => $installQty,
                    'is_factory' => $isFactory,
                    'installed_at' => $installedAt,
                    'installed_by' => $isFactory ? null : $this->technician->id,
                ]);

                if ($isFactory) {
                    continue;
                }

                $this->log($asset, AssetAction::ComponentInstalled, $installedAt, [
                    'performed_by' => $this->technician->id,
                    'note' => "{$installQty}x {$component->name} dipasang",
                    'meta' => ['component_id' => $component->id, 'qty' => $installQty],
                ]);

                if ($isRemoved) {
                    $removedAt = $installedAt->copy()->addDays(7);
                    $installation->update(['removed_at' => $removedAt, 'removed_by' => $this->technician->id]);

                    $this->log($asset, AssetAction::ComponentRemoved, $removedAt, [
                        'performed_by' => $this->technician->id,
                        'note' => "{$installQty}x {$component->name} dilepas, dikembalikan ke stok",
                        'meta' => ['component_id' => $component->id, 'qty' => $installQty],
                    ]);
                }
            }
        }

        $employees = Employee::orderBy('id')->get();

        // [nama, model, kategori, manufacturer, qty total, qty minimum, harga satuan, dipinjam aktif, sudah kembali]
        $accessories = [
            ['Mouse Wireless Logitech M331', 'M331', 'Mouse', 'Logitech', 60, 10, 185_000, 12, 2],
            ['Keyboard Logitech K380', 'K380', 'Keyboard', 'Logitech', 20, 5, 550_000, 6, 1],
            ['Headset Logitech H390', 'H390', 'Headset', 'Logitech', 35, 8, 450_000, 9, 2],
            ['Docking Station Dell WD19S', 'WD19S', 'Docking Station', 'Dell', 15, 4, 2_400_000, 11, 1],
            ['Webcam Logitech C920', 'C920', 'Webcam', 'Logitech', 12, 3, 1_050_000, 4, 0],
        ];

        foreach ($accessories as $index => [$name, $model, $category, $manufacturer, $qty, $min, $cost, $active, $returned]) {
            $accessory = Accessory::create([
                'name' => $name,
                'model' => $model,
                'category_id' => Category::where('type', CategoryType::Accessory)->where('name', $category)->value('id'),
                'manufacturer_id' => Manufacturer::where('name', $manufacturer)->value('id'),
                'qty_total' => $qty,
                'min_qty' => $min,
                'unit_cost' => $cost,
                'location_id' => $storage->id,
            ]);

            for ($i = 0; $i < $active + $returned; $i++) {
                $checkedOutAt = now()->subDays(fake()->numberBetween(20, 200))->setTime(9, 15);
                $isReturned = $i >= $active;

                AccessoryCheckout::create([
                    'accessory_id' => $accessory->id,
                    'employee_id' => $employees[($index * 7 + $i * 3) % $employees->count()]->id,
                    'qty' => 1,
                    'checked_out_at' => $checkedOutAt,
                    'checked_out_by' => $this->technician->id,
                    'checked_in_at' => $isReturned ? $checkedOutAt->copy()->addDays(14) : null,
                    'checked_in_by' => $isReturned ? $this->technician->id : null,
                ]);
            }
        }
    }

    private function seedMaintenances(): void
    {
        $vendors = Supplier::where('is_service_vendor', true)->get()->keyBy('name');

        $plans = [
            ['Servis rutin laptop (6 bulan)', 'Laptop', null, 6, 18, 'CV Mitra Service'],
            ['Kalibrasi printer', 'Printer', null, 12, 5, 'PT Prima Printer'],
            ['Pembersihan perangkat jaringan kantor pusat', 'Perangkat Jaringan', 'Kantor Pusat Jakarta', 3, 30, 'PT Solusi Jaringan'],
            ['Pengecekan PSU & kebersihan desktop', 'Desktop', null, 12, 45, null],
        ];

        foreach ($plans as [$name, $category, $location, $interval, $dueInDays, $vendor]) {
            MaintenancePlan::create([
                'name' => $name,
                'category_id' => Category::where('type', CategoryType::Asset)->where('name', $category)->value('id'),
                'location_id' => $location === null ? null : Location::where('name', $location)->value('id'),
                'interval_months' => $interval,
                'next_due_date' => now()->addDays($dueInDays)->toDateString(),
                'pic' => 'Tim IT Support',
                'supplier_id' => $vendor === null ? null : $vendors[$vendor]->id,
                'is_active' => true,
            ]);
        }

        $ticketNumber = 0;
        $nextTicket = function () use (&$ticketNumber): string {
            $ticketNumber++;

            return 'MT-'.now()->year.'-'.str_pad((string) $ticketNumber, 4, '0', STR_PAD_LEFT);
        };

        // Dua tiket selesai pada laptop yang sudah lama digunakan; status aset kembali ke deployed.
        $longDeployed = Asset::where('status', AssetStatus::Deployed)
            ->whereHas('assetModel.category', fn (Builder $query) => $query->where('tag_prefix', 'LT'))
            ->where('assigned_at', '<', now()->subDays(60))
            ->orderByDesc('id')
            ->take(2)
            ->get();

        $completed = [
            [MaintenanceType::Preventive, 'Servis preventif berkala', HandledBy::Vendor, 'CV Mitra Service', 40, 1, 350_000, 'Servis rutin laptop (6 bulan)', null],
            [MaintenanceType::Repair, 'Baterai cepat habis, ganti baterai', HandledBy::Internal, null, 20, 2, 720_000, null, 'Baterai Laptop 42Wh'],
        ];

        foreach ($longDeployed as $index => $asset) {
            [$type, $title, $handledBy, $vendor, $startedDaysAgo, $duration, $actualCost, $planName, $replacedComponent] = $completed[$index];
            $startedAt = now()->subDays($startedDaysAgo)->setTime(9, 0);
            $completedAt = $startedAt->copy()->addDays($duration)->setTime(16, 0);

            $maintenance = $this->createMaintenance([
                'ticket_number' => $nextTicket(),
                'asset_id' => $asset->id,
                'plan_id' => $planName === null ? null : MaintenancePlan::where('name', $planName)->value('id'),
                'type' => $type,
                'status' => MaintenanceStatus::Done,
                'priority' => MaintenancePriority::Medium,
                'title' => $title.' '.$asset->asset_tag,
                'description' => $type === MaintenanceType::Preventive
                    ? 'Pembersihan kipas, penggantian thermal paste, dan pengecekan kesehatan baterai.'
                    : 'Pengguna melaporkan baterai hanya bertahan kurang dari satu jam.',
                'handled_by' => $handledBy,
                'supplier_id' => $vendor === null ? null : $vendors[$vendor]->id,
                'reported_by_employee_id' => $asset->assigned_employee_id,
                'estimated_cost' => $actualCost,
                'actual_cost' => $actualCost,
                'start_date' => $startedAt->toDateString(),
                'target_date' => $completedAt->toDateString(),
                'completed_at' => $completedAt,
                'asset_status_before' => AssetStatus::Deployed,
                'created_by' => $this->technician->id,
            ], $startedAt);

            $this->log($asset, AssetAction::MaintenanceStart, $startedAt, [
                'from_status' => AssetStatus::Deployed,
                'to_status' => AssetStatus::Maintenance,
                'performed_by' => $this->technician->id,
                'note' => 'Tiket '.$maintenance->ticket_number.' dibuka',
                'meta' => ['maintenance_id' => $maintenance->id],
            ]);

            if ($replacedComponent !== null) {
                $component = Component::where('name', $replacedComponent)->firstOrFail();
                $installedAt = $completedAt->copy()->subHours(3);

                ComponentInstallation::create([
                    'component_id' => $component->id,
                    'asset_id' => $asset->id,
                    'qty' => 1,
                    'is_factory' => false,
                    'maintenance_id' => $maintenance->id,
                    'installed_at' => $installedAt,
                    'installed_by' => $this->technician->id,
                ]);

                $this->log($asset, AssetAction::ComponentInstalled, $installedAt, [
                    'performed_by' => $this->technician->id,
                    'note' => '1x '.$component->name.' dipasang (tiket '.$maintenance->ticket_number.')',
                    'meta' => ['component_id' => $component->id, 'qty' => 1, 'maintenance_id' => $maintenance->id],
                ]);
            }

            $this->log($asset, AssetAction::MaintenanceDone, $completedAt, [
                'from_status' => AssetStatus::Maintenance,
                'to_status' => AssetStatus::Deployed,
                'performed_by' => $this->technician->id,
                'note' => 'Tiket '.$maintenance->ticket_number.' selesai, inventaris dikembalikan ke pengguna',
                'meta' => ['maintenance_id' => $maintenance->id],
            ]);
        }

        // Satu tiket terbuka untuk setiap aset yang sedang berstatus maintenance.
        $open = [
            [MaintenanceStatus::Reported, MaintenanceType::Repair, MaintenancePriority::High, 'Perangkat tidak mau menyala', HandledBy::Warranty, null],
            [MaintenanceStatus::InProgress, MaintenanceType::Repair, MaintenancePriority::Medium, 'Layar berkedip dan bergaris', HandledBy::Internal, null],
            [MaintenanceStatus::WaitingParts, MaintenanceType::Repair, MaintenancePriority::High, 'Port USB rusak, menunggu sparepart', HandledBy::Vendor, 'CV Mitra Service'],
            [MaintenanceStatus::InProgress, MaintenanceType::Upgrade, MaintenancePriority::Low, 'Upgrade penyimpanan ke SSD', HandledBy::Internal, null],
        ];

        $inMaintenance = Asset::where('status', AssetStatus::Maintenance)->orderBy('id')->get();

        foreach ($inMaintenance as $index => $asset) {
            [$status, $type, $priority, $title, $handledBy, $vendor] = $open[$index % count($open)];

            /** @var AssetLog $startLog */
            $startLog = $asset->logs()->where('action', AssetAction::MaintenanceStart)->firstOrFail();

            $this->createMaintenance([
                'ticket_number' => $nextTicket(),
                'asset_id' => $asset->id,
                'type' => $type,
                'status' => $status,
                'priority' => $priority,
                'title' => $title,
                'description' => 'Dilaporkan oleh pengguna melalui helpdesk.',
                'handled_by' => $handledBy,
                'supplier_id' => $vendor === null ? null : $vendors[$vendor]->id,
                'reported_by_employee_id' => $asset->assigned_employee_id,
                'estimated_cost' => fake()->numberBetween(3, 25) * 100_000,
                'start_date' => $startLog->created_at->toDateString(),
                'target_date' => $startLog->created_at->copy()->addDays(10)->toDateString(),
                'asset_status_before' => AssetStatus::Deployed,
                'created_by' => $this->technician->id,
            ], $startLog->created_at);
        }
    }

    /**
     * Alerts come from the same daily job production uses, so the dashboard and the
     * alert page agree with what the scheduler would produce for this data.
     */
    private function seedAlerts(): void
    {
        GenerateAlertsJob::dispatchSync();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function log(Asset $asset, AssetAction $action, CarbonInterface $at, array $attributes = []): void
    {
        (new AssetLog)->forceFill([
            'asset_id' => $asset->id,
            'action' => $action,
            'performed_by' => $this->admin->id,
            ...$attributes,
            'created_at' => $at,
        ])->save();
    }

    private function logCheckout(Asset $asset, ?Employee $employee, ?Location $location, CarbonInterface $at): void
    {
        $this->bastCounter++;

        $this->log($asset, AssetAction::Checkout, $at, [
            'from_status' => AssetStatus::Ready,
            'to_status' => AssetStatus::Deployed,
            'employee_id' => $employee?->id,
            'location_id' => $location?->id,
            'note' => $employee !== null
                ? 'BAST diserahkan ke '.$employee->name
                : 'Dipasang di '.$location?->name,
            'meta' => ['bast_number' => sprintf('BAST/IT/%s/%04d', $at->format('Y'), $this->bastCounter)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createMaintenance(array $attributes, CarbonInterface $createdAt): Maintenance
    {
        $maintenance = new Maintenance($attributes);
        $maintenance->created_at = $createdAt;
        $maintenance->updated_at = $attributes['completed_at'] ?? $createdAt;
        $maintenance->save();

        return $maintenance;
    }
}
