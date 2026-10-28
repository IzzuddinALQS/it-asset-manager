<?php

namespace App\Providers;

use App\Models\Accessory;
use App\Models\Asset;
use App\Models\Component;
use App\Models\Employee;
use App\Models\License;
use App\Models\Maintenance;
use App\Models\MaintenancePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'accessory' => Accessory::class,
            'asset' => Asset::class,
            'component' => Component::class,
            'employee' => Employee::class,
            'license' => License::class,
            'maintenance' => Maintenance::class,
            'maintenance_plan' => MaintenancePlan::class,
            'user' => User::class,
        ]);
    }
}
