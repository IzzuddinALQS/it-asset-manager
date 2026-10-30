<?php

use App\Http\Controllers\Alerts\AlertController;
use App\Http\Controllers\Alerts\ResolveAlertController;
use App\Http\Controllers\Api\AssetLookupController;
use App\Http\Controllers\Api\AssetSearchController;
use App\Http\Controllers\Api\EmployeeSearchController;
use App\Http\Controllers\Api\OptionSearchController;
use App\Http\Controllers\Assets\AssetController;
use App\Http\Controllers\Assets\AssetLabelController;
use App\Http\Controllers\Assets\CheckinAssetController;
use App\Http\Controllers\Assets\CheckoutAssetController;
use App\Http\Controllers\Assets\RetireAssetController;
use App\Http\Controllers\Assets\TagAssetController;
use App\Http\Controllers\Auth\DevLoginController;
use App\Http\Controllers\Auth\HerdLoginController;
use App\Http\Controllers\Auth\LocalLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Directory\EmployeeController;
use App\Http\Controllers\Directory\SyncEmployeesController;
use App\Http\Controllers\Inventory\AccessoryController;
use App\Http\Controllers\Inventory\CheckinAccessoryController;
use App\Http\Controllers\Inventory\CheckoutAccessoryController;
use App\Http\Controllers\Inventory\ComponentController;
use App\Http\Controllers\Inventory\InstallComponentController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\RemoveComponentController;
use App\Http\Controllers\Licenses\AssignLicenseSeatController;
use App\Http\Controllers\Licenses\LicenseController;
use App\Http\Controllers\Licenses\ReleaseLicenseSeatController;
use App\Http\Controllers\Maintenance\CancelMaintenanceController;
use App\Http\Controllers\Maintenance\CompleteMaintenanceController;
use App\Http\Controllers\Maintenance\MaintenanceController;
use App\Http\Controllers\Maintenance\MaintenancePlanController;
use App\Http\Controllers\Maintenance\RunMaintenancePlanController;
use App\Http\Controllers\Maintenance\UpdateMaintenanceStatusController;
use App\Http\Controllers\Reports\ExportReportController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Settings\GrantHerdUserController;
use App\Http\Controllers\Settings\MasterDataController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\UserPasswordController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [DevLoginController::class, 'index'])->name('login');
    Route::post('/login', LocalLoginController::class)->name('login.local');
    Route::post('/dev-login/{user}', [DevLoginController::class, 'store'])->name('dev-login.store');
    Route::get('/auth/redirect', [HerdLoginController::class, 'redirect'])->name('herd.redirect');
    Route::get('/auth/callback', [HerdLoginController::class, 'callback'])->name('herd.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [DevLoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/assets/labels', AssetLabelController::class)->name('assets.labels');
    Route::resource('assets', AssetController::class)->except('destroy');
    Route::post('/assets/{asset}/tag', TagAssetController::class)->name('assets.tag');
    Route::post('/assets/{asset}/checkout', CheckoutAssetController::class)->name('assets.checkout');
    Route::post('/assets/{asset}/checkin', CheckinAssetController::class)->name('assets.checkin');
    Route::post('/assets/{asset}/retire', RetireAssetController::class)->name('assets.retire');

    // Internal JSON endpoint used by the tag scanner; shares the web session.
    Route::get('/api/assets/by-tag/{tag}', AssetLookupController::class)->name('api.assets.by-tag');
    Route::get('/api/assets/search', AssetSearchController::class)->name('api.assets.search');
    Route::get('/api/employees/search', EmployeeSearchController::class)->name('api.employees.search');
    Route::get('/api/options/{source}', OptionSearchController::class)->name('api.options.search');

    Route::get('/inventory', InventoryController::class)->name('inventory.index');
    Route::resource('inventory/components', ComponentController::class)
        ->only(['create', 'store', 'edit', 'update'])
        ->names('inventory.components');
    Route::resource('inventory/accessories', AccessoryController::class)
        ->only(['create', 'store', 'show', 'edit', 'update'])
        ->names('inventory.accessories');
    Route::post('/assets/{asset}/components', InstallComponentController::class)->name('assets.components.install');
    Route::post('/component-installations/{installation}/remove', RemoveComponentController::class)->name('component-installations.remove');
    Route::post('/inventory/accessories/{accessory}/checkout', CheckoutAccessoryController::class)->name('inventory.accessories.checkout');
    Route::post('/accessory-checkouts/{checkout}/checkin', CheckinAccessoryController::class)->name('accessory-checkouts.checkin');

    Route::resource('licenses', LicenseController::class)->except('destroy');
    Route::post('/licenses/{license}/seats', AssignLicenseSeatController::class)->name('licenses.seats.assign');
    Route::post('/license-seats/{seat}/release', ReleaseLicenseSeatController::class)->name('license-seats.release');

    Route::get('/maintenance/plans', [MaintenancePlanController::class, 'index'])->name('maintenance.plans.index');
    Route::post('/maintenance/plans', [MaintenancePlanController::class, 'store'])->name('maintenance.plans.store');
    Route::put('/maintenance/plans/{plan}', [MaintenancePlanController::class, 'update'])->name('maintenance.plans.update');
    Route::post('/maintenance/plans/{plan}/run', RunMaintenancePlanController::class)->name('maintenance.plans.run');
    Route::resource('maintenance', MaintenanceController::class)->only(['index', 'store', 'show']);
    Route::post('/maintenance/{maintenance}/status', UpdateMaintenanceStatusController::class)->name('maintenance.status');
    Route::post('/maintenance/{maintenance}/complete', CompleteMaintenanceController::class)->name('maintenance.complete');
    Route::post('/maintenance/{maintenance}/cancel', CancelMaintenanceController::class)->name('maintenance.cancel');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/resolve', ResolveAlertController::class)->name('alerts.resolve');

    Route::get('/directory', [EmployeeController::class, 'index'])->name('directory.index');
    Route::get('/directory/{employee}', [EmployeeController::class, 'show'])->name('directory.show');

    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/reports/export', ExportReportController::class)->name('reports.export');

    Route::get('/profile', ProfileController::class)->name('profile');

    Route::middleware('role:admin')->prefix('settings')->name('settings.')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/herd-sync', SyncEmployeesController::class)->name('herd-sync');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::post('/herd-users', GrantHerdUserController::class)->name('herd-users.grant');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::put('/users/{user}/password', UserPasswordController::class)->name('users.password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/master/{type}', [MasterDataController::class, 'index'])->name('master.index');
        Route::post('/master/{type}', [MasterDataController::class, 'store'])->name('master.store');
        Route::put('/master/{type}/{record}', [MasterDataController::class, 'update'])->whereNumber('record')->name('master.update');
        Route::delete('/master/{type}/{record}', [MasterDataController::class, 'destroy'])->whereNumber('record')->name('master.destroy');
    });
});
