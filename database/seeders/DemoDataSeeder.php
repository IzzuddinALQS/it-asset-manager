<?php

namespace Database\Seeders;

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
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
use App\Models\Accessory;
use App\Models\AccessoryCheckout;
use App\Models\Alert;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
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
            ['name' => 'Kantor Pusat Jakarta', 'address' => 'Jl. Jend. Sudirman Kav. 52-53, Jakarta Selatan'],
            ['name' => 'Plant Cikarang', 'address' => 'Kawasan Industri Jababeka, Cikarang Barat'],
            ['name' => 'Cabang Surabaya', 'address' => 'Jl. Mayjen Sungkono No. 89, Surabaya'],
            ['name' => 'Gudang IT', 'address' => 'Gedung IT Lantai 2, Kantor Pusat Jakarta'],
        ])->mapWithKeys(fn (array $location): array => [
            $location['name'] => Location::create($location),
        ]);

        $operations = Department::create(['herd_id' => 'DEPT-HRD-01', 'name' => 'Information Technology', 'synced_at' => now()]);
        Department::create(['herd_id' => 'DEPT-HRD-02', 'name' => 'Finance & Accounting', 'synced_at' => now()]);
        Department::create(['herd_id' => 'DEPT-HRD-03', 'name' => 'Human Resources', 'synced_at' => now()]);
        Department::create(['herd_id' => 'DEPT-HRD-04', 'name' => 'Produksi', 'synced_at' => now()]);
        Department::create(['herd_id' => 'DEPT-HRD-05', 'name' => 'Logistik', 'synced_at' => now()]);
        Department::create(['herd_id' => 'DEPT-HRD-06', 'name' => 'Marketing', 'synced_at' => now()]);
        Department::create(['herd_id' => 'DEPT-HRD-07', 'name' => 'Engineering', 'synced_at' => now()]);

        $people = [
            ['Budi Santoso', 'IT Manager', 'Information Technology', 'Kantor Pusat Jakarta'],
            ['Rina Kartika', 'IT Support Specialist', 'Information Technology', 'Kantor Pusat Jakarta'],
            ['Agus Prasetyo', 'Network Administrator', 'Information Technology', 'Kantor Pusat Jakarta'],
            ['Dewi Lestari', 'Finance Manager', 'Finance & Accounting', 'Kantor Pusat Jakarta'],
            ['Hendra Wijaya', 'Accounting Staff', 'Finance & Accounting', 'Kantor Pusat Jakarta'],
            ['Siti Rahmawati', 'HR Manager', 'Human Resources', 'Kantor Pusat Jakarta'],
            ['Andi Saputra', 'Recruitment Officer', 'Human Resources', 'Kantor Pusat Jakarta'],
            ['Joko Widodo', 'Production Supervisor', 'Produksi', 'Plant Cikarang'],
            ['Sri Wahyuni', 'Quality Control', 'Produksi', 'Plant Cikarang'],
            ['Bambang Sudarso', 'Operator Produksi', 'Produksi', 'Plant Cikarang'],
            ['Nur Aisyah', 'Warehouse Supervisor', 'Logistik', 'Gudang IT'],
            ['Rudi Hartono', 'Logistics Staff', 'Logistik', 'Plant Cikarang'],
            ['Maya Anggraini', 'Marketing Manager', 'Marketing', 'Kantor Pusat Jakarta'],
            ['Fajar Nugroho', 'Content Specialist', 'Marketing', 'Cabang Surabaya'],
            ['Indra Gunawan', 'Mechanical Engineer', 'Engineering', 'Plant Cikarang'],
            ['Putri Ayu', 'Electrical Engineer', 'Engineering', 'Plant Cikarang'],
            ['Yusuf Maulana', 'Sales Executive', 'Marketing', 'Cabang Surabaya'],
            ['Lina Marlina', 'Admin Cabang', 'Finance & Accounting', 'Cabang Surabaya'],
            ['Taufik Hidayat', 'IT Support', 'Information Technology', 'Cabang Surabaya'],
            ['Ayu Permata', 'HR Staff', 'Human Resources', 'Plant Cikarang'],
            ['Eko Purnomo', 'Maintenance Technician', 'Produksi', 'Plant Cikarang'],
            ['Wulan Sari', 'Finance Staff', 'Finance & Accounting', 'Kantor Pusat Jakarta'],
            ['Dimas Aditya', 'Software Engineer', 'Engineering', 'Kantor Pusat Jakarta'],
            ['Nadia Safitri', 'UX Researcher', 'Engineering', 'Kantor Pusat Jakarta'],
            ['Reza Fahlevi', 'Data Analyst', 'Information Technology', 'Kantor Pusat Jakarta'],
            ['Citra Dewi', 'Legal Officer', 'Human Resources', 'Kantor Pusat Jakarta'],
            ['Arif Setiawan', 'Procurement Staff', 'Logistik', 'Kantor Pusat Jakarta'],
            ['Mega Puspa', 'Customer Support', 'Marketing', 'Cabang Surabaya'],
            ['Bayu Kurniawan', 'Driver', 'Logistik', 'Plant Cikarang'],
            ['Rani Oktaviani', 'Secretary', 'Human Resources', 'Kantor Pusat Jakarta'],
            ['Hadi Susanto', 'Security', 'Produksi', 'Plant Cikarang'],
            ['Ika Puspitasari', 'Payroll Staff', 'Finance & Accounting', 'Kantor Pusat Jakarta'],
            ['Firman Ramadhan', 'Network Engineer', 'Information Technology', 'Kantor Pusat Jakarta'],
            ['Gita Nirmala', 'Product Manager', 'Engineering', 'Kantor Pusat Jakarta'],
            ['Ahmad Fauzi', 'Field Engineer', 'Engineering', 'Cabang Surabaya'],
            ['Sari Indah', 'Purchasing Staff', 'Logistik', 'Kantor Pusat Jakarta'],
        ];

        $employees = collect();
        foreach ($people as $index => [$name, $title, $department, $location]) {
            $employees->push(Employee::create([
                'herd_id' => 'HERD-'.str_pad((string) (1001 + $index), 6, '0', STR_PAD_LEFT),
                'employee_number' => 'NIK'.str_pad((string) (24001 + $index), 5, '0', STR_PAD_LEFT),
                'name' => $name,
                'email' => strtolower(str_replace([' ', '.'], ['.', ''], $name)).'@perusahaan.co.id',
                'job_title' => $title,
                'department_id' => Department::where('name', $department)->value('id'),
                'location_id' => $locations[$location]->id,
                'employment_status' => EmploymentStatus::Active,
                'synced_at' => now(),
            ]));
        }

        // Dua karyawan resign dan satu mutasi, masing-masing masih memegang aset.
        $employees[9]->update([
            'employment_status' => EmploymentStatus::Resigned,
            'status_effective_date' => now()->subDays(10)->toDateString(),
        ]);
        $employees[28]->update([
            'employment_status' => EmploymentStatus::Resigned,
            'status_effective_date' => now()->subDays(3)->toDateString(),
        ]);
        $employees[13]->update([
            'employment_status' => EmploymentStatus::Transferred,
            'status_effective_date' => now()->subDays(5)->toDateString(),
        ]);

        User::create(['employee_id' => $employees[0]->id, 'role' => Role::Admin, 'last_login_at' => now()->subHours(2)]);
        User::create(['employee_id' => $employees[1]->id, 'role' => Role::Technician, 'last_login_at' => now()->subHours(5)]);
        User::create(['employee_id' => $employees[3]->id, 'role' => Role::Viewer, 'last_login_at' => now()->subDay()]);

        HerdSyncRun::create([
            'started_at' => now()->subMinutes(4),
            'finished_at' => now()->subMinutes(3),
            'status' => 'success',
            'created_count' => 2,
            'updated_count' => 34,
            'offboarding_count' => 3,
        ]);
        HerdSyncRun::create([
            'started_at' => now()->subDay()->setTime(2, 0),
            'finished_at' => now()->subDay()->setTime(2, 1),
            'status' => 'success',
            'created_count' => 0,
            'updated_count' => 36,
            'offboarding_count' => 1,
        ]);
    }

    private function seedMasterData(): void
    {
        $manufacturers = collect(['Dell', 'Lenovo', 'HP', 'Apple', 'Asus', 'Cisco', 'Logitech', 'Microsoft', 'Samsung', 'Epson', 'Kingston', 'TP-Link'])
            ->mapWithKeys(fn (string $name): array => [$name => Manufacturer::create(['name' => $name])]);

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

        foreach ([['RAM', 'Memori'], ['Penyimpanan', 'Storage'], ['Baterai', 'Battery'], ['Power Supply', 'PSU']] as [$name, $spec]) {
            Category::create(['name' => $name, 'type' => CategoryType::Component]);
        }

        foreach (['Mouse', 'Keyboard', 'Headset', 'Docking Station', 'Kabel & Adaptor'] as $name) {
            Category::create(['name' => $name, 'type' => CategoryType::Accessory]);
        }

        Category::create(['name' => 'Software', 'type' => CategoryType::License]);

        $models = [
            ['Dell Latitude 5440', 'L5440', 'Dell', 'Laptop', 36],
            ['Dell OptiPlex 7010', 'O7010', 'Dell', 'Desktop', 48],
            ['Lenovo ThinkPad T14 Gen 4', 'T14G4', 'Lenovo', 'Laptop', 36],
            ['Lenovo ThinkCentre M70q', 'M70Q', 'Lenovo', 'Desktop', 48],
            ['HP EliteBook 840 G10', 'E840G10', 'HP', 'Laptop', 36],
            ['HP ProDesk 400 G9', 'P400G9', 'HP', 'Desktop', 48],
            ['Apple MacBook Pro 14 M3', 'MBP14M3', 'Apple', 'Laptop', 36],
            ['Dell UltraSharp U2723QE', 'U2723QE', 'Dell', 'Monitor', 60],
            ['Samsung Odyssey G5 27"', 'G5-27', 'Samsung', 'Monitor', 60],
            ['Epson EcoTank L3250', 'L3250', 'Epson', 'Printer', 60],
            ['Cisco Catalyst 1000-24T', 'C1000-24T', 'Cisco', 'Perangkat Jaringan', 60],
            ['TP-Link Omada EAP225', 'EAP225', 'TP-Link', 'Perangkat Jaringan', 48],
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
        $admin = User::where('role', Role::Admin)->firstOrFail();
        $employees = Employee::active()->get();
        $models = AssetModel::with('category')->get();
        $suppliers = Supplier::all();
        $locations = Location::all();

        $distribution = [
            AssetStatus::Pending->value => 6,
            AssetStatus::Ready->value => 18,
            AssetStatus::Deployed->value => 34,
            AssetStatus::Maintenance->value => 4,
            AssetStatus::Retired->value => 3,
        ];

        $counters = [];
        $employeeCursor = 0;

        foreach ($distribution as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                /** @var AssetModel $model */
                $model = $models->random();
                $prefix = $model->category->tag_prefix ?? 'AS';
                $counters[$prefix] = ($counters[$prefix] ?? 0) + 1;

                $assetTag = $status === AssetStatus::Pending->value
                    ? 'TMP-'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT)
                    : $prefix.'-'.str_pad((string) $counters[$prefix], 5, '0', STR_PAD_LEFT);

                $assignedEmployee = null;
                if ($status === AssetStatus::Deployed->value) {
                    $assignedEmployee = $employees[$employeeCursor % $employees->count()];
                    $employeeCursor++;
                }

                // Sisakan dua aset pada karyawan yang sudah resign/mutasi.
                if (in_array($i, [3, 7], true) && $status === AssetStatus::Deployed->value) {
                    $offboarding = Employee::whereIn('employment_status', [
                        EmploymentStatus::Resigned,
                        EmploymentStatus::Transferred,
                    ])->get();
                    $assignedEmployee = $offboarding[$i % $offboarding->count()];
                }

                $asset = Asset::create([
                    'asset_tag' => $assetTag,
                    'serial_number' => 'SN'.strtoupper(fake()->bothify('####????')),
                    'asset_model_id' => $model->id,
                    'status' => $status,
                    'assigned_employee_id' => $assignedEmployee?->id,
                    'assigned_at' => $assignedEmployee === null ? null : now()->subDays(fake()->numberBetween(5, 240)),
                    'expected_return_date' => null,
                    'location_id' => $locations->random()->id,
                    'supplier_id' => $suppliers->random()->id,
                    'purchase_order' => 'PO-2026-'.str_pad((string) fake()->numberBetween(1, 999), 4, '0', STR_PAD_LEFT),
                    'purchase_date' => now()->subMonths(fake()->numberBetween(1, 40))->toDateString(),
                    'purchase_cost' => fake()->numberBetween(5_500_000, 32_000_000),
                    'warranty_until' => now()->addDays(fake()->numberBetween(-120, 500))->toDateString(),
                    'last_audited_at' => fake()->boolean(40) ? now()->subMonths(fake()->numberBetween(1, 8))->toDateString() : null,
                    'notes' => null,
                    'retired_at' => $status === AssetStatus::Retired->value ? now()->subDays(fake()->numberBetween(5, 90)) : null,
                    'retire_reason' => $status === AssetStatus::Retired->value ? RetireReason::EndOfLife : null,
                    'created_by' => $admin->id,
                ]);

                AssetLog::create([
                    'asset_id' => $asset->id,
                    'action' => AssetAction::Created,
                    'to_status' => AssetStatus::Pending,
                    'performed_by' => $admin->id,
                    'note' => 'Aset dicatat dari pengadaan '.$asset->purchase_order,
                    'created_at' => $asset->created_at,
                ]);

                if ($status !== AssetStatus::Pending->value) {
                    AssetLog::create([
                        'asset_id' => $asset->id,
                        'action' => AssetAction::Tagged,
                        'from_status' => AssetStatus::Pending,
                        'to_status' => AssetStatus::Ready,
                        'performed_by' => $admin->id,
                        'note' => 'Label QR '.$asset->asset_tag.' dicetak',
                        'created_at' => $asset->created_at->copy()->addDay(),
                    ]);
                }

                if ($assignedEmployee !== null) {
                    AssetLog::create([
                        'asset_id' => $asset->id,
                        'action' => AssetAction::Checkout,
                        'from_status' => AssetStatus::Ready,
                        'to_status' => AssetStatus::Deployed,
                        'employee_id' => $assignedEmployee->id,
                        'performed_by' => $admin->id,
                        'note' => 'BAST diserahkan ke '.$assignedEmployee->name,
                        'created_at' => $asset->assigned_at,
                    ]);
                }

                if ($status === AssetStatus::Retired->value) {
                    AssetLog::create([
                        'asset_id' => $asset->id,
                        'action' => AssetAction::Retired,
                        'from_status' => AssetStatus::Ready,
                        'to_status' => AssetStatus::Retired,
                        'performed_by' => $admin->id,
                        'note' => 'Habis masa pakai, aset dikeluarkan dari daftar aktif',
                        'created_at' => $asset->retired_at,
                    ]);
                }
            }
        }

        // Simulasi aset yang sedang diperbaiki.
        Asset::where('status', AssetStatus::Maintenance)->get()->each(function (Asset $asset): void {
            AssetLog::create([
                'asset_id' => $asset->id,
                'action' => AssetAction::MaintenanceStart,
                'from_status' => AssetStatus::Deployed,
                'to_status' => AssetStatus::Maintenance,
                'performed_by' => User::where('role', Role::Technician)->value('id'),
                'note' => 'Dilaporkan pengguna, unit ditarik ke meja IT',
                'created_at' => now()->subDays(3),
            ]);
        });
    }

    private function seedLicenses(): void
    {
        $admin = User::where('role', Role::Admin)->firstOrFail();
        $employees = Employee::active()->take(60)->get();
        $software = Category::where('type', CategoryType::License)->firstOrFail();
        $microsoft = Manufacturer::where('name', 'Microsoft')->firstOrFail();
        $suppliers = Supplier::all();

        $data = [
            ['Microsoft 365 Business Premium', LicenseType::Subscription, 50, 1_450_000, now()->addDays(21)],
            ['Adobe Creative Cloud All Apps', LicenseType::Subscription, 10, 8_900_000, now()->addDays(75)],
            ['Windows 11 Pro OEM', LicenseType::Oem, 40, 2_100_000, null],
            ['AutoCAD 2026', LicenseType::Perpetual, 8, 24_000_000, null],
            ['Kaspersky Endpoint Security', LicenseType::Subscription, 60, 320_000, now()->addDays(12)],
            ['Slack Business+', LicenseType::Subscription, 25, 1_100_000, now()->addDays(120)],
        ];

        foreach ($data as $index => [$name, $type, $seats, $cost, $expires]) {
            $license = License::create([
                'name' => $name,
                'manufacturer_id' => $index < 4 ? $microsoft->id : Manufacturer::where('name', 'Adobe')->value('id') ?? $microsoft->id,
                'category_id' => $software->id,
                'license_type' => $type,
                'product_key' => strtoupper(fake()->bothify('????-????-????-####')),
                'seats_total' => $seats,
                'supplier_id' => $suppliers->random()->id,
                'purchase_order' => 'PO-2026-'.str_pad((string) (300 + $index), 4, '0', STR_PAD_LEFT),
                'purchase_date' => now()->subMonths(8)->toDateString(),
                'annual_cost' => $cost,
                'expires_at' => $expires?->toDateString(),
                'notes' => null,
            ]);

            $used = $index === 0 ? 50 : min($seats, fake()->numberBetween(3, $seats));

            foreach ($employees->take($used) as $employee) {
                LicenseSeat::create([
                    'license_id' => $license->id,
                    'employee_id' => $employee->id,
                    'assigned_at' => now()->subMonths(fake()->numberBetween(1, 6)),
                    'assigned_by' => $admin->id,
                ]);
            }
        }

        Alert::create([
            'type' => AlertType::LicenseSeatsFull,
            'severity' => AlertSeverity::Info,
            'subject_type' => License::class,
            'subject_id' => License::where('name', 'Microsoft 365 Business Premium')->value('id'),
            'message' => 'Seluruh seat Microsoft 365 Business Premium (50/50) sudah terpakai.',
            'due_date' => null,
        ]);

        Alert::create([
            'type' => AlertType::LicenseExpiring,
            'severity' => AlertSeverity::Warning,
            'subject_type' => License::class,
            'subject_id' => License::where('name', 'Kaspersky Endpoint Security')->value('id'),
            'message' => 'Lisensi Kaspersky Endpoint Security berakhir dalam 12 hari.',
            'due_date' => now()->addDays(12)->toDateString(),
        ]);
    }

    private function seedInventory(): void
    {
        $admin = User::where('role', Role::Admin)->firstOrFail();
        $technician = User::where('role', Role::Technician)->firstOrFail();
        $employees = Employee::active()->take(12)->get();

        $components = [
            ['RAM DDR4 8 GB', 'SODIMM 3200 MHz', 'RAM', 'Kingston', 40, 8, 450_000],
            ['SSD NVMe 512 GB', 'PCIe Gen4', 'Penyimpanan', 'Samsung', 25, 5, 850_000],
            ['Baterai Laptop Dell', '3-cell 42Wh', 'Baterai', 'Dell', 12, 3, 720_000],
            ['Power Supply 500W', '80+ Bronze', 'Power Supply', 'Dell', 8, 2, 650_000],
            ['RAM DDR5 16 GB', 'SODIMM 4800 MHz', 'RAM', 'Kingston', 16, 4, 780_000],
            ['SSD SATA 1 TB', '2.5 inch', 'Penyimpanan', 'Samsung', 10, 3, 1_100_000],
        ];

        $installedAssets = Asset::where('status', AssetStatus::Deployed)->take(10)->get();

        foreach ($components as $index => [$name, $spec, $category, $manufacturer, $qty, $min, $cost]) {
            $component = Component::create([
                'name' => $name,
                'spec' => $spec,
                'category_id' => Category::where('name', $category)->value('id'),
                'manufacturer_id' => Manufacturer::where('name', $manufacturer)->value('id'),
                'qty_total' => $qty,
                'min_qty' => $min,
                'unit_cost' => $cost,
                'location_id' => Location::where('name', 'Gudang IT')->value('id'),
            ]);

            if ($installedAssets->has($index)) {
                ComponentInstallation::create([
                    'component_id' => $component->id,
                    'asset_id' => $installedAssets[$index]->id,
                    'qty' => 1,
                    'is_factory' => false,
                    'installed_at' => now()->subMonths(2),
                    'installed_by' => $technician->id,
                ]);
            }
        }

        $accessories = [
            ['Mouse Wireless Logitech M331', 'M331', 'Mouse', 'Logitech', 60, 10, 185_000],
            ['Keyboard Mechanical Keychron K2', 'K2', 'Keyboard', 'Logitech', 20, 5, 1_250_000],
            ['Headset Jabra Evolve 20', 'EV20', 'Headset', 'Logitech', 35, 8, 950_000],
            ['Docking Station Dell WD19S', 'WD19S', 'Docking Station', 'Dell', 15, 4, 2_400_000],
            ['Kabel HDMI 2m', 'HDMI-2M', 'Kabel & Adaptor', 'TP-Link', 50, 15, 45_000],
            ['Webcam Logitech C920', 'C920', 'Kabel & Adaptor', 'Logitech', 12, 3, 1_050_000],
        ];

        foreach ($accessories as $index => [$name, $model, $category, $manufacturer, $qty, $min, $cost]) {
            $accessory = Accessory::create([
                'name' => $name,
                'model' => $model,
                'category_id' => Category::where('name', $category)->value('id'),
                'manufacturer_id' => Manufacturer::where('name', $manufacturer)->value('id'),
                'qty_total' => $qty,
                'min_qty' => $min,
                'unit_cost' => $cost,
                'location_id' => Location::where('name', 'Gudang IT')->value('id'),
            ]);

            foreach ($employees->take(3) as $position => $employee) {
                AccessoryCheckout::create([
                    'accessory_id' => $accessory->id,
                    'employee_id' => $employee->id,
                    'qty' => 1,
                    'checked_out_at' => now()->subDays(10 + $position)->subHours($index),
                    'checked_out_by' => $admin->id,
                ]);
            }
        }

        Alert::create([
            'type' => AlertType::LowStock,
            'severity' => AlertSeverity::Warning,
            'subject_type' => Component::class,
            'subject_id' => Component::where('name', 'Power Supply 500W')->value('id'),
            'message' => 'Stok Power Supply 500W tersisa 7 unit, di bawah minimum 8 unit.',
            'due_date' => null,
        ]);
    }

    private function seedMaintenances(): void
    {
        $admin = User::where('role', Role::Admin)->firstOrFail();
        $technician = User::where('role', Role::Technician)->firstOrFail();
        $suppliers = Supplier::where('is_service_vendor', true)->get();
        $assets = Asset::whereIn('status', [AssetStatus::Maintenance, AssetStatus::Deployed, AssetStatus::Ready])->take(10)->get();

        $plans = [
            ['Servis rutin laptop (6 bulan)', 'Laptop', 6, now()->addDays(18)],
            ['Kalibrasi printer produksi', 'Printer', 12, now()->addDays(7)],
            ['Pembersihan server & jaringan', 'Perangkat Jaringan', 3, now()->addDays(30)],
            ['Pengecekan baterai laptop', 'Laptop', 12, now()->addDays(45)],
        ];

        foreach ($plans as [$name, $category, $interval, $due]) {
            MaintenancePlan::create([
                'name' => $name,
                'category_id' => Category::where('name', $category)->value('id'),
                'location_id' => null,
                'interval_months' => $interval,
                'next_due_date' => $due->toDateString(),
                'pic' => 'Tim IT Support',
                'supplier_id' => $suppliers->random()->id,
                'is_active' => true,
            ]);
        }

        $statuses = [
            MaintenanceStatus::Reported,
            MaintenanceStatus::Reported,
            MaintenanceStatus::InProgress,
            MaintenanceStatus::InProgress,
            MaintenanceStatus::WaitingParts,
            MaintenanceStatus::Done,
            MaintenanceStatus::Done,
            MaintenanceStatus::Cancelled,
        ];

        foreach ($assets as $index => $asset) {
            $status = $statuses[$index % count($statuses)];
            $type = $index % 4 === 0 ? MaintenanceType::Preventive : MaintenanceType::Repair;

            Maintenance::create([
                'ticket_number' => 'MT-2026-'.str_pad((string) (1 + $index), 4, '0', STR_PAD_LEFT),
                'asset_id' => $asset->id,
                'plan_id' => null,
                'type' => $type,
                'status' => $status,
                'priority' => $index % 3 === 0 ? MaintenancePriority::High : MaintenancePriority::Medium,
                'title' => $type === MaintenanceType::Preventive
                    ? 'Servis preventif '.$asset->asset_tag
                    : 'Perbaikan '.$asset->asset_tag,
                'description' => 'Keluhan pengguna: perangkat lambat dan kadang mati sendiri.',
                'handled_by' => $index % 3 === 0 ? HandledBy::Vendor : HandledBy::Internal,
                'supplier_id' => $index % 3 === 0 ? $suppliers->random()->id : null,
                'reported_by_employee_id' => $asset->assigned_employee_id ?? null,
                'estimated_cost' => fake()->numberBetween(250_000, 2_500_000),
                'actual_cost' => $status === MaintenanceStatus::Done ? fake()->numberBetween(300_000, 2_000_000) : null,
                'start_date' => now()->subDays(6)->toDateString(),
                'target_date' => now()->addDays(4)->toDateString(),
                'completed_at' => $status === MaintenanceStatus::Done ? now()->subDays(2) : null,
                'asset_status_before' => AssetStatus::Deployed,
                'created_by' => $index % 2 === 0 ? $admin->id : $technician->id,
            ]);
        }

        Alert::create([
            'type' => AlertType::MaintenanceDue,
            'severity' => AlertSeverity::Warning,
            'subject_type' => MaintenancePlan::class,
            'subject_id' => MaintenancePlan::where('name', 'Kalibrasi printer produksi')->value('id'),
            'message' => 'Jadwal kalibrasi printer produksi jatuh tempo dalam 7 hari.',
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
    }

    private function seedAlerts(): void
    {
        $offboarding = Employee::where('employment_status', EmploymentStatus::Resigned)->first();

        if ($offboarding !== null) {
            Alert::create([
                'type' => AlertType::EmployeeOffboarding,
                'severity' => AlertSeverity::Critical,
                'subject_type' => Employee::class,
                'subject_id' => $offboarding->id,
                'message' => $offboarding->name.' sudah resign dan masih memegang aset perusahaan.',
                'due_date' => Carbon::parse($offboarding->status_effective_date)->addDays(7)->toDateString(),
            ]);
        }

        $assets = Asset::whereNotNull('warranty_until')
            ->whereDate('warranty_until', '>=', now()->toDateString())
            ->orderBy('warranty_until')
            ->take(3)
            ->get();

        foreach ($assets as $asset) {
            Alert::create([
                'type' => AlertType::WarrantyExpiring,
                'severity' => AlertSeverity::Warning,
                'subject_type' => Asset::class,
                'subject_id' => $asset->id,
                'message' => 'Garansi '.$asset->asset_tag.' berakhir pada '.$asset->warranty_until->format('d M Y').'.',
                'due_date' => $asset->warranty_until,
            ]);
        }
    }
}
