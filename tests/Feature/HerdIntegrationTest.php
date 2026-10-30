<?php

use App\Enums\AlertType;
use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Models\Asset;
use App\Models\Employee;
use App\Models\HerdSyncRun;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\artisan;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\withSession;

beforeEach(function (): void {
    config([
        'services.herd.origin' => 'https://herd.test',
        'services.herd.api_base' => 'https://herd.test/api/v1',
        'services.herd.token' => 'machine-token',
        'services.herd.consumer_app' => 'iam',
        'services.herd.client_id' => 'client-id',
        'services.herd.client_secret' => 'client-secret',
        'services.herd.redirect_uri' => 'https://app.test/auth/callback',
    ]);

    Http::preventStrayRequests();
});

/**
 * @param  array<string, mixed>  $claims
 */
function fakeHerdSso(array $claims): void
{
    Http::fake([
        'herd.test/oauth/token' => Http::response(['access_token' => 'user-token', 'token_type' => 'Bearer']),
        'herd.test/oauth/userinfo' => Http::response($claims),
    ]);
}

/**
 * @param  list<array<string, mixed>>  $users
 */
function fakeHerdGrantedUsers(array $users): void
{
    Http::fake([
        'herd.test/api/v1/applications/iam/users*' => Http::response([
            'application' => ['id' => 8, 'slug' => 'iam', 'isActive' => true],
            'data' => $users,
            'meta' => ['total' => count($users), 'page' => 1, 'perPage' => 15, 'lastPage' => 1],
        ]),
    ]);
}

it('redirects to the HERD authorize endpoint with state and an S256 PKCE challenge', function (): void {
    $response = get('/auth/redirect');

    $attempt = session('herd_oidc');
    parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);

    expect($response->headers->get('Location'))->toStartWith('https://herd.test/oauth/authorize?')
        ->and($query)->toMatchArray([
            'client_id' => 'client-id',
            'redirect_uri' => 'https://app.test/auth/callback',
            'response_type' => 'code',
            'state' => $attempt['state'],
            'code_challenge_method' => 'S256',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $attempt['verifier'], true)), '+/', '-_'), '='),
        ]);
});

it('signs in a user whose NIK matches a local employee with a role', function (): void {
    $user = User::factory()->technician()->create();
    fakeHerdSso(['sub' => '77', 'nik' => $user->employee->employee_number, 'name' => 'Nama HERD', 'upn' => 'nama@herd.test']);

    withSession(['herd_oidc' => ['state' => 'abc', 'verifier' => 'verifier']])
        ->get('/auth/callback?code=xyz&state=abc')
        ->assertRedirect('/dashboard');

    assertAuthenticatedAs($user);
    expect(session('herd_identity.nik'))->toBe($user->employee->employee_number)
        ->and($user->fresh()->last_login_at)->not->toBeNull();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://herd.test/oauth/token'
        && $request['code_verifier'] === 'verifier'
        && $request['client_secret'] === 'client-secret');
});

it('recovers the NIK from the application grant when HERD omits the nik claim', function (): void {
    $user = User::factory()->technician()->create();
    fakeHerdSso(['sub' => '60', 'name' => 'QASSAM', 'upn' => 'Qassam@Herd.test', 'email' => 'qassam@herd.test']);
    fakeHerdGrantedUsers([[
        'id' => 60, 'name' => 'QASSAM', 'email' => 'qassam@herd.test', 'upn' => 'qassam@herd.test',
        'employeeNik' => $user->employee->employee_number, 'department' => null, 'jobTitle' => null,
        'nikFunctional' => null, 'positions' => [], 'isActive' => true, 'grantedAt' => null,
    ]]);

    withSession(['herd_oidc' => ['state' => 'abc', 'verifier' => 'verifier']])
        ->get('/auth/callback?code=xyz&state=abc')
        ->assertRedirect('/dashboard');

    assertAuthenticatedAs($user);
    expect(session('herd_identity.nik'))->toBe($user->employee->employee_number);
});

it('still refuses a profile without nik when no grant matches its UPN or email', function (): void {
    fakeHerdSso(['sub' => '61', 'name' => 'Tanpa Grant', 'upn' => 'tanpa@herd.test', 'email' => 'tanpa@herd.test']);
    fakeHerdGrantedUsers([]);

    withSession(['herd_oidc' => ['state' => 'abc', 'verifier' => 'verifier']])
        ->get('/auth/callback?code=xyz&state=abc')
        ->assertRedirect('/login')
        ->assertSessionHas('error', 'Profil HERD tidak memuat NIK. Hubungi tim IT.');

    assertGuest();
});

it('rejects a callback whose state does not match the session', function (): void {
    withSession(['herd_oidc' => ['state' => 'abc', 'verifier' => 'verifier']])
        ->get('/auth/callback?code=xyz&state=forged')
        ->assertRedirect('/login')
        ->assertSessionHas('error');

    assertGuest();
    Http::assertNothingSent();
});

it('explains when HERD denies authorization because the employee has no grant', function (): void {
    withSession(['herd_oidc' => ['state' => 'abc', 'verifier' => 'verifier']])
        ->get('/auth/callback?error=access_denied&state=abc')
        ->assertRedirect('/login')
        ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'ditolak HERD'));

    assertGuest();
});

it('reports a rejected code exchange', function (): void {
    Http::fake(['herd.test/oauth/token' => Http::response(['error' => 'invalid_grant'], 400)]);

    withSession(['herd_oidc' => ['state' => 'abc', 'verifier' => 'verifier']])
        ->get('/auth/callback?code=xyz&state=abc')
        ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'PKCE'));

    assertGuest();
});

it('refuses SSO users who have no local role yet', function (): void {
    fakeHerdSso(['sub' => '77', 'nik' => 'NEW001', 'name' => 'Karyawan Baru', 'email' => 'baru@herd.test']);

    withSession(['herd_oidc' => ['state' => 'abc', 'verifier' => 'verifier']])
        ->get('/auth/callback?code=xyz&state=abc')
        ->assertRedirect('/login')
        ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'belum diberi peran'));

    assertGuest();
    expect(Employee::query()->where('employee_number', 'NEW001')->exists())->toBeTrue();
});

it('refuses SSO users whose employee record is inactive', function (): void {
    $user = User::factory()->create();
    $user->employee->update(['employment_status' => EmploymentStatus::Resigned]);
    fakeHerdSso(['sub' => '77', 'nik' => $user->employee->employee_number, 'name' => $user->employee->name]);

    withSession(['herd_oidc' => ['state' => 'abc', 'verifier' => 'verifier']])
        ->get('/auth/callback?code=xyz&state=abc')
        ->assertSessionHas('error');

    assertGuest();
});

it('shows HERD granted users with their local role to admins', function (): void {
    $admin = userWithRole(Role::Admin);
    fakeHerdGrantedUsers([
        ['id' => 1, 'name' => $admin->employee->name, 'employeeNik' => $admin->employee->employee_number, 'upn' => 'a@herd.test', 'jobTitle' => 'Manager', 'isActive' => true, 'grantedAt' => null],
        ['id' => 2, 'name' => 'Budi', 'employeeNik' => 'B001', 'upn' => 'budi@herd.test', 'jobTitle' => 'Staff', 'isActive' => true, 'grantedAt' => null],
    ]);

    actingAs($admin)
        ->get('/settings')
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Users')
            ->missing('herdUsers')
            ->loadDeferredProps(fn ($reload) => $reload
                ->has('herdUsers.data', 2)
                ->where('herdUsers.data.0.local_role_label', Role::Admin->label())
                ->where('herdUsers.data.1.local_role_label', null)
                ->where('herdUsers.data.1.job_title', 'Staff')));

    Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer machine-token'));
});

it('grants a local role to a HERD user and creates the employee from HERD data', function (): void {
    fakeHerdGrantedUsers([
        ['id' => 2, 'name' => 'Budi', 'employeeNik' => 'B001', 'upn' => 'budi@herd.test', 'jobTitle' => 'Staff', 'isActive' => true],
    ]);

    actingAs(userWithRole(Role::Admin))
        ->post('/settings/herd-users', ['nik' => 'B001', 'role' => 'technician'])
        ->assertSessionHas('success');

    $employee = Employee::query()->where('employee_number', 'B001')->sole();
    expect($employee->name)->toBe('Budi')
        ->and($employee->user->role)->toBe(Role::Technician);
});

it('refuses to grant a role to an NIK that has no HERD grant', function (): void {
    fakeHerdGrantedUsers([]);

    actingAs(userWithRole(Role::Admin))
        ->post('/settings/herd-users', ['nik' => 'X999', 'role' => 'viewer'])
        ->assertSessionHas('error');

    expect(Employee::query()->where('employee_number', 'X999')->exists())->toBeFalse();
});

it('only lets admins grant HERD users', function (): void {
    actingAs(userWithRole(Role::Technician))
        ->post('/settings/herd-users', ['nik' => 'B001', 'role' => 'viewer'])
        ->assertForbidden();
});

it('hides dev users outside local environments but keeps the SSO button', function (): void {
    User::factory()->create();
    app()->detectEnvironment(fn (): string => 'production');

    get('/login')->assertInertia(fn ($page) => $page
        ->component('Auth/DevLogin')
        ->has('users', 0)
        ->where('ssoEnabled', true));
});

it('syncs granted HERD employees, logs the run and flags offboarding', function (): void {
    $holder = Employee::factory()->create(['employee_number' => 'H001']);
    Asset::factory()->deployed()->create(['assigned_employee_id' => $holder->id]);
    fakeHerdGrantedUsers([
        ['id' => 1, 'name' => 'Pemegang', 'employeeNik' => 'H001', 'isActive' => false],
        ['id' => 2, 'name' => 'Baru', 'employeeNik' => 'N001', 'isActive' => true],
    ]);

    actingAs(userWithRole(Role::Admin))
        ->post('/settings/herd-sync')
        ->assertSessionHas('success');

    $run = HerdSyncRun::query()->sole();
    expect($run->status)->toBe('success')
        ->and([$run->created_count, $run->updated_count, $run->offboarding_count])->toBe([1, 1, 1])
        ->and($holder->fresh()->employment_status)->toBe(EmploymentStatus::Inactive)
        ->and($holder->alerts()->open()->where('type', AlertType::EmployeeOffboarding)->exists())->toBeTrue();
});

it('records a failed sync when HERD rejects the machine token', function (): void {
    Http::fake(['herd.test/api/v1/*' => Http::response(['message' => 'Unauthenticated.'], 401)]);

    actingAs(userWithRole(Role::Admin))
        ->post('/settings/herd-sync')
        ->assertSessionHas('error');

    expect(HerdSyncRun::query()->sole()->status)->toBe('failed');
});

it('signs in locally with NIK and password without contacting HERD', function (): void {
    $user = User::factory()->create(['password' => 'rahasia123']);

    post('/login', ['employee_number' => $user->employee->employee_number, 'password' => 'rahasia123'])
        ->assertRedirect('/dashboard');

    assertAuthenticatedAs($user);
    Http::assertNothingSent();
});

it('rejects wrong local passwords, users without one, and throttles repeated attempts', function (): void {
    $user = User::factory()->create(['password' => 'rahasia123']);
    $noPassword = User::factory()->create();
    $nik = $user->employee->employee_number;

    post('/login', ['employee_number' => $nik, 'password' => 'salah'])->assertSessionHasErrors('employee_number');
    post('/login', ['employee_number' => $noPassword->employee->employee_number, 'password' => 'apa saja'])->assertSessionHasErrors('employee_number');

    foreach (range(1, 4) as $attempt) {
        post('/login', ['employee_number' => $nik, 'password' => 'salah']);
    }

    post('/login', ['employee_number' => $nik, 'password' => 'rahasia123'])
        ->assertSessionHasErrors('employee_number');

    expect(session('errors')->first('employee_number'))->toStartWith('Terlalu banyak percobaan');

    assertGuest();
});

it('lets admins set and clear a local password', function (): void {
    $viewer = User::factory()->create();

    actingAs(userWithRole(Role::Admin))
        ->put("/settings/users/{$viewer->id}/password", ['password' => 'rahasia123', 'password_confirmation' => 'rahasia123'])
        ->assertSessionHas('success');
    expect($viewer->fresh()->hasLocalPassword())->toBeTrue();

    actingAs(userWithRole(Role::Admin))
        ->put("/settings/users/{$viewer->id}/password", ['password' => '', 'password_confirmation' => ''])
        ->assertSessionHas('success');
    expect($viewer->fresh()->hasLocalPassword())->toBeFalse();
});

it('bootstraps an admin with a local password from the console', function (): void {
    $employee = Employee::factory()->create(['employee_number' => 'ADM01']);

    artisan('users:set-password', ['nik' => 'ADM01', '--role' => 'admin'])
        ->expectsQuestion('Password baru (min. 8 karakter)', 'rahasia123')
        ->assertSuccessful();

    expect($employee->user->role)->toBe(Role::Admin)
        ->and($employee->user->hasLocalPassword())->toBeTrue();
});
