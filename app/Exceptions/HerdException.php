<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Thrown when HERD (SSO or machine API) refuses a request or cannot be reached.
 */
class HerdException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Integrasi HERD belum dikonfigurasi. Hubungi tim IT.');
    }

    public static function stateMismatch(): self
    {
        return new self('Sesi login tidak valid atau sudah kedaluwarsa. Silakan coba masuk lagi.');
    }

    public static function authorizationDenied(?string $description): self
    {
        return new self('Akses ke aplikasi ini ditolak HERD'.($description ? ": {$description}" : '.').' Pastikan akun Anda sudah di-grant ke IT Asset Management.');
    }

    public static function tokenExchangeFailed(Response $response): self
    {
        $error = $response->json('error');

        return new self(match ($error) {
            'invalid_client' => 'Kredensial aplikasi ke HERD salah. Hubungi tim IT.',
            'invalid_grant' => 'Kode login dari HERD tidak valid atau sudah dipakai (PKCE gagal). Silakan coba masuk lagi.',
            default => "Gagal menukar kode login dengan HERD (HTTP {$response->status()}).",
        });
    }

    public static function requestFailed(Response $response): self
    {
        return new self(match ($response->status()) {
            401 => 'Token HERD tidak valid atau sudah kedaluwarsa.',
            404 => 'Data tidak ditemukan di HERD.',
            422 => 'Permintaan ke HERD tidak valid.',
            429 => 'Terlalu banyak permintaan ke HERD. Coba lagi sebentar.',
            default => "HERD merespons dengan HTTP {$response->status()}.",
        });
    }

    public static function unreachable(): self
    {
        return new self('HERD tidak dapat dihubungi. Coba lagi beberapa saat.');
    }
}
