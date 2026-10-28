<?php

use App\Enums\AlertSeverity;
use App\Enums\AlertType;
use App\Enums\Role;
use App\Models\Alert;
use App\Models\Asset;

use function Pest\Laravel\actingAs;

it('lists open alerts with a link to their subject', function (): void {
    $asset = Asset::factory()->create();
    Alert::factory()->create(['subject_id' => $asset->id]);
    Alert::factory()->create(['resolved_at' => now()]);

    actingAs(userWithRole(Role::Viewer))
        ->get('/alerts')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Alerts/Index')
            ->has('alerts.data', 1)
            ->where('alerts.data.0.subject_label', $asset->asset_tag)
            ->where('alerts.data.0.subject_url', "/assets/{$asset->id}"));
});

it('filters by type, severity and status', function (): void {
    Alert::factory()->create(['type' => AlertType::WarrantyExpiring, 'severity' => AlertSeverity::Warning]);
    Alert::factory()->create(['type' => AlertType::OverdueReturn, 'severity' => AlertSeverity::Critical]);
    Alert::factory()->create(['type' => AlertType::OverdueReturn, 'severity' => AlertSeverity::Warning, 'resolved_at' => now()]);

    $viewer = userWithRole(Role::Viewer);

    actingAs($viewer)->get('/alerts?type=overdue_return')
        ->assertInertia(fn ($page) => $page->has('alerts.data', 1)->where('summary.critical', 1));
    actingAs($viewer)->get('/alerts?severity=warning')
        ->assertInertia(fn ($page) => $page->has('alerts.data', 1)->where('alerts.data.0.type', 'warranty_expiring'));
    actingAs($viewer)->get('/alerts?status=resolved')
        ->assertInertia(fn ($page) => $page->has('alerts.data', 1)->where('filters.status', 'resolved'));
});

it('lets a technician resolve an alert', function (): void {
    $alert = Alert::factory()->create();
    $technician = userWithRole(Role::Technician);

    actingAs($technician)->post("/alerts/{$alert->id}/resolve")->assertRedirect()->assertSessionHas('success');

    expect($alert->fresh()->resolved_at)->not->toBeNull()
        ->and($alert->fresh()->resolved_by)->toBe($technician->id);
});

it('forbids viewers from resolving alerts', function (): void {
    $alert = Alert::factory()->create();

    actingAs(userWithRole(Role::Viewer))->post("/alerts/{$alert->id}/resolve")->assertForbidden();

    expect($alert->fresh()->resolved_at)->toBeNull();
});
