<?php

use App\Enums\AssetStatus;

it('allows the documented asset status transitions', function (): void {
    expect(AssetStatus::Pending->canTransitionTo(AssetStatus::Ready))->toBeTrue()
        ->and(AssetStatus::Ready->canTransitionTo(AssetStatus::Deployed))->toBeTrue()
        ->and(AssetStatus::Ready->canTransitionTo(AssetStatus::Maintenance))->toBeTrue()
        ->and(AssetStatus::Ready->canTransitionTo(AssetStatus::Retired))->toBeTrue()
        ->and(AssetStatus::Deployed->canTransitionTo(AssetStatus::Ready))->toBeTrue()
        ->and(AssetStatus::Deployed->canTransitionTo(AssetStatus::Maintenance))->toBeTrue()
        ->and(AssetStatus::Deployed->canTransitionTo(AssetStatus::Retired))->toBeTrue()
        ->and(AssetStatus::Maintenance->canTransitionTo(AssetStatus::Ready))->toBeTrue()
        ->and(AssetStatus::Maintenance->canTransitionTo(AssetStatus::Retired))->toBeTrue();
});

it('rejects forbidden asset status transitions', function (): void {
    expect(AssetStatus::Pending->canTransitionTo(AssetStatus::Deployed))->toBeFalse()
        ->and(AssetStatus::Pending->canTransitionTo(AssetStatus::Retired))->toBeFalse()
        ->and(AssetStatus::Ready->canTransitionTo(AssetStatus::Pending))->toBeFalse()
        ->and(AssetStatus::Deployed->canTransitionTo(AssetStatus::Pending))->toBeFalse()
        ->and(AssetStatus::Retired->canTransitionTo(AssetStatus::Ready))->toBeFalse();
});

it('treats retired as a terminal status', function (): void {
    expect(AssetStatus::Retired->allowedTransitions())->toBe([]);
});
