# sena

`sena` adalah sistem manajemen program seminar, webinar, pelatihan tutor, peserta, materi LMS, asesmen, bukti dukung, payment terms, dan sertifikat digital untuk alur RTIK Local, RTIK Pusat, tutor, dan peserta.

Project ini berjalan menggunakan Laravel 12, Filament 3, Livewire, MariaDB, Nginx, dan Docker.

## Fitur Utama

- Login tunggal dengan redirect otomatis berdasarkan role.
- Panel Super Admin / Admin RTIK Pusat.
- Panel Admin RTIK Local.
- Panel Tutor / Fasilitator.
- Panel Peserta.
- Wizard pengajuan seminar/webinar.
- Approval event, publish event, catatan revisi, dan status pembayaran.
- Data lokasi, peserta, tutor, RAB, rundown, dan payment terms.
- Materi event, pertemuan, PDF/PPT/video, pre-test, kuis modul, dan post-test.
- Dashboard peserta dengan event aktif, lokasi/Zoom, WhatsApp Group, progres, dan sertifikat.
- Bukti dukung peserta dan kegiatan.
- Template absensi kering.
- Preview dan download sertifikat PDF.
- Template sertifikat dua halaman: halaman sertifikat dan halaman rekap materi/nilai.

## Struktur Project

```text
.
├── docker-compose.yml
├── db/
├── docs/
├── nginx/
├── php/
└── src/                 # Laravel application
    ├── app/
    ├── database/
    ├── resources/
    ├── routes/
    └── public/
```

## Menjalankan Project

Pastikan Docker aktif.

```bash
docker compose up -d --build
```

Masuk ke container PHP:

```bash
docker compose exec php bash
```

Command Laravel dijalankan dari folder `/var/www/html` di container:

```bash
php artisan migrate
php artisan db:seed
php artisan optimize:clear
```

Untuk menjalankan test:

```bash
php artisan test
```

## URL Lokal

```text
https://isoc.test
https://isoc.test/login
```

Semua user login dari satu halaman:

```text
https://isoc.test/login
```

Setelah berhasil login, sistem akan mengarahkan user ke panel sesuai role.

## Akun Default

Password default semua akun utama:

```text
password
```

| Role | Email | Panel |
|---|---|---|
| Super Admin / Admin RTIK Pusat | `su@isoc.id` | `/superadmin` |
| Admin RTIK Local | `adm@isoc.id` | `/admin` |
| Tutor | `tutor@isoc.id` | `/tutor` |
| Peserta | `peserta@isoc.id` | `/peserta` |

Dokumen akun lengkap ada di:

```text
docs/users.md
```

## Alur Sistem

### 1. Admin RTIK Local Mengajukan Event

Admin RTIK Local membuat seminar/webinar melalui wizard:

- Data lokasi atau sekolah.
- Kategori peserta: sekolah atau umum.
- Data peserta.
- Data tutor/fasilitator.
- RAB.
- Rundown acara.
- Materi event yang akan digunakan.

Setelah pengajuan dikirim, event masuk ke proses approval Admin RTIK Pusat.

### 2. Admin RTIK Pusat Melakukan Approval

Admin RTIK Pusat mengecek kelengkapan pengajuan:

- Approve event.
- Minta revisi dengan catatan.
- Cancel event.
- Publish event agar tampil di landing page.
- Mengaktifkan payment terms sesuai termin.

User peserta dan tutor dibuat setelah pengajuan awal disetujui sesuai workflow sistem.

### 3. Tutor Mengikuti ToT dan Mendampingi Event

Tutor login ke panel tutor untuk:

- Mengakses ToT.
- Melihat event yang ditugaskan.
- Melihat rundown.
- Upload bukti dukung sesi.
- Monitoring peserta.

### 4. Peserta Mengikuti LMS

Peserta login ke panel peserta untuk:

- Melihat event yang diikuti.
- Membuka modul.
- Mengerjakan pre-test.
- Mengerjakan kuis modul secara berurutan.
- Mengerjakan post-test setelah kuis modul selesai.
- Upload bukti follow IG dan checklist join WhatsApp Group.
- Preview/download sertifikat jika eligible.

### 5. Sertifikat

Sertifikat hanya dapat dicetak jika syarat belajar dan bukti dukung terpenuhi:

- Pre-test selesai.
- Kuis modul selesai berurutan.
- Post-test selesai.
- Bukti peserta/kegiatan sudah sesuai.
- Event sudah melewati validasi yang diperlukan.

## Command Penting

Clear cache:

```bash
docker compose exec php php artisan optimize:clear
```

Cache Blade:

```bash
docker compose exec php php artisan view:cache
```

Migrasi:

```bash
docker compose exec php php artisan migrate
```

Seeder:

```bash
docker compose exec php php artisan db:seed
```

Test:

```bash
docker compose exec php php artisan test
```

## Dokumen Pendukung

```text
docs/BRD.md
docs/PRD.md
docs/WORKFLOW.md
docs/PANDUAN_APLIKASI.md
docs/users.md
docs/modul ajar/
docs/templte sertifikat/
```

## Catatan Development

- Source Laravel ada di folder `src`.
- File `.env` Laravel ada di `src/.env`.
- Nama aplikasi menggunakan `APP_NAME="sena"`.
- Database berjalan di service Docker `db`.
- Host lokal utama menggunakan domain `isoc.test`.
- Route `/login` mengarah ke login tunggal Filament.

## Troubleshooting Singkat

Jika perubahan UI belum muncul:

```bash
docker compose exec php php artisan optimize:clear
docker compose exec php php artisan view:clear
```

Jika route/cache Filament bermasalah:

```bash
docker compose exec php php artisan optimize:clear
docker compose exec php php artisan filament:clear-cached-components
```

Jika database belum lengkap:

```bash
docker compose exec php php artisan migrate --seed
```
