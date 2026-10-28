<?php

use App\Enums\Role;
use App\Http\Middleware\EnsureRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/**
 * @param  TestResponse<Response>  $response
 * @return list<string>
 */
function navigationHrefs(TestResponse $response): array
{
    /** @var list<array{group: string, items: list<array{label: string, href: string, icon: string}>}> $navigation */
    $navigation = $response->viewData('page')['props']['navigation'];

    return array_merge(...array_map(
        fn (array $section): array => array_column($section['items'], 'href'),
        $navigation,
    ));
}

const READ_MENU = ['/dashboard', '/assets', '/licenses', '/maintenance', '/inventory', '/directory', '/reports', '/alerts'];

it('shows the settings menu to admins only', function (Role $role, array $expectedMenu): void {
    $response = actingAs(User::factory()->create(['role' => $role]))->get('/dashboard')->assertOk();

    expect(navigationHrefs($response))->toBe($expectedMenu);
})->with([
    'admin' => [Role::Admin, [...READ_MENU, '/settings']],
    'technician' => [Role::Technician, READ_MENU],
    'viewer' => [Role::Viewer, READ_MENU],
]);

it('lets every role open the menu pages it is shown', function (Role $role): void {
    $user = User::factory()->create(['role' => $role]);

    foreach (READ_MENU as $href) {
        actingAs($user)->get($href)->assertOk();
    }
})->with(Role::cases());

it('restricts the settings page to admins through the role middleware', function (Role $role, int $status): void {
    actingAs(User::factory()->create(['role' => $role]))->get('/settings')->assertStatus($status);
})->with([
    'admin' => [Role::Admin, 200],
    'technician' => [Role::Technician, 403],
    'viewer' => [Role::Viewer, 403],
]);

it('redirects guests away from module pages', function (string $href): void {
    get($href)->assertRedirect('/login');
})->with(['/assets', '/settings']);

it('offers different actions per role', function (string $href, Role $role, bool $canResolve): void {
    actingAs(User::factory()->create(['role' => $role]))
        ->get($href)
        ->assertInertia(fn ($page) => $page
            ->component('Alerts/Index')
            ->where('can.resolve', $canResolve));
})->with([
    'technician on alerts' => ['/alerts', Role::Technician, true],
    'viewer on alerts' => ['/alerts', Role::Viewer, false],
]);

it('shares role label and coarse permissions with the frontend', function (): void {
    actingAs(User::factory()->technician()->create())
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.role', 'technician')
            ->where('auth.user.role_label', 'Teknisi IT')
            ->where('permissions.operate', true)
            ->where('permissions.manage', false));
});

it('rejects unauthenticated requests in the role middleware itself', function (): void {
    (new EnsureRole)->handle(Request::create('/settings'), fn () => response('ok'), 'admin');
})->throws(HttpException::class);
