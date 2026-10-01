# Panduan Aplikasi SI-DSC Digital Safety Champions

Dokumen ini berisi panduan penggunaan aplikasi SI-DSC untuk mengelola program Digital Safety Champions, mulai dari pendaftaran event/lokus, verifikasi RTIK Pusat, ToT tutor, pelaksanaan pelatihan, bukti dukung, sertifikat, sampai termin pembayaran.

## 1. Ringkasan Aplikasi

SI-DSC adalah aplikasi operasional berbasis Laravel dan Filament untuk mendukung workflow Digital Safety Champions.

Fungsi utama aplikasi:

- Mengelola event/lokus pelatihan.
- Mengelola data sekolah/tempat kegiatan.
- Mengelola peserta dan tutor/fasilitator.
- Mengelola materi, modul, kuis, pre-test, dan post-test.
- Mengelola ToT tutor.
- Mengelola absensi digital dan template absensi.
- Mengelola bukti dukung kegiatan.
- Mengelola sertifikat dan eligibility peserta.
- Mengelola payment terms 3 termin.
- Memantau KPI program.

## 2. Akses Aplikasi

### URL Lokal

Gunakan URL berikut saat aplikasi dijalankan di environment lokal:

| Halaman | URL |
|---|---|
| Website publik | `https://isoc.test` |
| Panel Super Admin / RTIK Pusat | `https://isoc.test/superadmin/login` |
| Panel Admin RTIK Local | `https://isoc.test/admin/login` |
| Panel Tutor | `https://isoc.test/tutor/login` |
| Panel Peserta | `https://isoc.test/peserta/login` |
| Verifikasi sertifikat | `https://isoc.test/verify/{nomor-sertifikat}` |

Pastikan domain `isoc.test` sudah diarahkan ke `127.0.0.1` di file hosts perangkat.

Contoh:

```text
127.0.0.1 isoc.test
```

### Akun Default Lokal

| Role | Email | Password | Panel |
|---|---|---|---|
| Super Admin / Admin RTIK Pusat | `su@isoc.id` | `password` | `superadmin` |
| Admin RTIK Local | `adm@isoc.id` | `password` | `admin` |
| Tutor | `tutor@isoc.id` | `password` | `tutor` |
| Peserta | `peserta@isoc.id` | `password` | `peserta` |

Catatan: akun lokal dipakai untuk development/demo. Untuk production, password wajib diganti dan user dummy tidak boleh dipakai.

## 3. Menjalankan Aplikasi Lokal

Jalankan perintah berikut dari root project:

```bash
docker compose up -d --build
```

Cek status container:

```bash
docker compose ps
```

Status yang diharapkan:

- `isoc_db`: `healthy`
- `isoc_php`: `healthy`
- `isoc_nginx`: `healthy`

Cek health endpoint:

```bash
curl --insecure --header 'Host: isoc.test' https://127.0.0.1/up
```

Jika halaman menampilkan `Application up`, aplikasi sudah berjalan.

## 4. Role dan Hak Akses

### Super Admin / Admin RTIK Pusat

Panel: `superadmin`

Tanggung jawab:

- Memverifikasi pengajuan event/lokus.
- Menyetujui atau meminta revisi Termin-1, Termin-2, dan Termin-3.
- Memantau data lintas daerah/lokus.
- Mengelola master data.
- Mengelola template dan desain sertifikat.
- Memvalidasi bukti dukung dan penerbitan sertifikat.

### Admin RTIK Local / Daerah

Panel: `admin`

Tanggung jawab:

- Mendaftarkan event/lokus.
- Menginput data sekolah/tempat kegiatan.
- Menginput atau import peserta.
- Menginput atau import tutor/fasilitator.
- Menginput RAB dan kategori biaya.
- Mengunggah bukti dukung kegiatan.
- Memantau status payment terms dan catatan dari RTIK Pusat.

### Tutor / Fasilitator

Panel: `tutor`

Tanggung jawab:

- Mengikuti ToT tutor.
- Mendukung pelaksanaan pelatihan lapangan.
- Membantu monitoring peserta.
- Membantu absensi dan dokumentasi.
- Mengunggah bukti kegiatan sesuai hak akses.
- Memantau materi, modul, dan hasil tes peserta yang terkait.

### Peserta / Siswa

Panel: `peserta`

Tanggung jawab:

- Mengakses modul pembelajaran.
- Mengikuti pre-test.
- Mengerjakan kuis per modul.
- Mengerjakan post-test setelah syarat terpenuhi.
- Mengakses sertifikat jika sudah eligible.

## 5. Website Publik

Website publik dapat diakses tanpa login.

Menu utama:

- Home: halaman utama program.
- About: informasi tentang program.
- Programs: daftar program.
- Events: daftar event yang tersedia.
- Resources: halaman sumber daya.
- Our Partner: informasi mitra.
- Event Registration: pendaftaran peserta untuk event tertentu.
- Certificate Verification: validasi nomor sertifikat.

Route penting:

| Fungsi | URL |
|---|---|
| Halaman utama | `/` |
| Tentang program | `/about` |
| Program | `/programs` |
| Event | `/events` |
| Resources | `/resources` |
| Partner | `/our-partner` |
| Pendaftaran event | `/events/{event}/register` |
| Verifikasi sertifikat | `/verify/{certificateNumber}` |

## 6. Alur Utama Admin RTIK Local

### 6.1 Login

1. Buka `https://isoc.test/admin/login`.
2. Masukkan email dan password Admin RTIK Local.
3. Setelah login, pengguna masuk ke dashboard panel admin.

### 6.2 Membuat Event / Lokus

1. Buka menu `Seminar > Kelola Seminar`.
2. Klik tombol `Create` atau `Add Seminar`.
3. Isi wizard event/lokus.

Data yang perlu dilengkapi:

- Data sekolah atau tempat event.
- Jadwal pelatihan.
- Target peserta.
- Data peserta.
- Data tutor/fasilitator.
- RAB dan kategori biaya.
- Dokumen persiapan jika tersedia.

Setelah disimpan, sistem akan:

- Membuat atau menautkan data sekolah/tempat.
- Membuat data event/lokus.
- Menyimpan data peserta.
- Menyimpan data tutor/fasilitator.
- Menyiapkan payment terms Termin-1, Termin-2, dan Termin-3.

### 6.3 Submit Pengajuan ke RTIK Pusat

1. Buka daftar `Kelola Seminar`.
2. Pilih event/lokus yang sudah lengkap.
3. Klik action `Submit`.
4. Event masuk ke status pengajuan untuk diverifikasi Super Admin / RTIK Pusat.

Jika data belum lengkap, lengkapi dahulu data event, peserta, tutor, jadwal, dan RAB.

### 6.4 Mengelola Peserta

Menu terkait: `Absensi & Peserta > Peserta`

Data peserta yang dikelola:

- Nama lengkap.
- Email.
- Nomor kontak.
- Sekolah/lokus.
- Status peserta.
- Akun peserta.

Peserta dapat dibuat dari wizard event atau dibuat secara manual melalui resource peserta.

### 6.5 Mengelola Tutor

Menu terkait: `Absensi & Peserta > Tutor`

Data tutor yang dikelola:

- Nama lengkap.
- Email.
- Nomor kontak.
- Institusi.
- Sekolah/lokus dampingan.
- Status ToT.

Tutor dapat dibuat dari wizard event atau dibuat secara manual melalui resource tutor.

### 6.6 Mengelola Sekolah

Menu terkait: `Absensi & Peserta > Sekolah`

Data sekolah/tempat kegiatan yang dikelola:

- Nama sekolah/tempat.
- NPSN jika tersedia.
- Alamat.
- Provinsi, kota/kabupaten, kecamatan, dan kelurahan.
- PIC.
- Kontak PIC.
- Link Google Maps jika tersedia.

Gunakan action `Open Maps` untuk membuka lokasi jika URL maps tersedia.

### 6.7 Mengelola Jadwal Sesi

Menu terkait: `Absensi & Peserta > Jadwal Sesi`

Gunakan menu ini untuk mencatat sesi pelatihan.

Data utama:

- Event/lokus.
- Tanggal sesi.
- Jam mulai dan selesai.
- Lokasi.
- Tutor terkait.

Gunakan action `Absensi Kering` untuk membuka atau mencetak template absensi.

### 6.8 Mengunggah Bukti Dukung

Menu terkait: `Validasi & Sertifikat > Bukti Dukung`

Jenis bukti dukung yang umumnya diperlukan:

- Absensi basah.
- Foto kegiatan.
- Video slogan.
- Bukti follow Instagram.
- Bukti join WAG.
- Bukti registrasi e-Certificate.
- Bukti praktik microsite.
- RAB final.
- Kuitansi atau invoice.
- Bukti logistik.
- Laporan akhir.

Alur:

1. Buka menu `Bukti Dukung`.
2. Klik `Create`.
3. Pilih event/lokus.
4. Pilih jenis bukti.
5. Upload file atau isi URL bukti.
6. Simpan.
7. Tunggu validasi dari Super Admin / RTIK Pusat.

## 7. Alur Super Admin / RTIK Pusat

### 7.1 Login

1. Buka `https://isoc.test/superadmin/login`.
2. Login menggunakan akun Super Admin.
3. Masuk ke dashboard Super Admin.

### 7.2 Verifikasi Event / Lokus

1. Buka menu `Seminar > Kelola Seminar`.
2. Cari event/lokus dengan status submitted.
3. Periksa data:
   - Sekolah/tempat.
   - Jadwal.
   - Peserta.
   - Tutor.
   - RAB.
   - Dokumen persiapan.
4. Pilih action sesuai keputusan:
   - `Verify T1` untuk menyetujui Termin-1.
   - `Request Revision` untuk meminta perbaikan.
   - `Cancel` untuk membatalkan pengajuan.

Jika meminta revisi atau membatalkan, isi catatan agar Admin RTIK Local mengetahui perbaikan yang diperlukan.

### 7.3 Approval Payment Terms

Menu terkait: `Validasi & Sertifikat > Payment Terms`

Termin yang digunakan:

- Termin-1: persiapan.
- Termin-2: pelaksanaan.
- Termin-3: final.

Action penting:

- `Approve`: menyetujui termin.
- `Mark Paid`: menandai termin sudah dibayar.

### 7.4 Validasi Bukti Dukung

Menu terkait: `Validasi & Sertifikat > Bukti Dukung`

Action validasi:

- `Setujui`: bukti diterima.
- `Tolak`: bukti ditolak.
- `Minta Revisi`: bukti perlu diperbaiki.

Gunakan catatan validasi agar Admin RTIK Local atau tutor mengetahui alasan penolakan/revisi.

### 7.5 Mengelola Template Sertifikat

Menu terkait:

- `Validasi & Sertifikat > Template Sertifikat`
- `Validasi & Sertifikat > Studio Sertifikat`

Fungsi:

- Membuat template sertifikat.
- Mengatur teks sertifikat.
- Mengunggah aset desain.
- Membuka studio desain sertifikat.
- Menyiapkan template untuk penerbitan e-certificate.

### 7.6 Penerbitan Sertifikat

Menu terkait: `Validasi & Sertifikat > e-Certificate`

Alur:

1. Buka data sertifikat peserta.
2. Klik `Cek Eligibility`.
3. Jika eligible, klik `Terbitkan`.
4. Peserta dapat mengakses atau memverifikasi sertifikat.

Sertifikat tidak dapat diterbitkan jika syarat peserta atau bukti dukung belum terpenuhi.

## 8. Alur Tutor

### 8.1 Login

1. Buka `https://isoc.test/tutor/login`.
2. Login menggunakan akun tutor.
3. Tutor diarahkan ke halaman `Pelatihan Tutor`.

### 8.2 Mengikuti ToT

Menu terkait: `Pelatihan Tutor > ToT Awal`

Tutor wajib menyelesaikan ToT sebelum melanjutkan kegiatan lapangan. Nilai ToT dicatat di sistem oleh admin yang berwenang.

### 8.3 Melihat Rundown Acara

Menu terkait: `Seminar & Materi > Rundown Acara`

Tutor menggunakan halaman ini untuk melihat agenda dan alur kegiatan event/lokus.

### 8.4 Membantu Pelaksanaan

Tutor dapat membantu:

- Monitoring peserta.
- Melihat jadwal sesi.
- Membantu absensi.
- Mengunggah bukti dukung.
- Memantau pre-test, kuis, dan post-test.

## 9. Alur Peserta

### 9.1 Login

1. Buka `https://isoc.test/peserta/login`.
2. Login menggunakan akun peserta.
3. Masuk ke dashboard peserta.

### 9.2 Mengakses Modul

Menu terkait: `Seminar & Materi > Modul`

Peserta dapat melihat modul pembelajaran sesuai event/lokus tempat peserta terdaftar.

### 9.3 Mengerjakan Tes

Menu terkait: `Seminar & Materi > Tes`

Urutan pengerjaan:

1. Pre-test.
2. Kuis per modul.
3. Post-test.

Post-test terkunci jika pre-test belum selesai atau kuis modul belum lengkap.

### 9.4 Mengakses Sertifikat

Peserta dapat mengakses sertifikat jika:

- Pre-test selesai.
- Kuis modul selesai.
- Post-test selesai.
- Bukti dukung wajib event/lokus sudah disetujui.
- Sertifikat sudah diterbitkan oleh admin.

## 10. Menu Utama Panel Admin dan Super Admin

### Seminar

| Menu | Fungsi |
|---|---|
| Kelola Seminar | Membuat dan mengelola event/lokus |
| Add Seminar | Pintasan membuat seminar/event |

### Konten & Penilaian

| Menu | Fungsi |
|---|---|
| Materi Event | Mengelola template materi event |
| Pertemuan & Materi | Mengelola pertemuan dan materi |
| Learning Material | Mengelola bahan ajar |
| Tes & Kuis | Mengelola assessment, pre-test, post-test, dan kuis |

### Penilaian

| Menu | Fungsi |
|---|---|
| Assessment Attempt | Melihat hasil pengerjaan assessment |
| ToT Assessment | Mengelola nilai ToT tutor |

### Absensi & Peserta

| Menu | Fungsi |
|---|---|
| Sekolah | Mengelola sekolah/tempat kegiatan |
| Peserta | Mengelola peserta |
| Tutor | Mengelola tutor/fasilitator |
| Jadwal Sesi | Mengelola sesi pelatihan |
| Attendance | Mengelola absensi |
| WAG Group | Mengelola grup WhatsApp mentoring |

### Tugas Peserta

| Menu | Fungsi |
|---|---|
| Microsite Practice | Mengelola praktik microsite peserta |

### Komunitas

| Menu | Fungsi |
|---|---|
| Peer Group | Mengelola kelompok belajar/peer group |

### Validasi & Sertifikat

| Menu | Fungsi |
|---|---|
| Bukti Dukung | Upload dan validasi bukti dukung |
| Payment Terms | Mengelola termin pembayaran |
| e-Certificate | Mengelola sertifikat peserta |
| Template Sertifikat | Mengelola template sertifikat |
| Studio Sertifikat | Mendesain sertifikat |

### Administrasi

| Menu | Fungsi |
|---|---|
| User | Mengelola akun pengguna |
| Profil Saya | Mengelola profil pengguna login |

## 11. Payment Terms

### Termin-1 Persiapan

Syarat umum:

- Data sekolah/tempat lengkap.
- Data peserta valid.
- Data tutor/fasilitator valid.
- Jadwal pelatihan lengkap.
- RAB awal tersedia.
- Dokumen persiapan tersedia jika diperlukan.

Hasil approval Termin-1:

- Event/lokus dinyatakan siap.
- Payment Termin-1 menjadi eligible atau approved.
- Akun peserta/tutor dapat dipakai sesuai data yang sudah dibuat.

### Termin-2 Pelaksanaan

Syarat umum:

- ToT tutor selesai sesuai standar.
- Kegiatan lapangan terlaksana.
- Absensi basah tersedia.
- Foto kegiatan tersedia.
- Video slogan tersedia.
- Bukti follow Instagram tersedia.
- Bukti join WAG tersedia.
- Pre-test, kuis, dan post-test berjalan.
- Bukti pelaksanaan modul tersedia.

### Termin-3 Final

Syarat umum:

- RAB final tersedia.
- Kuitansi/invoice tersedia.
- Bukti logistik tersedia.
- Laporan akhir tersedia.
- Sertifikat peserta sudah memenuhi eligibility.
- Bukti final sudah divalidasi.

## 12. Lifecycle Event / Lokus

Status umum event/lokus:

- Draft.
- Submitted.
- Needs Revision.
- Verified for Termin-1.
- ToT In Progress.
- ToT Completed.
- Field Training Scheduled.
- Field Training Completed.
- Evidence Submitted.
- Verified for Termin-2.
- Final Report Submitted.
- Verified for Termin-3.
- Closed.
- Cancelled.

Catatan: nama status di tampilan dapat mengikuti label yang digunakan di aplikasi, tetapi alur bisnisnya mengikuti lifecycle di atas.

## 13. Eligibility Sertifikat

Peserta eligible menerima sertifikat jika:

- Peserta terdaftar pada event/lokus.
- Pre-test selesai.
- Semua kuis modul selesai.
- Post-test selesai.
- Bukti dukung wajib event/lokus sudah disetujui.
- Sertifikat sudah dicek dan diterbitkan oleh admin.

Jika salah satu syarat belum terpenuhi, sertifikat dapat berstatus blocked atau belum dapat diterbitkan.

## 14. Import Template

Aplikasi menyediakan template import:

| Template | URL |
|---|---|
| Peserta | `/templates/import/participants.xlsx` |
| Tutor | `/templates/import/tutors.xlsx` |

Template hanya dapat diunduh oleh user yang sudah login.

## 15. Troubleshooting

### Aplikasi menampilkan 502 Bad Gateway

Penyebab umum:

- Nginx sudah berjalan, tetapi PHP-FPM belum siap.
- Container PHP masih menjalankan setup Laravel, migration, atau project init.
- PHP-FPM belum listen di port `9000`.

Langkah pengecekan:

```bash
docker compose ps
docker compose logs --tail=100 php
docker compose logs --tail=100 nginx
```

Pastikan `isoc_php` dan `isoc_nginx` berstatus `healthy`.

### Domain `isoc.test` tidak terbuka

Cek file hosts:

```text
127.0.0.1 isoc.test
```

Cek container:

```bash
docker compose ps
```

Cek HTTPS lokal:

```bash
curl --insecure --header 'Host: isoc.test' https://127.0.0.1/up
```

### Perubahan kode tidak muncul

Jalankan clear cache di container PHP:

```bash
docker compose exec php php artisan optimize:clear
```

Jika ada perubahan dependency atau image:

```bash
docker compose up -d --build
```

### Database belum siap

Cek status database:

```bash
docker compose ps db
docker compose logs --tail=100 db
```

Jika database belum healthy, tunggu beberapa saat lalu cek ulang.

## 16. Catatan Operasional

- Gunakan akun sesuai role masing-masing.
- Jangan memakai akun Super Admin untuk pekerjaan Admin Local harian.
- Setiap revisi dari RTIK Pusat harus ditindaklanjuti melalui data event, payment, atau bukti dukung terkait.
- Upload bukti dukung dengan nama file yang jelas.
- Pastikan peserta menyelesaikan pre-test, kuis modul, dan post-test sebelum proses sertifikat.
- Untuk production, ubah password default dan batasi akses user dummy/demo.

## 17. Ringkasan Alur End-to-End

1. Admin RTIK Local login ke panel admin.
2. Admin RTIK Local membuat event/lokus.
3. Admin RTIK Local mengisi sekolah, peserta, tutor, jadwal, dan RAB.
4. Admin RTIK Local submit event ke RTIK Pusat.
5. Super Admin / RTIK Pusat memverifikasi pengajuan.
6. Jika sesuai, RTIK Pusat menyetujui Termin-1.
7. Tutor mengikuti dan menyelesaikan ToT.
8. Pelatihan lapangan dilaksanakan.
9. Peserta mengerjakan pre-test, modul, kuis, dan post-test.
10. Admin Local/Tutor mengunggah bukti dukung.
11. RTIK Pusat memverifikasi bukti dan menyetujui Termin-2.
12. Admin Local mengunggah laporan final, RAB final, kuitansi, dan bukti final.
13. RTIK Pusat menyetujui Termin-3.
14. Admin mengecek eligibility sertifikat.
15. Sertifikat diterbitkan dan dapat diverifikasi publik.

