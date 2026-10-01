# Workflow SI-DSC Saat Ini

Dokumen ini menjelaskan alur sistem yang sudah terpasang setelah perubahan workflow RTIK Local, RTIK Pusat, tutor, peserta, payment terms, ToT, bukti dukung, dan eligibility sertifikat.

## Ringkasan Status

| Area | Status | Catatan |
|---|---|---|
| Wizard event/lokus | Sudah ada | Di menu `Seminar & Materi > Seminar / Event`. |
| Data sekolah/tempat | Sudah ada | Bisa pilih sekolah existing atau buat sekolah baru dari wizard. |
| Data peserta | Sebagian | Data peserta bisa diinput di repeater dan file Excel bisa diupload sebagai arsip. Parsing Excel otomatis belum penuh. |
| Data tutor/fasilitator | Sebagian | Data tutor bisa diinput di repeater dan file Excel bisa diupload sebagai arsip. Parsing Excel otomatis belum penuh. |
| Auto-generate user | Sudah ada | Peserta/tutor dibuat otomatis dari data wizard. Email kosong dibuat menjadi format nama berbasis `@isoc.id`. |
| RAB dan kategori biaya | Sudah ada | Disimpan di event dan membuat payment terms 1-3. |
| Payment terms 3 termin | Sudah ada | Menu `Validasi & Sertifikat > Payment Terms`. |
| Verifikasi pusat | Sudah ada | Super Admin bisa verify Termin-1, Termin-2, Termin-3 dari action event/payment. |
| ToT tutor | Sudah ada | Menu `Tes & Nilai > ToT Tutor`. Nilai 100 dianggap lulus sempurna. |
| Gate ToT ke event | Sebagian | Event otomatis `tot_completed` jika semua tutor event nilai 100, tetapi pembuatan sesi lapangan belum hard-block. |
| Absensi kering | Sudah ada | Dari `Jadwal Sesi`, klik `Absensi Kering` untuk print/download. |
| Absensi digital kode | Sebagian | Event bisa generate kode absensi, tetapi halaman input kode peserta belum dibuat. |
| Modul dan materi | Sudah ada | Pertemuan dan materi bisa diatur admin. |
| Kuis per modul | Sudah ada | Assessment mendukung tipe `quiz`. |
| Pre-test/Post-test lock | Sudah ada | Kuis terkunci sebelum pre-test; post-test terkunci sebelum pre-test dan semua kuis selesai. |
| Bukti dukung | Sudah ada | Evidence mendukung absensi basah, foto sesi, video slogan, follow IG, WAG, e-certificate, microsite, RAB final, kuitansi, logistik, laporan akhir. |
| Sertifikat eligibility | Sudah ada | Sertifikat bisa dicek eligibility dan tidak bisa diterbitkan dari form jika belum eligible. |
| Reminder otomatis termin | Belum penuh | Field deadline/status sudah ada, scheduler/notifikasi otomatis belum dibuat. |
| Dashboard KPI final | Belum penuh | Data KPI tersedia sebagian, dashboard workflow lengkap masih perlu dibuat. |

## Alur Admin RTIK Local

1. Login ke panel admin: `https://isoc.test/admin/login`.
2. Buka `Seminar & Materi > Seminar / Event`.
3. Klik Create.
4. Isi wizard:
   - Step 1: Data sekolah/tempat, jadwal, target peserta, target tutor, dokumen persiapan.
   - Step 2: Data peserta atau upload file Excel peserta.
   - Step 3: Data tutor/fasilitator atau upload file Excel tutor.
   - Step 4: RAB dan kategori biaya.
5. Simpan event.
6. Sistem otomatis:
   - Membuat/menautkan peserta.
   - Membuat/menautkan tutor.
   - Membuat akun peserta/tutor bila belum ada.
   - Membuat payment terms Termin-1, Termin-2, Termin-3.
7. Klik action `Submit` pada event untuk mengirim aplikasi ke Admin RTIK Pusat.
8. Setelah pelaksanaan, Admin RTIK Local/Tutor upload bukti dukung di `Validasi & Sertifikat > Bukti Dukung`.
9. Upload dokumen final, RAB final, kuitansi, dan laporan akhir sebagai bukti dukung Termin-3.

## Alur Admin RTIK Pusat / Super Admin

1. Login ke panel superadmin: `https://isoc.test/superadmin/login`.
2. Buka `Seminar & Materi > Seminar / Event`.
3. Cek event dengan status `submitted`.
4. Jika data sekolah, peserta, tutor, jadwal, dan RAB sesuai, klik `Verify T1`.
5. Termin-1 di `Payment Terms` berubah menjadi eligible.
6. Pantau ToT tutor di `Tes & Nilai > ToT Tutor`.
7. Setelah bukti pelaksanaan lengkap, klik `Verify T2`.
8. Setelah laporan final dan kuitansi lengkap, klik `Verify T3`.
9. Cek sertifikat peserta di `Validasi & Sertifikat > e-Certificate`.
10. Klik `Cek Eligibility`, lalu `Terbitkan` jika eligible.

## Alur Tutor/Fasilitator

1. Login ke panel tutor: `https://isoc.test/tutor/login`.
2. Mengikuti ToT.
3. Admin/Super Admin menginput nilai ToT di `Tes & Nilai > ToT Tutor`.
4. Tutor dinyatakan lulus jika nilai ToT `100`.
5. Tutor membantu pelaksanaan sesi lapangan.
6. Tutor dapat membantu upload bukti dukung seperti foto sesi, video slogan, dan absensi basah.
7. Tutor memonitor hasil tes peserta melalui menu hasil tes/nilai.

## Alur Peserta

1. Login ke panel peserta: `https://isoc.test/peserta/login`.
2. Buka `Seminar & Materi > Modul & Tes`.
3. Pilih event yang diikuti.
4. Kerjakan pre-test.
5. Setelah pre-test selesai, peserta bisa mengerjakan kuis modul.
6. Setelah semua kuis modul selesai, peserta bisa mengerjakan post-test.
7. Sertifikat hanya bisa diterbitkan jika eligibility terpenuhi.

## Payment Terms

### Termin-1 Persiapan

- Data sekolah/tempat lengkap.
- 100 peserta valid.
- 3 tutor valid.
- Jadwal pelatihan lengkap.
- RAB awal lengkap.
- Akun peserta dan tutor aktif.
- Dokumen persiapan tersedia.

### Termin-2 Pelaksanaan

- ToT tutor lulus sempurna.
- Absensi basah.
- Foto kegiatan.
- Video slogan.
- Bukti follow IG.
- Bukti join WAG.
- Pre-test dan post-test lengkap.
- Bukti 6 modul tersampaikan.
- Praktik microsite s.id.

### Termin-3 Final

- RAB final.
- Kuitansi/invoice.
- Bukti pembelian logistik.
- Laporan KPI.
- Sertifikat peserta eligible.
- WAG Mentoring aktif.
- Laporan akhir disetujui.
- Berita acara serah terima.

## Eligibility Sertifikat

Peserta eligible jika:

- Pre-test selesai.
- Post-test selesai.
- Semua kuis modul selesai.
- Bukti dukung wajib event/lokus sudah approved:
  - Absensi basah
  - Foto wajib
  - Video slogan
  - Follow IG
  - Join WAG
  - Registrasi e-Certificate

Jika salah satu belum terpenuhi, certificate status akan `blocked`.

## Gap yang Masih Perlu Disempurnakan

1. Parsing Excel otomatis penuh untuk peserta/tutor.
2. Halaman input kode absensi digital untuk peserta.
3. Scheduler reminder otomatis payment terms.
4. Hard-block pembuatan sesi lapangan jika ToT belum sempurna.
5. Dashboard KPI workflow baru yang lengkap.
6. Validasi minimum 100 peserta dan 3 tutor secara hard validation di wizard.

