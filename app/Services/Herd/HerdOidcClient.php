<?php

namespace App\Services\Herd;

use App\Exceptions\HerdException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * HERD as OIDC identity provider: Authorization Code + PKCE (S256) for signing users in.
 */
class HerdOidcClient
{
    public function isConfigured(): bool
    {
        return filled(config('services.herd.origin'))
            && filled(config('services.herd.client_id'))
            && filled(config('services.herd.client_secret'))
            && filled(config('services.herd.redirect_uri'));
    }

    /**
     * Fresh state and PKCE pair for one login attempt.
     *
     * @return array{state: string, verifier: string, challenge: string}
     */
    public function newAttempt(): array
    {
        $verifier = Str::random(64);

        return [
            'state' => Str::random(40),
            'verifier' => $verifier,
            'challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
        ];
    }

    public function authorizationUrl(string $state, string $challenge): string
    {
        $this->ensureConfigured();

        return config('services.herd.origin').'/oauth/authorize?'.http_build_query([
            'client_id' => config('services.herd.client_id'),
            'redirect_uri' => config('services.herd.redirect_uri'),
            'response_type' => 'code',
            'scope' => config('services.herd.scopes'),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], arg_separator: '&', encoding_type: PHP_QUERY_RFC3986);
    }

    /**
     * @return array{access_token: string, refresh_token?: string, id_token?: string, expires_in?: int}
     */
    public function exchangeCode(string $code, string $verifier): array
    {
        $this->ensureConfigured();

        $response = $this->send(fn () => Http::asForm()->acceptJson()->timeout(15)
            ->post(config('services.herd.origin').'/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.herd.client_id'),
                'client_secret' => config('services.herd.client_secret'),
                'redirect_uri' => config('services.herd.redirect_uri'),
                'code' => $code,
                'code_verifier' => $verifier,
            ]));

        if ($response->failed() || ! is_string($response->json('access_token'))) {
            throw HerdException::tokenExchangeFailed($response);
        }

        return $response->json();
    }

    /**
     * Claims of the signed-in user (sub, nik, upn, email, name, job_title, department).
     *
     * @return array<string, mixed>
     */
    public function userInfo(string $accessToken): array
    {
        $response = $this->send(fn () => Http::withToken($accessToken)->acceptJson()->timeout(15)
            ->get(config('services.herd.origin').'/oauth/userinfo'));

        if ($response->failed()) {
            throw HerdException::requestFailed($response);
        }

        return $response->json();
    }

    private function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw HerdException::notConfigured();
        }
    }

    /**
     * @param  callable(): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            return $request();
        } catch (ConnectionException) {
            throw HerdException::unreachable();
        }
    }
}
