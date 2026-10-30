<?php

use App\Enums\Role;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('renders the branded error page for missing pages', function (): void {
    get('/does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn ($page) => $page->component('Error')->where('status', 404));
});

it('renders the branded error page for forbidden pages', function (): void {
    actingAs(userWithRole(Role::Viewer))
        ->get('/assets/create')
        ->assertForbidden()
        ->assertInertia(fn ($page) => $page->component('Error')->where('status', 403));
});

it('keeps the stack trace for server errors while debugging', function (): void {
    config(['app.debug' => true]);
    Route::get('/boom', fn () => throw new RuntimeException('boom'));

    get('/boom')->assertServerError()->assertDontSee('"component":"Error"', false);
});

it('renders the branded error page for server errors in production', function (): void {
    config(['app.debug' => false]);
    Route::get('/boom', fn () => throw new RuntimeException('boom'));

    get('/boom')
        ->assertServerError()
        ->assertInertia(fn ($page) => $page->component('Error')->where('status', 500));
});
