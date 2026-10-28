# IT Asset Manager — Rencana Kerja

Dokumen ini adalah peta jalan pengerjaan. Prinsip: **satu fase = satu increment yang bisa
dijalankan dan diuji**, tidak ada fase yang dikerjakan setengah lalu ditinggal.

Stack: Laravel 13 + Inertia + React 19 (TypeScript strict) + Tailwind + shadcn/ui +
TanStack Table + PostgreSQL + Pest/Vitest. UI Bahasa Indonesia, kode Bahasa Inggris.

---

## Hasil studi Snipe-IT (v8.8.3) → apa yang diambil, apa yang dibuang

Snipe-IT: ~50 model, 70+ controller, 502 migrasi, Laravel 12 + Blade/Livewire, dukungan
MySQL/MariaDB/SQLite. Konsep inti yang dipakai ulang:

| Konsep Snipe-IT | Keputusan di proyek ini |
|---|---|
| Asset → AssetModel → Category/Manufacturer | Diambil, disederhanakan (tanpa custom fieldset) |
| Statuslabel bebas | Diganti **enum tetap** `pending/ready/deployed/maintenance/retired` |
| Checkout/checkin ke user **atau** lokasi | Diambil, dengan constraint "tepat satu pemegang" |
| Licenses + seats | Diambil (tanpa EULA/acceptance) |
| Components (dipasang ke aset) | Diambil + stok terhitung (bukan kolom manual) |
| Accessories (dipinjamkan ke user) | Diambil + stok terhitung |
| Consumables | **Dibuang** — tidak ada di skema |
| Maintenance + maintenance type | Diganti tiket + preventive plan sesuai skema |
| Depreciation | **Dibuang** (hanya `depreciation_months` sebagai metadata) |
| Custom fields / fieldsets | **Dibuang** — kolom tetap sesuai skema |
| Companies / Departments / Groups ACL | **Dibuang** — role tunggal admin/technician/viewer |
| LDAP / SAML / SCIM / Socialite generik | Ditunda; SSO HERD menyusul |
| Slack/Teams/webhook, EULA, signature, audit fisik, import wizard, labels designer | **Dibuang** |

Fitur baru dibanding Snipe-IT: alur status aset yang divalidasi enum, alert otomatis
(offboarding, garansi, lisensi, stok, maintenance), kanban maintenance, QR label aset,
riwayat append-only `asset_logs`, dan sinkronisasi karyawan HERD.

---

## Fase 0 — Fondasi proyek (½ hari)

**Keluaran**
- Laravel 13 terpasang, `.env` PostgreSQL (`it_asset_manager`), timezone `Asia/Jakarta`, locale `id`.
- Inertia + React + TS strict + Vite + Tailwind v4 + alias `@/`.
- shadcn/ui base (button, card, table, badge, input, select, dialog, dropdown, tabs, sonner).
- Tooling: Pint, Larastan level 6, Pest, ESLint, Prettier, Vitest; skrip `npm run lint|types|test`.
- Layout dasar: sidebar `#111827`, header, halaman placeholder.
- `docs/schema.sql` disalin dari spesifikasi.

**Selesai bila**: `php artisan serve` + `npm run dev` menampilkan halaman kosong berlayout,
`php artisan test` hijau, `npm run build` sukses.

---

## Fase 1 — Domain & database (1–1½ hari)

**Keluaran**
- 15 migrasi sesuai `docs/schema.sql`: departments, locations, employees, users, herd_sync_runs,
  suppliers, manufacturers, categories, asset_models, assets, asset_logs, components,
  component_installations, accessories, accessory_checkouts, licenses, license_seats,
  maintenance_plans, maintenances, alerts + 2 view stok.
- Enum: `AssetStatus` (+`canTransitionTo`), `MaintenanceStatus`, `MaintenanceType`,
  `LicenseType`, `EmploymentStatus`, `AlertType`, `AlertSeverity`, `Role`, `HandledBy`,
  `RetireReason`, `CategoryType`, `AssetAction`.
- Model + relasi + cast + scope (`available`, `deployed`, `open`), soft delete pada aset,
  lisensi, komponen, aksesori, supplier, model.
- Seeder demo: 3 lokasi, 6 departemen, 40 karyawan, 3 user (admin/technician/viewer),
  25 model aset, 60 aset lintas status, 8 lisensi + seat, 5 komponen, 5 aksesori,
  6 maintenance, alert contoh.
- Factory untuk semua model yang dipakai tes.

**Selesai bila**: `migrate:fresh --seed` bersih di PostgreSQL, `php artisan db:show` benar,
tes model/relasi hijau.

---

## Fase 2 — Kerangka aplikasi & otorisasi (½ hari)

**Catatan**: halaman login & SSO HERD **ditunda** sesuai instruksi. Untuk pengembangan dipakai
"dev login" (pilih user dari daftar) yang hanya aktif bila `APP_ENV=local`; nanti diganti SSO
tanpa mengubah Policy/controller.

**Keluaran**
- `HandleInertiaRequests` membagikan auth user, flash, nav.
- Middleware `EnsureRole`, Policy per model (view/viewAny/create/update/delete/retire/checkout).
- Dev login + logout, redirect dari `/` ke dashboard.
- Layout aplikasi final (sidebar, breadcrumb, page header, toast, dialog konfirmasi).
- Komponen bersama: `StatusBadge`, `PageHeader`, `EmptyState`, `DataTable` (TanStack),
  `Pagination`, `SearchInput`, `CurrencyInput`, `DateDisplay`, `ConfirmDialog`.

**Selesai bila**: 3 role menghasilkan menu & aksi berbeda; tes Policy hijau.

---

## Fase 3 — Aset: inti bisnis (2–3 hari)

**Keluaran**
- Halaman: daftar aset (filter status/kategori/lokasi, pencarian, urut, paginasi),
  detail aset (ringkasan, pemegang, riwayat, komponen terpasang, lisensi, maintenance),
  tambah/edit aset, form checkout/checkin/retire, cetak label QR.
- Action + tes: `CreateAsset`, `UpdateAsset`, `TagAsset`, `CheckoutAsset`, `CheckinAsset`,
  `RetireAsset` — semuanya menulis `asset_logs` dalam transaksi.
- Validasi transisi lewat `AssetStatus::canTransitionTo()`; checkout hanya untuk `ready`,
  checkin mengembalikan `ready` atau `maintenance`.
- QR: `simplesoftwareio/simple-qrcode` (atau bawaan `endroid`)? → **pakai SVG QR dari
  `bacon/bacon-qr-code`** agar tanpa ekstensi tambahan; halaman label siap cetak A4.
- API internal (JSON) untuk scan halaman: `GET /api/assets/by-tag/{tag}`.

**Selesai bila**: siklus penuh pending→ready→deployed→ready/retired berjalan di UI dan tiap
langkah muncul di riwayat; tes action (sukses + transisi ditolak) hijau.

---

## Fase 4 — Komponen & aksesori (1–1½ hari)

**Keluaran**
- Daftar stok komponen (qty total, terpasang, tersedia, minimum) + form tambah/edit.
- Pasang/lepas komponen ke aset (qty, `is_factory`, catatan), stok dihitung dari view.
- Daftar aksesori + checkout/checkin ke karyawan (qty, partial return), stok dari view.
- Peringatan `low_stock` otomatis saat stok ≤ minimum.

**Selesai bila**: pasang 2 RAM → stok turun 2; lepas → kembali; checkout aksesori melebihi
stok ditolak.

---

## Fase 5 — Lisensi software (1 hari)

**Keluaran**
- Daftar lisensi (kartu/meja) dengan meteran seat, expiry, biaya tahunan.
- CRUD lisensi; `product_key` terenkripsi (cast `encrypted`), ditampilkan tersamar.
- Assign/release seat ke karyawan **atau** aset; batas `seats_total` dicek di Action dengan
  `lockForUpdate`.

**Selesai bila**: seat ke-6 dari 5 ditolak; release mengembalikan seat; alert expiry muncul H-30.

---

## Fase 6 — Maintenance (1–1½ hari)

**Keluaran**
- Papan kanban: reported / in_progress / waiting_parts / done (+ cancelled tersembunyi).
- Buat tiket dari aset (repair/preventive/upgrade/calibration), prioritas, vendor/warranty.
- `SendToMaintenance` (status aset → maintenance, simpan status sebelumnya),
  `CompleteMaintenance` (kembalikan status), biaya aktual, komponen terpakai.
- Jadwal preventif (`maintenance_plans`) + penanda `maintenance_due`.

**Selesai bila**: aset deployed → maintenance → selesai kembali ke deployed/ready sesuai
pilihan; nomor tiket `MT-2026-0001` berurutan.

---

## Fase 7 — Dashboard & alert (1 hari)

**Keluaran**
- Dashboard: KPI (total aset, deployed, ready, maintenance, nilai perolehan), grafik
  distribusi status/kategori, lisensi segera habis, maintenance jatuh tempo, alert terbaru.
- Halaman alert: filter tipe/severity, resolve, tautan ke subjek.
- `GenerateAlertsJob` (scheduler harian): garansi H-30, lisensi H-30, overdue return,
  low stock, maintenance due, offboarding.

**Selesai bila**: job idempoten (unique index alert terbuka), dashboard akurat terhadap data seed.

---

## Fase 8 — Direktori, laporan, ekspor (1 hari)

**Keluaran**
- Direktori karyawan (read-only) + detail: aset, aksesori, lisensi yang dipegang.
- Laporan: aset per status/kategori/lokasi, nilai aset, lisensi & utilisasi, maintenance
  cost, stok. Filter tanggal, ekspor CSV/Excel (`maatwebsite/excel` hanya jika perlu —
  default CSV native).
- Halaman pengaturan: profil, manajemen user lokal (role), data master (kategori,
  manufacturer, supplier, lokasi, model).

**Selesai bila**: ekspor menghasilkan file yang sama dengan tampilan terfilter; tes laporan hijau.

---

## Fase 9 — HERD (ditunda, 1–2 hari)

- `HerdClient` + `SyncEmployeesFromHerd` (upsert NIK, catat `herd_sync_runs`, alert offboarding).
- Scheduler 02:00 + tombol "Sync sekarang".
- SSO Socialite custom driver + halaman login (setelah detail endpoint dikonfirmasi).
- Sampai saat itu: seeder memakai data karyawan sintetis; `HERD_FAKE=true` mengaktifkan
  sync palsu agar alur bisa diuji.

---

## Fase 10 — Pengerasan (1 hari, berulang)

- Tes: Pest action + feature per modul, Vitest komponen kunci.
- `pint --test`, `phpstan`, `npm run lint`, `npm run build`, `php artisan test` hijau.
- README (instalasi, peran, alur), screenshot, seed demo, dokumentasi keputusan.

---

## Definisi selesai per fase

1. Migrasi & model konsisten dengan `docs/schema.sql`.
2. Logika bisnis hanya di `app/Actions`, controller tipis, validasi di FormRequest.
3. Ada tes Pest untuk jalur sukses + jalur ditolak.
4. UI memakai komponen bersama, label Indonesia, warna status sesuai tabel.
5. `pint --test`, `phpstan`, `npm run lint`, `php artisan test` lolos.
