<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows the dev login page to guests', function (): void {
    $user = User::factory()->admin()->create();

    get('/login')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Auth/DevLogin')
            ->has('users', 1)
            ->where('users.0.id', $user->id));
});

it('logs a user in and records the login time', function (): void {
    $user = User::factory()->technician()->create();

    post("/dev-login/{$user->id}")
        ->assertRedirect('/dashboard');

    assertAuthenticatedAs($user);
    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('redirects guests from the dashboard to login', function (): void {
    get('/dashboard')->assertRedirect('/login');
});

it('redirects the root url to the dashboard', function (): void {
    actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect('/dashboard');
});

it('logs a user out', function (): void {
    $user = User::factory()->create();

    actingAs($user)->post('/logout')->assertRedirect('/login');

    assertGuest();
});
