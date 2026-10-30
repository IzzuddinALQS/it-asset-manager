<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Herd\SyncEmployeeFromHerd;
use App\Exceptions\HerdException;
use App\Http\Controllers\Controller;
use App\Services\Herd\HerdApiClient;
use App\Services\Herd\HerdOidcClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Single sign-on through HERD (OIDC Authorization Code + PKCE). Roles stay local in the users table.
 */
class HerdLoginController extends Controller
{
    public function __construct(private HerdOidcClient $oidc) {}

    public function redirect(Request $request): RedirectResponse
    {
        if (! $this->oidc->isConfigured()) {
            return $this->fail(HerdException::notConfigured());
        }

        $attempt = $this->oidc->newAttempt();
        $request->session()->put('herd_oidc', ['state' => $attempt['state'], 'verifier' => $attempt['verifier']]);

        return redirect()->away($this->oidc->authorizationUrl($attempt['state'], $attempt['challenge']));
    }

    public function callback(Request $request, SyncEmployeeFromHerd $syncEmployee, HerdApiClient $herdApi): RedirectResponse
    {
        /** @var array{state: string, verifier: string}|null $attempt */
        $attempt = $request->session()->pull('herd_oidc');

        try {
            if ($request->filled('error')) {
                throw HerdException::authorizationDenied($request->string('error_description')->toString() ?: null);
            }

            if ($attempt === null || ! is_string($request->query('state')) || ! hash_equals($attempt['state'], $request->query('state')) || ! $request->filled('code')) {
                throw HerdException::stateMismatch();
            }

            $tokens = $this->oidc->exchangeCode($request->string('code')->toString(), $attempt['verifier']);
            $claims = $this->oidc->userInfo($tokens['access_token']);
        } catch (HerdException $exception) {
            return $this->fail($exception);
        }

        if (filled($claims['sub'] ?? null) && blank($claims['nik'] ?? null)) {
            $claims = $this->withNikFromGrant($claims, $herdApi);
        }

        if (blank($claims['sub'] ?? null) || blank($claims['nik'] ?? null)) {
            Log::warning('HERD userinfo tanpa sub/nik', ['claims' => array_keys($claims)]);

            return $this->fail(new HerdException('Profil HERD tidak memuat NIK. Hubungi tim IT.'));
        }

        $employee = $syncEmployee->handle(SyncEmployeeFromHerd::fromUserInfo($claims));
        $user = $employee->user;

        if ($user === null) {
            return $this->fail(new HerdException("Akun {$employee->name} sudah lolos SSO HERD tetapi belum diberi peran di aplikasi ini. Hubungi Administrator IT."));
        }

        if (! $employee->isActive()) {
            return $this->fail(new HerdException("Akun {$employee->name} tidak aktif dan tidak bisa masuk."));
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('herd_identity', [
            'nik' => $claims['nik'],
            'upn' => $claims['upn'] ?? null,
            'name' => $claims['name'] ?? $employee->name,
        ]);

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    private function fail(HerdException $exception): RedirectResponse
    {
        return redirect()->route('login')->with('error', $exception->getMessage());
    }

    /**
     * HERD omits the `nik` claim for some accounts (e.g. local accounts); recover it from
     * the application grant by UPN/email, which also confirms the user is granted.
     *
     * @param  array<string, mixed>  $claims
     * @return array<string, mixed>
     */
    private function withNikFromGrant(array $claims, HerdApiClient $herdApi): array
    {
        if (! $herdApi->isConfigured()) {
            return $claims;
        }

        try {
            $grant = $herdApi->grantedUserByIdentity($claims['upn'] ?? null, $claims['email'] ?? null);
        } catch (HerdException $exception) {
            Log::warning('Gagal mencari NIK dari grant HERD', ['message' => $exception->getMessage()]);

            return $claims;
        }

        if ($grant === null || blank($grant['employeeNik'] ?? null)) {
            return $claims;
        }

        return array_merge($claims, [
            'nik' => $grant['employeeNik'],
            'job_title' => $claims['job_title'] ?? $grant['jobTitle'] ?? null,
            'department' => $claims['department'] ?? $grant['department'] ?? null,
        ]);
    }
}
