# Proses Bisnis IT Asset Manager (BPMN 2.0)

Dokumen ini memodelkan proses bisnis aplikasi IT Asset Manager memakai notasi **BPMN 2.0
(Business Process Model and Notation)**. Cakupannya siklus hidup aset IT dari pengadaan sampai
pensiun, ditambah proses pendukung: stok komponen dan aksesori, lisensi, maintenance, offboarding
karyawan, akses dan login, peringatan otomatis, serta pelaporan.

---

## 1. Notasi yang Dipakai

Diagram ditulis dengan Mermaid. Mermaid tidak punya diagram BPMN asli, jadi elemen BPMN
digambar dengan bentuk berikut:

| Elemen BPMN | Bentuk di diagram | Arti |
|---|---|---|
| **Start Event** | `(( ))` lingkaran tipis, hijau | Awal proses |
| **Intermediate / Timer Event** | `(( ))` lingkaran, kuning | Kejadian di tengah proses, mis. jadwal atau tunggu waktu |
| **End Event** | `((( )))` lingkaran tebal, merah | Akhir proses |
| **Task** | `( )` persegi sudut tumpul | Aktivitas yang dikerjakan aktor |
| **Service Task** | Task berlabel ⚙ | Aktivitas yang dikerjakan sistem secara otomatis |
| **Exclusive Gateway (XOR)** | `{ }` belah ketupat berlabel ✕ | Hanya satu jalur yang diambil |
| **Parallel Gateway (AND)** | `{ }` belah ketupat berlabel ＋ | Semua jalur dijalankan |
| **Data Object / Store** | `[( )]` silinder | Data yang dibaca/ditulis (database, log) |
| **Pool / Lane** | `subgraph` | Peserta proses atau pembagian peran |
| **Sequence Flow** | `-->` garis utuh | Urutan dalam satu pool |
| **Message Flow** | `-.->` garis putus-putus | Pesan antar pool (mis. aplikasi ↔ HERD) |

### Peserta (Pool & Lane)

| Peserta | Peran |
|---|---|
| **Administrator** | Pengadaan, ubah data aset, pensiun aset, kelola pengguna dan master data |
| **Teknisi IT** | Operasional harian: pelabelan, serah terima, pengembalian, maintenance, stok |
| **Karyawan** | Pemegang/pengguna aset, aksesori, dan seat lisensi |
| **Sistem (IT Asset Manager)** | Validasi, penomoran otomatis, pencatatan riwayat, alert, laporan |
| **HERD** | Sistem eksternal: SSO (OIDC), data karyawan, dan grant akses aplikasi |
| **Supplier / Vendor** | Pengirim barang dan penyedia jasa perbaikan |

---

## 2. Gambaran Umum (Landscape Process)

```mermaid
flowchart LR
    S((Kebutuhan<br/>aset IT)):::start --> P1(Pengadaan) --> P2(Pelabelan QR) --> P3(Serah terima)
    P3 --> G1{✕ Kondisi<br/>aset?}:::gw
    G1 -- Dikembalikan baik --> P4(Pengembalian) --> P2b(Kembali ke stok<br/>Siap Guna) --> P3
    G1 -- Rusak / jadwal preventif --> P5(Maintenance)
    P5 --> G2{✕ Layak<br/>pakai?}:::gw
    G2 -- Ya --> P3
    G2 -- Tidak --> P6(Pensiun)
    G1 -- Habis masa pakai / hilang --> P6
    P6 --> E(((Aset<br/>pensiun))):::endEv

    classDef start fill:#dcfce7,stroke:#16a34a,stroke-width:1px
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

### Status Aset

| Status | Label | Arti |
|---|---|---|
| `pending` | Menunggu | Tercatat dari pengadaan, belum berlabel |
| `ready` | Siap Guna | Berlabel, ada di stok IT, siap diberikan |
| `deployed` | Digunakan | Dipegang karyawan atau ditempatkan di lokasi |
| `maintenance` | Perbaikan | Sedang dalam tiket maintenance |
| `retired` | Pensiun | Tidak dipakai lagi (status akhir) |

```mermaid
stateDiagram-v2
    [*] --> Menunggu: Pengadaan dicatat
    Menunggu --> SiapGuna: Pelabelan
    SiapGuna --> Digunakan: Serah terima
    SiapGuna --> Perbaikan: Tiket maintenance
    SiapGuna --> Pensiun
    Digunakan --> SiapGuna: Pengembalian
    Digunakan --> Perbaikan: Check-in rusak / tiket
    Digunakan --> Pensiun
    Perbaikan --> SiapGuna: Selesai
    Perbaikan --> Digunakan: Selesai, kembali ke pemegang
    Perbaikan --> Pensiun
    Pensiun --> [*]
```

Sistem menolak transisi yang tidak ada di diagram ini.

---

## 3. Proses Pengadaan dan Pelabelan

**Pemicu:** barang dari Purchase Order (PO) diterima. **Hasil:** aset berstatus *Siap Guna*
dengan tag permanen dan label QR.

```mermaid
flowchart LR
    subgraph SUP[Pool: Supplier]
        direction LR
        SU1(Kirim barang<br/>+ dokumen PO)
    end

    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph ADM[Lane: Administrator]
            direction LR
            S((Barang<br/>diterima)):::start --> G1{✕ Jumlah<br/>banyak?}:::gw
            G1 -- Tidak --> A1(Input aset:<br/>model, serial, PO,<br/>nilai perolehan, garansi)
            G1 -- Ya --> A2(Impor file CSV)
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            B1(⚙ Simpan aset<br/>status Menunggu<br/>tag sementara TMP-xxxx)
            B2(⚙ Buat tag permanen<br/>PREFIX-00001)
            B3(⚙ Status → Siap Guna)
            D1[(Riwayat aset)]
        end
        subgraph TEK[Lane: Teknisi IT]
            direction LR
            T1(Pilih aset<br/>Menunggu) --> T2(Cetak & tempel<br/>label QR)
            T2 --> E(((Aset siap<br/>diberikan))):::endEv
        end
    end

    SU1 -. barang & PO .-> S
    A1 --> B1
    A2 --> B1
    B1 --> T1
    T1 --> B2 --> B3 --> T2
    B1 -. catat: Inventaris dicatat .-> D1
    B3 -. catat: Ditandai siap guna .-> D1

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

| Langkah | Aturan bisnis |
|---|---|
| Input aset | Hanya Administrator. Nomor PO dan nilai perolehan dicatat untuk laporan nilai aset. |
| Tag permanen | Format `PREFIX-NNNNN`, prefix dari kategori (default `AS`), nomor berurutan. |
| Pelabelan | Hanya aset *Menunggu*. Label QR bisa dicetak massal. |

---

## 4. Proses Serah Terima (Checkout)

**Pemicu:** permintaan aset dari karyawan atau kebutuhan lokasi. **Hasil:** aset *Digunakan*.

```mermaid
flowchart LR
    subgraph KRY[Pool: Karyawan]
        K1(Ajukan kebutuhan<br/>perangkat)
        K2(Terima perangkat)
    end

    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph TEK[Lane: Teknisi IT]
            direction LR
            S((Permintaan<br/>diterima)):::start --> T1(Pilih aset<br/>Siap Guna)
            T1 --> G1{✕ Penerima?}:::gw
            G1 -- Karyawan --> T2(Pilih karyawan)
            G1 -- Lokasi --> T3(Pilih lokasi)
            T2 --> T4(Isi tanggal rencana<br/>kembali — opsional)
            T3 --> T4
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            G2{✕ Valid?}:::gw
            B1(⚙ Status → Digunakan<br/>catat pemegang &<br/>lokasi fisik)
            X1(⚙ Tolak & tampilkan<br/>alasan)
            E1(((Aset<br/>diserahkan))):::endEv
            E2(((Ditolak))):::endEv
            D1[(Riwayat aset)]
        end
    end

    K1 -. permintaan .-> S
    T4 --> G2
    G2 -- Ya --> B1 --> E1
    G2 -- Tidak --> X1 --> E2
    B1 -. perangkat .-> K2
    B1 -. catat: Diberikan .-> D1

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

**Gateway "Valid?"**: ditolak jika aset bukan *Siap Guna*, penerima tidak diisi, atau karyawan
tidak berstatus *Aktif*. Jika tanggal rencana kembali terlewati, sistem membuat alert
*Pengembalian terlambat* (lihat §10).

---

## 5. Proses Pengembalian (Check-in)

```mermaid
flowchart LR
    subgraph KRY[Pool: Karyawan]
        K1(Kembalikan perangkat)
    end
    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph TEK[Lane: Teknisi IT]
            direction LR
            S((Perangkat<br/>diterima)):::start --> T1(Periksa kondisi)
            T1 --> G1{✕ Kondisi?}:::gw
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            B1(⚙ Status → Siap Guna<br/>kosongkan pemegang)
            B2(⚙ Status → Perbaikan<br/>kosongkan pemegang)
            E1(((Kembali<br/>ke stok))):::endEv
            E2(((Lanjut ke proses<br/>Maintenance))):::endEv
            D1[(Riwayat aset)]
        end
    end
    K1 -. perangkat .-> S
    G1 -- Baik --> B1 --> E1
    G1 -- Rusak --> B2 --> E2
    B1 -. catat: Dikembalikan .-> D1
    B2 -. catat: Dikembalikan .-> D1

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

Hanya aset *Digunakan* yang bisa di-check-in. Riwayat tetap menyimpan siapa pemegang sebelumnya.

---

## 6. Proses Maintenance

### 6.1 Maintenance Korektif (Tiket Perbaikan)

```mermaid
flowchart LR
    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph TEK[Lane: Teknisi IT]
            direction LR
            S((Kerusakan<br/>dilaporkan)):::start --> T1(Buat tiket:<br/>jenis, prioritas,<br/>deskripsi, estimasi biaya)
            T1 --> G1{✕ Ditangani<br/>oleh?}:::gw
            G1 -- Internal IT --> T2(Kerjakan perbaikan)
            G1 -- Vendor / Garansi --> T3(Kirim ke vendor)
            T2 --> G2{✕ Butuh<br/>sparepart?}:::gw
            G2 -- Ya --> W((⏳ Menunggu<br/>sparepart)):::inter --> T2
            G2 -- Tidak --> G3{✕ Hasil?}:::gw
            T3 --> G3
            G3 -- Berhasil --> T4(Tutup tiket:<br/>biaya aktual,<br/>komponen dipasang)
            G3 -- Batal --> T5(Batalkan tiket)
            G3 -- Tidak layak --> T6(Ajukan pensiun)
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            B1(⚙ Nomor tiket otomatis<br/>status aset → Perbaikan)
            B2(⚙ Kurangi stok<br/>komponen)
            G4{✕ Pemegang<br/>sebelumnya aktif?}:::gw
            B3(⚙ Status → Digunakan)
            B4(⚙ Status → Siap Guna)
            E1(((Tiket<br/>selesai))):::endEv
            E2(((Lanjut ke<br/>proses Pensiun))):::endEv
        end
    end
    subgraph VEN[Pool: Vendor]
        V1(Perbaiki / klaim garansi)
    end

    T1 --> B1
    T3 -. perangkat .-> V1
    V1 -. hasil perbaikan .-> G3
    T4 --> B2 --> G4
    T5 --> G4
    G4 -- Ya --> B3 --> E1
    G4 -- Tidak --> B4 --> E1
    T6 --> E2

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef inter fill:#fef3c7,stroke:#d97706,stroke-width:2px
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

Status tiket: **Dilaporkan → Dikerjakan ⇄ Menunggu Sparepart → Selesai / Dibatalkan**. Satu aset
hanya boleh punya satu tiket terbuka. Jenis tiket: Perbaikan, Preventif, Upgrade, Kalibrasi.

### 6.2 Maintenance Preventif Terjadwal

```mermaid
flowchart LR
    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph ADM[Lane: Administrator]
            direction LR
            S((Rencana dibuat:<br/>interval bulan,<br/>cakupan, PIC)):::start
            A1(Jalankan rencana)
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            TM((⏱ H-7 jatuh<br/>tempo)):::inter --> B1(⚙ Alert<br/>Maintenance jatuh tempo)
            B2(⚙ Buat tiket preventif<br/>untuk aset Siap Guna/Digunakan<br/>tanpa tiket terbuka)
            B3(⚙ Hitung jatuh<br/>tempo berikutnya)
            E(((Tiket preventif<br/>dibuat))):::endEv
        end
    end
    S --> TM
    B1 --> A1 --> B2 --> B3 --> E
    E -. tiap tiket mengikuti .-> N[[Proses 6.1]]

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef inter fill:#fef3c7,stroke:#d97706,stroke-width:2px
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
```

---

## 7. Proses Pensiun Aset (Disposal)

```mermaid
flowchart LR
    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph ADM[Lane: Administrator]
            direction LR
            S((Aset tidak<br/>layak / hilang)):::start --> A1(Pilih alasan pensiun)
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            P{＋}:::gw
            B1(⚙ Status → Pensiun)
            B2(⚙ Kosongkan pemegang)
            B3(⚙ Lepas semua seat<br/>lisensi di perangkat)
            J{＋}:::gw
            E(((Aset<br/>pensiun))):::endEv
            D1[(Riwayat aset)]
        end
    end
    A1 --> P
    P --> B1 --> J
    P --> B2 --> J
    P --> B3 --> J
    J --> E
    B1 -. catat: Pensiun .-> D1

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

Alasan: Habis masa pakai, Rusak berat, Hilang, Dijual, Dihibahkan, Dimusnahkan. Bisa dari status
*Siap Guna*, *Digunakan*, atau *Perbaikan*. *Pensiun* adalah status akhir; datanya tetap tersimpan
untuk audit.

---

## 8. Proses Stok Komponen dan Aksesori

```mermaid
flowchart LR
    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph TEK[Lane: Teknisi IT]
            direction LR
            S((Permintaan<br/>barang)):::start --> G1{✕ Jenis?}:::gw
            G1 -- Komponen --> T1(Pasang komponen<br/>ke aset)
            G1 -- Aksesori --> T2(Pinjamkan aksesori<br/>ke karyawan)
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            G2{✕ Stok<br/>cukup?}:::gw
            B1(⚙ Kurangi stok tersedia<br/>& catat transaksi)
            G3{✕ Stok ≤<br/>minimum?}:::gw
            B2(⚙ Alert Stok menipis<br/>Kritis jika habis)
            X(⚙ Tolak permintaan)
            E1(((Selesai))):::endEv
            E2(((Ditolak))):::endEv
        end
    end
    T1 --> G2
    T2 --> G2
    G2 -- Ya --> B1 --> G3
    G2 -- Tidak --> X --> E2
    G3 -- Ya --> B2 --> E1
    G3 -- Tidak --> E1

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

Komponen bawaan pabrik bisa dicatat tanpa mengurangi stok. Saat komponen dilepas atau aksesori
dikembalikan, stok bertambah lagi dan alert stok tertutup sendiri bila stok kembali di atas minimum.

---

## 9. Proses Lisensi Software

```mermaid
flowchart LR
    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph ADM[Lane: Administrator / Teknisi IT]
            direction LR
            S((Kebutuhan<br/>software)):::start --> A1(Pilih lisensi)
            A1 --> G1{✕ Pemegang?}:::gw
            G1 -- Karyawan --> A2(Pilih karyawan)
            G1 -- Perangkat --> A3(Pilih aset)
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            G2{✕ Seat tersedia &<br/>belum dimiliki?}:::gw
            B1(⚙ Beri seat)
            B2(⚙ Perbarui alert<br/>Kuota penuh)
            X(⚙ Tolak)
            TM((⏱ H-30<br/>kedaluwarsa)):::inter
            B3(⚙ Alert Lisensi<br/>segera habis)
            E1(((Seat<br/>diberikan))):::endEv
            E2(((Ditolak))):::endEv
        end
    end
    A2 --> G2
    A3 --> G2
    G2 -- Ya --> B1 --> B2 --> E1
    G2 -- Tidak --> X --> E2
    TM --> B3

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef inter fill:#fef3c7,stroke:#d97706,stroke-width:2px
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

Tipe lisensi: Langganan, Perpetual, OEM, Concurrent. Seat dilepas secara manual, atau otomatis
saat perangkatnya dipensiunkan.

---

## 10. Proses Sinkronisasi Karyawan dan Offboarding

```mermaid
flowchart LR
    subgraph HERD[Pool: HERD]
        H1(Daftar karyawan<br/>yang di-grant ke aplikasi iam)
    end

    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph SYS[Lane: Sistem]
            direction LR
            TM((⏱ Setiap hari<br/>02:00)):::inter --> B1(⚙ Catat sync run<br/>status running)
            MAN((Admin klik<br/>Sync sekarang)):::start --> B1
            B1 --> B2(⚙ Ambil data<br/>per halaman)
            B2 --> G0{✕ HERD<br/>merespons?}:::gw
            G0 -- Tidak --> F(⚙ Sync run → failed<br/>simpan pesan error) --> EF(((Gagal))):::endEv
            G0 -- Ya --> B3(⚙ Upsert karyawan<br/>berdasarkan NIK)
            B3 --> G1{✕ Nonaktif<br/>di HERD?}:::gw
            G1 -- Ya --> B4(⚙ Status → Nonaktif)
            B4 --> G2{✕ Masih pegang aset /<br/>aksesori / seat?}:::gw
            G2 -- Ya --> B5(⚙ Alert KRITIS Offboarding<br/>batas kembali 7 hari)
            G1 -- Tidak --> B6(⚙ Sync run → success<br/>jumlah baru/diperbarui/offboarding)
            G2 -- Tidak --> B6
            B5 --> B6 --> E(((Sync<br/>selesai))):::endEv
            D1[(herd_sync_runs)]
        end
        subgraph TEK[Lane: Teknisi IT]
            direction LR
            T1(Tarik aset, aksesori,<br/>lepas seat lisensi) --> E2(((Alert tertutup<br/>otomatis))):::endEv
        end
    end

    B2 -. GET /applications/iam/users .-> H1
    H1 -. data karyawan .-> G0
    B5 --> T1
    B1 -.-> D1
    B6 -.-> D1

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef inter fill:#fef3c7,stroke:#d97706,stroke-width:2px
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

Alert offboarding juga muncul bila status karyawan berubah menjadi Resign, Mutasi, atau Nonaktif
lewat jalur lain. Alert tertutup sendiri setelah semua barang kembali.

---

## 11. Proses Akses dan Login

### 11.1 Pemberian Akses Pengguna

Untuk masuk, karyawan butuh **dua hal**: di-grant ke aplikasi `iam` di HERD (untuk SSO) dan
punya **peran lokal** (Administrator / Teknisi IT / Viewer) di aplikasi ini.

```mermaid
flowchart LR
    subgraph HERD[Pool: HERD]
        H1(Tim IT grant karyawan<br/>ke aplikasi iam)
    end
    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph ADM[Lane: Administrator]
            direction LR
            S((Karyawan butuh<br/>akses)):::start --> A1(Buka Pengaturan →<br/>Pengguna → Akses dari HERD)
            A1 --> A2(Pilih karyawan,<br/>tentukan peran)
            A2 --> G1{✕ Perlu login<br/>cadangan?}:::gw
            G1 -- Ya --> A3(Atur password lokal)
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            B1(⚙ Verifikasi grant ke HERD<br/>upsert karyawan, buat user)
            E(((Akses<br/>aktif))):::endEv
        end
    end
    H1 -. daftar user ter-grant .-> A1
    A2 --> B1
    B1 -. cek NIK .-> H1
    G1 -- Tidak --> E
    A3 --> E

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

Administrator pertama dibuat dari konsol: `php artisan users:set-password <NIK> --role=admin`.

### 11.2 Login (Dua Jalur)

```mermaid
flowchart LR
    subgraph USR[Pool: Pengguna]
        S((Buka halaman<br/>Masuk)):::start --> G1{✕ Metode?}:::gw
        G1 -- Masuk dengan HERD --> U1(Klik tombol SSO)
        G1 -- HERD tidak tersedia --> U2(Isi NIK + password)
    end

    subgraph HERD[Pool: HERD]
        H1(Login akun perusahaan)
        H2{✕ Di-grant<br/>ke iam?}:::gw
        H3(Kirim kode otorisasi)
        H4(Tolak — access denied)
    end

    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        B1(⚙ Buat state + PKCE,<br/>redirect ke HERD)
        B2(⚙ Cek state, tukar kode<br/>→ token → userinfo)
        B3(⚙ Cocokkan NIK ke karyawan)
        B4(⚙ Cek NIK + password,<br/>maks. 5 percobaan)
        G2{✕ Punya peran lokal<br/>& karyawan aktif?}:::gw
        OK(⚙ Buat sesi,<br/>catat login terakhir)
        X(⚙ Tampilkan pesan<br/>di halaman Masuk)
        E1(((Masuk<br/>dashboard))):::endEv
        E2(((Gagal<br/>masuk))):::endEv
    end

    U1 --> B1
    B1 -. authorize .-> H1 --> H2
    H2 -- Ya --> H3
    H2 -- Tidak --> H4
    H3 -. code + state .-> B2
    H4 -. error .-> X
    B2 --> B3 --> G2
    U2 --> B4 --> G2
    G2 -- Ya --> OK --> E1
    G2 -- Tidak --> X --> E2

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

| Jalur | Kapan dipakai | Kontrol |
|---|---|---|
| **SSO HERD** | Normal | OIDC Authorization Code + PKCE (S256), validasi `state`, harus di-grant di HERD |
| **Login lokal** | HERD tidak bisa dihubungi | Password ter-hash di database aplikasi, maks. 5 percobaan per NIK + IP |

Kedua jalur memakai peran yang sama, jadi hak akses tidak berubah apa pun cara masuknya.

---

## 12. Proses Peringatan Otomatis (Alert)

```mermaid
flowchart LR
    subgraph SYS[Pool: IT Asset Manager — Sistem]
        direction LR
        TM((⏱ Setiap hari<br/>06:00)):::inter --> P{＋}:::gw
        TX((Transaksi<br/>terjadi)):::start --> P
        P --> C1(⚙ Garansi ≤ 30 hari)
        P --> C2(⚙ Lisensi ≤ 30 hari<br/>/ kuota penuh)
        P --> C3(⚙ Lewat tanggal<br/>rencana kembali)
        P --> C4(⚙ Stok ≤ minimum)
        P --> C5(⚙ Maintenance ≤ 7 hari)
        P --> C6(⚙ Offboarding)
        C1 --> J{＋}:::gw
        C2 --> J
        C3 --> J
        C4 --> J
        C5 --> J
        C6 --> J
        J --> G{✕ Kondisi<br/>masih berlaku?}:::gw
        G -- Ya --> O(⚙ Buka / perbarui alert)
        G -- Tidak --> R(⚙ Tutup alert otomatis)
        O --> E(((Dashboard &<br/>menu Alert))):::endEv
        R --> E
    end

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef inter fill:#fef3c7,stroke:#d97706,stroke-width:2px
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

| Alert | Pemicu |
|---|---|
| Offboarding karyawan | Karyawan tidak aktif masih memegang barang IT |
| Garansi segera habis | Garansi berakhir dalam 30 hari |
| Lisensi segera habis | Lisensi kedaluwarsa dalam 30 hari |
| Kuota lisensi penuh | Semua seat terpakai |
| Pengembalian terlambat | Melewati tanggal rencana kembali |
| Stok menipis | Stok komponen/aksesori ≤ minimum |
| Maintenance jatuh tempo | Rencana preventif jatuh tempo dalam 7 hari |

---

## 13. Proses Pelaporan

```mermaid
flowchart LR
    subgraph IAM[Pool: IT Asset Manager]
        direction TB
        subgraph USR[Lane: Semua Peran]
            direction LR
            S((Butuh<br/>laporan)):::start --> U1(Pilih jenis laporan<br/>& rentang tanggal)
            U1 --> G{✕ Perlu<br/>file?}:::gw
            G -- Ya --> U2(Ekspor)
        end
        subgraph SYS[Lane: Sistem]
            direction LR
            B1(⚙ Hitung rekap)
            D1[(Database aset,<br/>lisensi, maintenance, stok)]
            E(((Laporan<br/>tersedia))):::endEv
        end
    end
    U1 --> B1
    D1 -.-> B1
    B1 --> G
    G -- Tidak --> E
    U2 --> E

    classDef start fill:#dcfce7,stroke:#16a34a
    classDef endEv fill:#fee2e2,stroke:#dc2626,stroke-width:3px
    classDef gw fill:#fef9c3,stroke:#ca8a04
```

| Laporan | Dasar tanggal |
|---|---|
| Inventaris per status / kategori / lokasi | Tanggal pembelian |
| Nilai inventaris (nilai perolehan) | Tanggal pembelian |
| Lisensi & utilisasi | Tanggal kedaluwarsa |
| Biaya maintenance | Tanggal selesai |
| Stok komponen & aksesori | — |

---

## 14. Kontrol dan Jejak Audit

| Kontrol | Penerapan |
|---|---|
| **Riwayat aset** | Setiap aksi dicatat: pelaku, waktu, status sebelum/sesudah, catatan |
| **Validasi transisi** | Hanya transisi status pada §2 yang diizinkan |
| **Penguncian transaksi** | Mencegah dua pengguna memproses aset, stok, atau seat yang sama bersamaan |
| **Pembagian peran** | Pengadaan, ubah data, pensiun, dan kelola pengguna hanya untuk Administrator |
| **Keamanan login** | SSO dengan PKCE + `state`; password lokal ter-hash dan dibatasi percobaannya |
| **Log sinkronisasi** | Setiap sync HERD tercatat di `herd_sync_runs`, termasuk yang gagal |
