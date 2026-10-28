<?php

use App\Enums\AssetStatus;
use App\Enums\Role;
use App\Models\Alert;
use App\Models\Asset;
use App\Models\License;
use App\Models\MaintenancePlan;
use Database\Seeders\DemoDataSeeder;

use function Pest\Laravel\actingAs;

it('shows KPIs that match the seeded data', function (): void {
    app(DemoDataSeeder::class)->run();

    $inService = Asset::whereNot('status', AssetStatus::Retired);

    actingAs(userWithRole(Role::Viewer))
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('kpis.total', (clone $inService)->count())
            ->where('kpis.retired', Asset::where('status', AssetStatus::Retired)->count())
            ->where('kpis.deployed', Asset::where('status', AssetStatus::Deployed)->count())
            ->where('kpis.ready', Asset::where('status', AssetStatus::Ready)->count())
            ->where('kpis.maintenance', Asset::where('status', AssetStatus::Maintenance)->count())
            ->where('kpis.acquisition_value', (string) (clone $inService)->sum('purchase_cost'))
            ->has('statusDistribution', count(AssetStatus::cases()))
            ->where('categoryDistribution', fn ($items): bool => collect($items)->sum('total') === (clone $inService)->count())
            ->has('expiringLicenses', min(6, License::expiringSoon(30)->count()))
            ->has('maintenanceDue', min(6, MaintenancePlan::where('is_active', true)->whereDate('next_due_date', '<=', now()->addDays(7))->count()))
            ->has('recentAlerts', min(6, Alert::open()->count())));
});

it('renders for an empty database', function (): void {
    actingAs(userWithRole(Role::Viewer))
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('kpis.total', 0)
            ->has('categoryDistribution', 0)
            ->has('recentAlerts', 0));
});
