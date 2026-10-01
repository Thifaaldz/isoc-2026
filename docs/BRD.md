# BRD - SI-DSC Digital Safety Champions

## Ringkasan Bisnis

SI-DSC adalah sistem operasional untuk mengelola program Digital Safety Champions dari tahap persiapan lokus, pelaksanaan Training of Trainers (ToT), pelatihan siswa, monitoring capaian, bukti dukung, sertifikat, sampai validasi termin pembayaran.

Workflow baru memisahkan peran RTIK menjadi dua tingkat:

- Admin RTIK Local/Daerah: pengguna panel `admin`, bertanggung jawab mendaftarkan event/lokus, menginput data awal, mengelola pelaksanaan lapangan, dan mengunggah bukti dukung.
- Admin RTIK Pusat/Super Admin: pengguna panel `superadmin`, bertanggung jawab memverifikasi kelengkapan, kesesuaian data, approval termin pembayaran, dan validasi final program.

## Tujuan Bisnis

- Memastikan setiap lokus memiliki data sekolah/tempat, 100 peserta, 3 tutor/fasilitator, jadwal, RAB, dan bukti dukung yang lengkap.
- Membuat akun sistem untuk peserta dan tutor setelah pengajuan persiapan/Termin-1 disetujui Admin RTIK Pusat.
- Mengontrol pencairan payment terms dalam 3 termin berdasarkan kelengkapan dan verifikasi data.
- Memastikan tutor lulus ToT dengan nilai sempurna sebelum pelatihan lapangan.
- Memastikan siswa mengikuti pre-test, modul, kuis per modul, post-test, dan kewajiban pendukung sebelum sertifikat dapat dicetak.
- Menyediakan monitoring KPI lokasi, termasuk skor kuis, identifikasi ancaman, dan self-efficacy.

## Aktor Utama

- Admin RTIK Local/Daerah
- Admin RTIK Pusat/Super Admin
- Tutor/Fasilitator
- Peserta/Siswa
- Sekolah/Tempat Event
- Tim ISOC/RTIK sebagai pemangku kepentingan program

## Workflow Utama

### 1. Pendaftaran Event oleh Admin RTIK Local

Admin RTIK Local mendaftarkan event/lokus melalui wizard di panel admin. Wizard wajib mencakup:

1. Data sekolah/tempat event
2. Data peserta, termasuk import dari Excel
3. Data tutor/fasilitator
4. Rancangan biaya/RAB beserta kategori kegunaan biaya

Setelah data lengkap dan valid:

- Sistem membuat data event/lokus.
- Sistem membuat data sekolah/tempat pelaksanaan.
- Sistem menyimpan daftar calon peserta dan calon tutor sebagai data pengajuan.
- Sistem menyiapkan payment terms 3 termin dalam status menunggu/pending.
- Sistem menyimpan RAB awal sebagai dasar verifikasi termin.
- Admin RTIK Local hanya dapat melihat dan memperbarui event/lokus yang dia ajukan, tanpa hak delete.

### 2. Verifikasi Persiapan oleh Admin RTIK Pusat

Admin RTIK Pusat memeriksa kelengkapan aplikasi:

- Data sekolah/tempat event
- Data 100 peserta
- Data 3 tutor/fasilitator
- Jadwal pelatihan
- RAB dan kategori biaya
- Data calon peserta dan tutor siap dibuatkan akun
- Berita acara/surat kesiapan sekolah bila tersedia

Jika sesuai, Admin RTIK Pusat menyetujui pengajuan/Termin-1. Sistem membuat akun peserta dan tutor dengan password default `password`, email dari data import atau format nama lengkap `@isoc.id`, lalu menandai Termin-1 eligible. Jika belum sesuai, Admin RTIK Pusat wajib memberi catatan dan memilih status perbaikan atau cancel. Payment terms tetap terlihat bagi Admin RTIK Local sebagai status pending/eligible/paid/revisi beserta catatan pusat.

Desain sertifikat, template sertifikat, dan studio drag-and-drop hanya dikelola Admin RTIK Pusat/Super Admin. Admin RTIK Local fokus pada pengajuan event, koordinasi mitra/sekolah, materi, kuis, pre-test/post-test, RAB, dan bukti dukung.

### 3. ToT Tutor/Fasilitator

Setelah Termin-1:

- Tutor wajib mengikuti Training of Trainers (ToT).
- Tutor wajib mendapatkan nilai sempurna sesuai standar ToT.
- Tutor yang belum lulus sempurna tidak boleh melanjutkan ke pelatihan lapangan.
- Sistem mencatat status ToT untuk masing-masing tutor.

### 4. Pelatihan Lapangan

Setelah 3 tutor di lokus memenuhi syarat ToT:

- Pelatihan lapangan berjalan 180 menit, pukul 09.00-12.00 WIB.
- Tutor memandu 6 modul OTS.
- Siswa melakukan absensi digital menggunakan kode absensi.
- Tutor/Admin Local mengelola absensi basah/manual.
- Sistem menyediakan template absensi kering untuk diunduh dan dicetak.
- Siswa mempelajari modul dan mengerjakan kuis per modul.

Aturan pembelajaran:

- Post-test terkunci jika pre-test belum diisi.
- Sertifikat tidak dapat dicetak jika pre-test belum selesai.
- Sertifikat tidak dapat dicetak jika post-test belum selesai.
- Sertifikat tidak dapat dicetak jika kuis per modul belum lengkap.
- Sertifikat tidak dapat dicetak jika bukti dukung wajib belum diverifikasi sesuai aturan lokus.

### 5. Bukti Dukung Pelaksanaan

Setelah acara, tutor/fasilitator bersama Admin RTIK Local melengkapi bukti dukung:

- Foto kegiatan per sesi
- Video kegiatan/slogan
- Absensi basah/offline
- Bukti follow IG
- Bukti join WAG mentoring
- Bukti registrasi e-Certificate
- Bukti praktik microsite s.id

Admin RTIK Pusat memverifikasi kelengkapan data. Jika sesuai, Admin RTIK Pusat menyetujui Termin-2.

### 6. Verifikasi Final dan Termin-3

Setelah acara selesai, Admin RTIK Local mengunggah data sisa:

- RAB final
- Kuitansi pembelian
- Bukti pembelian banner/logistik
- Laporan akhir lokasi
- Berita acara serah terima
- Kelengkapan data final

Admin RTIK Pusat memverifikasi data final. Jika sesuai, Admin RTIK Pusat menyetujui Termin-3.

## Tanggung Jawab Tutor/Fasilitator

### Sebelum Pelatihan

- Mengikuti Training of Trainers (ToT) untuk kader/fasilitator lokal.
- Berkoordinasi dengan RTIK, sekolah, dan tim ISOC terkait jadwal, peserta, dan logistik.
- Membantu rekrutmen peserta 100 siswa per lokasi.
- Memastikan kesiapan sesi.
- Mempelajari 6 modul OTS dan instrumen pre-test/post-test.

### Saat Pelatihan

- Memandu ice breaking dan pembukaan.
- Menyampaikan 6 modul OTS:
  - Modul 1: Online scam
  - Modul 2: Perlindungan device
  - Modul 3: Informasi palsu
  - Modul 4: Peretasan media sosial
  - Modul 5: Internet aman
  - Modul 6: Penanganan insiden digital
- Memfasilitasi pre-test dan post-test.
- Mengelola kehadiran peserta digital dan manual.
- Memandu praktik microsite s.id, simulasi kasus, role-play, diskusi, open mic, dan refleksi.
- Menjaga mutu penyampaian sesuai kurikulum dan rundown standar.

### Setelah Pelatihan

- Melakukan dokumentasi wajib bersama Admin RTIK Local.
- Mengunggah foto, video slogan, dan absensi basah.
- Membentuk dan mendampingi WAG Mentoring serta kelompok belajar sebaya.
- Memonitor progress peserta dan KPI:
  - Skor rata-rata kuis minimal 85
  - Identifikasi simulasi ancaman minimal 82%
  - Self-efficacy baseline 55, saat pelatihan 70, setelah pendampingan 85
- Melaporkan hasil dan kendala ke RTIK/ISOC.

## Tanggung Jawab Admin RTIK Local

### Sebelum Kegiatan

- Koordinasi dengan sekolah, RTIK Pusat, dan ISOC.
- Rekrutmen dan validasi 100 peserta per lokasi.
- Rekrutmen dan validasi 3 tutor/fasilitator per lokasi.
- Input data sekolah, peserta, tutor, event, jadwal, dan RAB ke SI-DSC.
- Distribusi informasi, materi, dan logistik ke tutor/sekolah.
- Menyusun jadwal kegiatan bersama tutor dan sekolah.

### Saat Kegiatan

- Monitoring pelaksanaan sesuai rundown 180 menit dan 6 modul OTS.
- Mendukung administrasi tutor: absensi, registrasi, dokumentasi.
- Monitor kehadiran peserta dan kelengkapan pre-test/post-test.
- Mengumpulkan bukti dukung lokasi.
- Menjadi penghubung jika ada kendala teknis/administratif.

### Setelah Kegiatan

- Menyusun dan mengunggah bukti dukung lokasi ke SI-DSC.
- Memastikan checklist bukti dukung per lokasi terpenuhi.
- Membantu pembentukan WAG Mentoring dan kelompok belajar sebaya.
- Melaporkan capaian dan kendala lokasi ke RTIK Pusat/ISOC.
- Mendukung verifikasi bukti untuk Termin-2 dan Termin-3.
- Monitoring KPI lokasi.
- Export laporan lokasi.

## Checklist Bukti Dukung Per Lokasi

- Absensi basah daftar hadir fisik dengan tanda tangan peserta.
- Foto wajib dokumentasi kegiatan pelatihan.
- Video slogan ISOC dengan slogan "The Internet is for Everyone".
- Bukti follow IG `@isoc.id.jkt`.
- Bukti join WAG Mentoring.
- Bukti registrasi e-Certificate.

## Termin Pembayaran

### Termin-1 Persiapan Per Lokus

1. Data sekolah: nama, alamat, PIC, kontak.
2. Daftar 100 siswa per lokus: nama, NISN/NIS, kelas, kontak, email.
3. Daftar 3 tutor per lokus: nama, kontak, status ToT.
4. Jadwal pelatihan per lokus: tanggal, waktu, tempat.
5. Surat/MoU kesiapan sekolah.
6. Rencana logistik dan narasumber.
7. Data calon peserta dan tutor valid; akun SI-DSC dibuat setelah approval Termin-1.
8. Berita acara persiapan lokus.

### Termin-2 Pelaksanaan Per Lokus

- Absensi basah per lokus.
- Foto kegiatan per lokus.
- Video slogan ISOC "The Internet is for Everyone".
- Bukti follow IG `@isoc.id.jkt`.
- Bukti join WAG Mentoring.
- Bukti registrasi e-Certificate.
- Pre-test dan post-test 100 peserta per lokus.
- Rekap kehadiran 100 siswa dan 3 tutor.
- Bukti 6 modul OTS tersampaikan.
- Bukti praktik microsite s.id.
- Checklist bukti dukung lokus lengkap.

### Termin-3 Pasca Program/Verifikasi Final Per Lokus

- Verifikasi final semua bukti dukung per lokus.
- Laporan KPI per lokus.
- e-Certificate terbit untuk peserta eligible.
- Kelompok belajar sebaya terbentuk per lokus.
- WAG Mentoring aktif per lokus, target minimal 70 anggota/lokus.
- Laporan akhir lokus disetujui.
- Berita acara serah terima per lokus.
- Invoice dan kuitansi Termin-3.

## KPI Program

- 100 siswa per lokasi/lokus.
- 3 tutor/fasilitator per lokasi/lokus.
- 6 modul OTS tersampaikan.
- Skor rata-rata kuis minimal 85.
- Identifikasi simulasi ancaman minimal 82%.
- Self-efficacy meningkat dari 55 ke 70 saat pelatihan dan 85 setelah pendampingan.
- Bukti dukung per lokus lengkap dan terverifikasi.
- Sertifikat hanya terbit untuk peserta eligible.
