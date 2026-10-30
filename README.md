# IT Asset Manager

Aplikasi manajemen aset IT internal: hardware, lisensi software, komponen, aksesori, dan
maintenance. Data karyawan & SSO berasal dari **HERD**, dengan login lokal sebagai cadangan.

Proyek ini adalah penulisan ulang konsep Snipe-IT dengan cakupan yang lebih kecil dan alur
bisnis yang tegas: Laravel 13 + Inertia + React (TypeScript) + PostgreSQL.

## Stack

| Lapisan | Teknologi |
|---|---|
| Backend | Laravel 13, PHP 8.3+ (diuji PHP 8.5) |
| Frontend | React 19 + TypeScript strict, Inertia.js, Vite |
| UI | Tailwind CSS v4, shadcn/ui, TanStack Table, Lucide |
| Database | PostgreSQL 17 |
| Auth | Sesi; SSO HERD (OIDC + PKCE) atau login lokal NIK + password |
| Queue | Database driver + scheduler |
| Tes | Pest 5 (backend), Vitest (frontend) |
| Kualitas | Pint, Larastan level 6, ESLint, Prettier |

## Instalasi (lokal)

```bash
composer install
cp .env.example .env
php artisan key:generate

# Buat database
createdb -h 127.0.0.1 -p 5433 -U postgres it_asset_manager
createdb -h 127.0.0.1 -p 5433 -U postgres it_asset_manager_test   # untuk tes

php artisan migrate --seed
npm install
npm run build      # atau: npm run dev
php artisan serve
```

Buka <http://localhost:8000>.

> Sail/Docker: `docker/` belum disiapkan karena pengembangan berjalan di PostgreSQL lokal.
> Konfigurasi Sail dapat ditambahkan tanpa mengubah kode aplikasi.

## Perintah penting

```bash
php artisan test                 # Pest
vendor/bin/pint                  # format PHP
vendor/bin/phpstan analyse       # analisis statis (level 6)
npm run lint                     # ESLint
npm run types                    # tsc --noEmit
npm run build                    # build produksi
php artisan migrate:fresh --seed # reset database + data demo
php artisan herd:sync-employees # sinkron karyawan dari HERD (terjadwal 02:00)
php artisan users:set-password NIK --role=admin # password lokal / admin pertama
```

## Akun demo

| Nama | NIK | Role |
|---|---|---|
| Budi Santoso | NIK24001 | admin |
| Rina Kartika | NIK24002 | technician |
| Dewi Lestari | NIK24004 | viewer |

Di `APP_ENV=local` halaman masuk juga menampilkan pemilih pengguna demo (`/dev-login`).

## Login

Ada dua jalur masuk; keduanya memakai peran yang sama dari tabel `users`.

1. **SSO HERD** — tombol *Masuk dengan HERD*. Authorization Code + PKCE ke HERD, lalu
   `userinfo` dicocokkan ke karyawan lewat NIK (cadangan: email). Karyawan harus di-grant
   ke aplikasi `iam` di HERD **dan** punya peran di *Pengaturan → Pengguna*.
2. **Login lokal** — NIK + password yang disimpan di database aplikasi ini. Dipakai saat HERD
   tidak bisa dihubungi. Admin mengatur password di *Pengaturan → Pengguna → Atur password*;
   percobaan dibatasi 5 kali per NIK + IP.

Admin pertama dibuat dari konsol: `php artisan users:set-password NIK24001 --role=admin`.

### Konfigurasi HERD (`.env`)

| Variabel | Isi |
|---|---|
| `HERD_ORIGIN` | `https://stg-herd.agroveta.co.id` |
| `HERD_API_BASE` | `https://stg-herd.agroveta.co.id/api/v1` |
| `HERD_TOKEN` | Sanctum machine token (dari tim IT) — untuk daftar user & sync |
| `CONSUMER_APP` | `iam` |
| `OAUTH_CLIENT_ID` / `OAUTH_CLIENT_SECRET` | Kredensial OAuth aplikasi (secret dari tim IT) |
| `OAUTH_REDIRECT_URI` | Harus **persis** sama dengan yang terdaftar di HERD |

Secret dan token hanya disimpan di `.env`, jangan di-commit.

## Struktur

```
app/
  Actions/          logika bisnis (satu kelas satu aksi)
  Enums/            AssetStatus, MaintenanceStatus, LicenseType, ...
  Http/Controllers/ tipis: validasi -> action -> Inertia::render
  Http/Requests/    seluruh validasi input
  Models/           satu model per tabel
  Policies/         hak akses per model
  Services/Herd/    HerdClient + provider Socialite
resources/js/
  pages/            dashboard, assets, licenses, inventory, maintenance, directory, reports
  components/       komponen bersama + shadcn/ui
  layouts/          AppLayout
docs/
  schema.sql        rancangan database (acuan migration)
  PLAN.md           rencana per fase
```

## Aturan bisnis inti

1. Status aset: `pending → ready → deployed → ready|maintenance → retired`. Transisi divalidasi
   `AssetStatus::canTransitionTo()`.
2. Status hanya berubah lewat Action, dan setiap perubahan menulis `asset_logs` dalam transaksi.
3. Karyawan & departemen read-only (sumber HERD).
4. Stok komponen/aksesori dihitung dari view, bukan kolom manual.
5. Seat lisensi tidak boleh melebihi `seats_total` (dicek dengan `lockForUpdate`).
6. Uang disimpan `numeric(15,2)` (Rupiah), tanpa float.
7. Soft delete untuk aset, lisensi, komponen, aksesori, supplier, model.

## Status pengerjaan

- [x] Fase 0 — fondasi (Laravel 13, Inertia React, Tailwind, PostgreSQL, tooling)
- [x] Fase 1 — skema, enum, model, factory, seeder demo
- [x] Fase 2 — otorisasi & komponen UI bersama
- [x] Fase 3 — modul aset (checkout/checkin, riwayat, QR)
- [x] Fase 4 — komponen & aksesori
- [x] Fase 5 — lisensi
- [x] Fase 6 — maintenance
- [x] Fase 7 — dashboard & alert
- [x] Fase 8 — direktori, laporan, ekspor
- [x] Fase 9 — HERD: SSO OIDC, login lokal, akses pengguna, sync karyawan
- [x] Fase 10 — pengerasan (tes, lint, build, dokumentasi)
