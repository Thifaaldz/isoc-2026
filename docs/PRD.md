# PRD - SI-DSC Digital Safety Champions

## 1. Tujuan Produk

SI-DSC harus mendukung workflow baru program Digital Safety Champions dari pendaftaran event/lokus oleh Admin RTIK Local, verifikasi Admin RTIK Pusat, ToT tutor, pelatihan siswa, monitoring modul/kuis/pre-test/post-test, pengelolaan bukti dukung, sertifikat, hingga payment terms 3 termin.

## 2. Role dan Panel

### Super Admin / Admin RTIK Pusat

- Panel: `superadmin`
- Verifikasi aplikasi event/lokus.
- Verifikasi kelengkapan data dan bukti dukung.
- Approval pengajuan event/lokus dengan keputusan approve, perbaikan, atau cancel disertai catatan.
- Approval payment terms Termin-1, Termin-2, dan Termin-3.
- Monitoring KPI lintas daerah/lokus.
- Kelola master data, desain sertifikat, dan laporan pusat.

### Admin RTIK Local / Daerah

- Panel: `admin`
- Mendaftarkan event/lokus.
- Import peserta dari Excel.
- Input tutor/fasilitator.
- Input RAB dan kategori biaya.
- Mengunggah bukti dukung.
- Melihat hanya event/lokus yang dia ajukan beserta status payment terms dan catatan pusat.
- Export laporan lokasi.

### Tutor/Fasilitator

- Panel: `tutor`
- Mengikuti ToT.
- Melaksanakan pelatihan lapangan.
- Mengelola absensi, monitoring peserta, modul, kuis, pre-test/post-test.
- Mengunggah bukti kegiatan bersama Admin RTIK Local.
- Melihat hanya event/lokus tempat dirinya ditugaskan.

### Peserta/Siswa

- Panel: `peserta`
- Mengakses modul.
- Mengisi pre-test.
- Mengerjakan kuis per modul.
- Mengisi post-test setelah memenuhi syarat.
- Mengakses sertifikat jika eligible.
- Melihat hanya event/lokus tempat dirinya terdaftar.

## 3. Modul Utama

1. Event/Lokus Wizard
2. Import Peserta Excel
3. Manajemen Tutor/Fasilitator
4. RAB dan Budget Category
5. Payment Terms 3 Termin
6. ToT Tutor
7. Absensi Digital dan Absensi Basah
8. Template Absensi Kering
9. Modul OTS dan Kuis Per Modul
10. Pre-test dan Post-test
11. Monitoring KPI dan Self-Efficacy
12. Bukti Dukung
13. Sertifikat dan Eligibility
14. Laporan dan Export

## 4. Event/Lokus Wizard

Admin RTIK Local harus mendaftarkan event/lokus menggunakan Filament Wizard. Referensi implementasi: Filament Forms Wizard 3.x.

Catatan implementasi:

- Gunakan `Filament\Forms\Components\Wizard`.
- Setiap step divalidasi sebelum lanjut.
- Submit akhir hanya tersedia pada step terakhir.
- Wizard dapat memakai `persistStepInQueryString()` agar admin tidak kehilangan posisi step.
- Step dapat memakai grid/columns agar form tidak terlalu panjang.

### Step 1 - Data Sekolah/Tempat Event

Field minimum:

- Nama sekolah/tempat
- NPSN jika sekolah
- Tipe sekolah/tempat
- Alamat
- Kota/kabupaten
- Provinsi
- PIC sekolah/tempat
- Kontak PIC
- Tanggal pelatihan
- Jam mulai dan selesai
- Target peserta
- Catatan kesiapan tempat
- Upload surat/MoU kesiapan sekolah jika tersedia

Output:

- Record sekolah/tempat dibuat atau diperbarui.
- Record event/lokus dibuat.

### Step 2 - Data Peserta

Fitur:

- Import dari Excel.
- Mapping kolom Excel.
- Preview data sebelum submit.
- Validasi minimal/target 100 peserta per lokus.
- Deteksi duplikasi nama, NIS/NISN, email, dan kontak.
- Data peserta tersimpan sebagai calon akun sampai pengajuan/Termin-1 disetujui Admin RTIK Pusat.

Field peserta minimum:

- Nama lengkap
- NIS/NISN
- Kelas
- Jenis kelamin
- Tanggal lahir jika tersedia
- Kontak
- Email jika tersedia
- Sekolah/lokus

Aturan generate akun setelah approval Termin-1:

- Jika email kosong, sistem membuat email dari nama lengkap dengan domain `@isoc.id`.
- Nama lengkap dinormalisasi menjadi slug email.
- Jika duplikat, sistem menambahkan angka urut.
- Role user: `peserta`.
- Password default user: `password`.
- Status user aktif setelah pengajuan/Termin-1 di-approve pusat.

### Step 3 - Data Tutor/Fasilitator

Fitur:

- Input manual tutor.
- Import tutor dari Excel bila dibutuhkan.
- Target 3 tutor/fasilitator per lokus.
- Data tutor tersimpan sebagai calon akun sampai pengajuan/Termin-1 disetujui Admin RTIK Pusat.

Field tutor minimum:

- Nama lengkap
- Kontak
- Email jika tersedia
- Institusi/lembaga
- Sekolah/lokus dampingan
- Status ToT
- Catatan fasilitator

Aturan generate akun setelah approval Termin-1:

- Jika email kosong, sistem membuat email dari nama lengkap dengan domain `@isoc.id`.
- Role user: `tutor`.
- Password default user: `password`.
- Tutor terhubung ke sekolah/lokus.

### Step 4 - RAB dan Kategori Biaya

Fitur:

- Input rancangan biaya.
- Kategori kegunaan biaya.
- Total otomatis.
- Upload dokumen pendukung awal jika tersedia.

Kategori awal:

- Konsumsi
- Transportasi
- ATK
- Banner/publikasi
- Dokumentasi
- Honor/narasumber
- Sewa/perlengkapan
- Lain-lain

Output:

- RAB awal tersimpan untuk verifikasi Termin-1.
- Payment terms awal dibuat dalam status pending.
- Status aplikasi event/lokus dapat disubmit oleh Admin RTIK Local untuk review pusat.

## 5. Status Lifecycle Event/Lokus

Status minimum:

- Draft
- Submitted by Local Admin
- Verified for Termin-1
- ToT In Progress
- ToT Completed
- Field Training Scheduled
- Field Training Completed
- Evidence Submitted
- Verified for Termin-2
- Final Report Submitted
- Verified for Termin-3
- Closed
- Rejected/Needs Revision
- Cancelled by Pusat

Aturan approval:

- Admin RTIK Pusat dapat approve pengajuan/Termin-1 jika data sesuai.
- Admin RTIK Pusat dapat mengembalikan pengajuan ke status perbaikan dengan catatan wajib.
- Admin RTIK Pusat dapat membatalkan pengajuan dengan alasan/catatan wajib.
- Admin RTIK Local tidak dapat delete event/lokus; perubahan data yang disimpan tetap terlihat oleh Admin RTIK Pusat.

## 6. Payment Terms

Payment terms harus diaktifkan kembali dengan 3 termin.

### Termin-1 Persiapan

Syarat approval:

- Data sekolah/tempat lengkap.
- 100 peserta valid.
- 3 tutor valid.
- Jadwal pelatihan lengkap.
- Surat/MoU kesiapan sekolah jika diwajibkan.
- RAB awal lengkap.
- Data calon peserta dan tutor valid.
- Berita acara persiapan tersedia jika diwajibkan.

Fitur:

- Checklist termin.
- Upload dokumen.
- Approval Admin RTIK Pusat.
- Akun peserta dan tutor dibuat otomatis saat approval Termin-1.
- Reminder Termin-1 jika belum lengkap.

### Termin-2 Pelaksanaan

Syarat approval:

- ToT tutor selesai dan lulus sempurna.
- Absensi basah terunggah.
- Foto kegiatan terunggah.
- Video slogan terunggah.
- Bukti follow IG terunggah.
- Bukti join WAG terunggah.
- Bukti registrasi e-Certificate terunggah.
- Pre-test dan post-test peserta lengkap.
- Rekap kehadiran siswa dan tutor tersedia.
- Bukti 6 modul tersampaikan.
- Bukti praktik microsite s.id.

Fitur:

- Checklist termin.
- Review bukti dukung.
- Approval/revision/reject.
- Reminder Termin-2.

### Termin-3 Final

Syarat approval:

- Verifikasi final bukti dukung.
- RAB final terunggah.
- Kuitansi dan invoice terunggah.
- Bukti pembelian logistik seperti banner terunggah.
- Laporan KPI per lokus lengkap.
- Sertifikat peserta eligible terbit.
- Kelompok belajar sebaya terbentuk.
- WAG Mentoring aktif.
- Laporan akhir disetujui.
- Berita acara serah terima tersedia.

Fitur:

- Checklist termin.
- Approval Admin RTIK Pusat.
- Reminder Termin-3.
- Export laporan final.

## 7. ToT Tutor/Fasilitator

Requirement:

- Tutor wajib mengikuti ToT sebelum pelatihan lapangan.
- Nilai ToT harus sempurna.
- Jika nilai ToT belum sempurna, status tutor belum eligible untuk field training.
- Pelatihan lapangan baru dapat diproses jika 3 tutor lokus sudah eligible.

Data ToT:

- Tutor
- Event/lokus
- Materi ToT
- Nilai
- Status lulus
- Tanggal lulus
- Catatan evaluator

## 8. Pelatihan Lapangan

Rundown standar:

- Durasi 180 menit.
- Waktu default 09.00-12.00 WIB.
- Ice breaking dan pembukaan.
- Penyampaian 6 modul OTS.
- Pre-test dan post-test.
- Praktik microsite s.id.
- Simulasi kasus, role-play, diskusi, open mic, refleksi.

Modul OTS:

1. Online scam
2. Perlindungan device
3. Informasi palsu
4. Peretasan media sosial
5. Internet aman
6. Penanganan insiden digital

## 9. Modul, Kuis, Pre-test, dan Post-test

Requirement:

- Setiap modul dapat memiliki materi PPT/PDF/link/video.
- Setiap modul memiliki kuis pilihan ganda.
- Peserta wajib mengerjakan kuis per modul.
- Pre-test wajib dikerjakan sebelum post-test.
- Post-test terkunci jika pre-test belum diisi.
- Sertifikat terkunci jika salah satu kuis modul belum selesai.
- Sertifikat terkunci jika pre-test/post-test belum selesai.

Data kuis:

- Modul
- Pertanyaan
- Pilihan jawaban
- Jawaban benar
- Skor
- Attempt peserta
- Status selesai

## 10. Absensi

### Absensi Digital

- Tutor/Admin Local membuat kode absensi.
- Peserta melakukan absensi menggunakan kode.
- Sistem mencatat waktu absen.
- Sistem menampilkan rekap hadir/izin/sakit/alpa.

### Absensi Basah

- Sistem menyediakan template absensi kering untuk diunduh dan dicetak.
- Template berisi data event, sekolah, tanggal, daftar peserta, kolom tanda tangan, dan kolom catatan.
- Setelah ditandatangani, Admin Local/Tutor mengunggah scan/foto absensi basah.

## 11. Bukti Dukung

Jenis bukti dukung minimum:

- Absensi basah
- Foto kegiatan
- Video slogan ISOC "The Internet is for Everyone"
- Bukti follow IG `@isoc.id.jkt`
- Bukti join WAG Mentoring
- Bukti registrasi e-Certificate
- Bukti praktik microsite s.id
- Bukti 6 modul tersampaikan
- RAB final
- Kuitansi/invoice
- Bukti pembelian logistik
- Laporan akhir

Status bukti:

- Pending
- Approved
- Rejected
- Needs Revision

## 12. Sertifikat dan Eligibility

Sertifikat hanya dapat dicetak jika:

- Peserta hadir sesuai syarat minimal.
- Pre-test selesai.
- Post-test selesai.
- Semua kuis modul selesai.
- Syarat bukti dukung lokus terpenuhi sesuai konfigurasi.
- Status peserta eligible.
- Sertifikat event/lokus sudah diizinkan Admin RTIK Pusat.

Sistem harus menyediakan:

- Desain sertifikat.
- Template sertifikat.
- Nomor sertifikat.
- QR/verifikasi sertifikat.
- Status sertifikat pending/issued/revoked.

## 13. Monitoring KPI

KPI yang harus ditampilkan:

- Jumlah peserta per lokus.
- Jumlah tutor per lokus.
- Status ToT tutor.
- Persentase kehadiran.
- Progress pre-test.
- Progress post-test.
- Progress kuis per modul.
- Skor rata-rata kuis minimal 85.
- Identifikasi simulasi ancaman minimal 82%.
- Self-efficacy baseline 55, saat pelatihan 70, setelah pendampingan 85.
- Status bukti dukung.
- Status payment terms.
- Status sertifikat.

## 14. Laporan dan Export

Admin RTIK Local dapat export:

- Data peserta.
- Data tutor.
- Rekap absensi.
- Rekap pre-test/post-test.
- Rekap kuis modul.
- Rekap KPI lokasi.
- Checklist bukti dukung.
- Laporan termin.

Admin RTIK Pusat dapat export:

- Laporan lintas daerah/lokus.
- Rekap payment terms.
- Rekap KPI nasional/program.
- Rekap sertifikat.
- Rekap bukti dukung.

## 15. Acceptance Criteria

- Admin RTIK Local dapat membuat event/lokus melalui wizard 4 step.
- Sistem dapat import peserta dari Excel dan membuat akun peserta otomatis.
- Sistem dapat membuat akun tutor otomatis.
- Admin RTIK Pusat dapat memverifikasi Termin-1, Termin-2, dan Termin-3.
- Reminder payment terms berjalan untuk termin yang belum lengkap.
- Tutor tidak dapat melanjutkan field training jika ToT belum lulus sempurna.
- Post-test terkunci jika pre-test belum selesai.
- Sertifikat terkunci jika pre-test, post-test, atau kuis modul belum lengkap.
- Tutor dapat memonitor progress peserta.
- Admin Local dapat mengunduh template absensi kering.
- Admin Local/Tutor dapat mengunggah absensi basah dan bukti dukung.
- Admin RTIK Pusat dapat melakukan approval/revision/reject bukti dukung.
- Self-efficacy tetap tercatat dan termonitor dalam KPI.

## 16. Catatan Implementasi

- Gunakan Filament Wizard untuk pendaftaran event/lokus.
- Gunakan validasi per step untuk mencegah data parsial yang tidak valid.
- Import Excel harus memiliki preview dan mapping kolom.
- Pembuatan email otomatis harus menangani duplikasi.
- Semua checklist termin harus audit-able: siapa yang submit, siapa yang approve, waktu approve, catatan revisi.
- Payment terms harus memiliki reminder berbasis status dan deadline.
- Sertifikat harus memakai eligibility gate agar tidak bisa dicetak sebelum syarat terpenuhi.
