<?php

namespace App\Services\Herd;

use App\Exceptions\HerdException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * HERD machine API, authenticated with the Sanctum token (never a user's OIDC token).
 */
class HerdApiClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.herd.api_base')) && filled(config('services.herd.token'));
    }

    /**
     * Employees granted SSO access to this application, already enriched with org positions.
     *
     * @return array{
     *     data: list<array{id: int, name: string, email: ?string, upn: ?string, employeeNik: string, department: ?string, jobTitle: ?string, nikFunctional: ?string, positions: list<array<string, mixed>>, isActive: bool, grantedAt: ?string}>,
     *     meta: array{total: int, page: int, perPage: int, lastPage: int}
     * }
     */
    public function applicationUsers(?string $search = null, int $page = 1, int $perPage = 15, bool $activeOnly = false): array
    {
        if (! $this->isConfigured()) {
            throw HerdException::notConfigured();
        }

        $application = config('services.herd.consumer_app');

        try {
            $response = Http::withToken(config('services.herd.token'))
                ->acceptJson()
                ->timeout(15)
                ->get(config('services.herd.api_base')."/applications/{$application}/users", array_filter([
                    'search' => $search,
                    'active_only' => $activeOnly ? 1 : null,
                    'page' => $page,
                    'per_page' => $perPage,
                ], fn (mixed $value): bool => $value !== null && $value !== ''));
        } catch (ConnectionException) {
            throw HerdException::unreachable();
        }

        if ($response->failed()) {
            throw HerdException::requestFailed($response);
        }

        return [
            'data' => $response->json('data', []),
            'meta' => $response->json('meta', ['total' => 0, 'page' => $page, 'perPage' => $perPage, 'lastPage' => 1]),
        ];
    }

    /**
     * A granted user found by exact NIK, or null when the employee has no grant.
     *
     * @return array<string, mixed>|null
     */
    public function grantedUserByNik(string $nik): ?array
    {
        return collect($this->applicationUsers($nik, perPage: 100)['data'])
            ->first(fn (array $user): bool => $user['employeeNik'] === $nik);
    }

    /**
     * A granted user found by exact UPN or email, for SSO profiles that carry no NIK claim
     * (e.g. HERD local accounts).
     *
     * @return array<string, mixed>|null
     */
    public function grantedUserByIdentity(?string $upn, ?string $email): ?array
    {
        $identities = collect([$upn, $email])->filter()->map(fn (string $value): string => mb_strtolower($value))->unique();

        foreach ($identities as $identity) {
            $match = collect($this->applicationUsers($identity, perPage: 100)['data'])->first(
                fn (array $user): bool => in_array($identity, [mb_strtolower((string) ($user['upn'] ?? '')), mb_strtolower((string) ($user['email'] ?? ''))], true),
            );

            if ($match !== null) {
                return $match;
            }
        }

        return null;
    }
}
