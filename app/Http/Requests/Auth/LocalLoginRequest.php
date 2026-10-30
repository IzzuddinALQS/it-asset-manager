<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Fallback sign-in with NIK + local password, rate limited per NIK and IP.
 */
class LocalLoginRequest extends FormRequest
{
    private const int MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:32'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_number.required' => 'Isi NIK.',
            'password.required' => 'Isi password.',
        ];
    }

    /**
     * The user these credentials belong to; throws a validation error otherwise.
     */
    public function authenticatedUser(): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->whereHas('employee', fn ($query) => $query->where('employee_number', $this->string('employee_number')->trim()->toString()))
            ->with('employee')
            ->first();

        if ($user === null || ! $user->hasLocalPassword() || ! Hash::check($this->string('password')->toString(), $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages(['employee_number' => 'NIK atau password salah.']);
        }

        if (! $user->employee->isActive()) {
            throw ValidationException::withMessages(['employee_number' => "Akun {$user->employee->name} tidak aktif dan tidak bisa masuk."]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages(['employee_number' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."]);
    }

    private function throttleKey(): string
    {
        return Str::lower($this->string('employee_number')->trim()->toString()).'|'.$this->ip();
    }
}
