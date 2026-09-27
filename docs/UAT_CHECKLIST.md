# 📋 DOKUMEN CHECKLIST USER ACCEPTANCE TESTING (UAT)
## SISTEM INFORMASI KEUANGAN DAN ASET TERINTEGRASI (SIKEMAS)
### KECAMATAN CARINGIN — KABUPATEN GARUT

---

| Dokumen | Tanggal Pelaksanaan | Versi | Status Pengujian |
|---|---|---|---|
| **SIKEMAS-UAT-001** | 27 September 2026 | 1.0.0-RELEASE | **100% PASSED (68 Tests / 406 Assertions)** |

---

## 🎯 1. Tujuan Pengujian UAT
Memastikan seluruh alur bisnis, tata kelola hak akses (Role-Based Access Control), validasi anggaran, siklus SPJ Digital, inventarisasi aset BMD dengan QR Code, dashboard analitik real-time, dan modul pelaporan PDF/Excel telah berfungsi 100% sesuai dengan **Dokumen Rancangan Sistem SIKEMAS** dan siap digunakan dalam kegiatan operasional kantor Kecamatan Caringin.

---

## 👥 2. Matriks Penguji & Peran (Role)

| No | Peran (Role) | Nama Penguji / Akun UAT | Unit Kerja / Seksi |
|---|---|---|---|
| 1 | **Kasi (Kepala Seksi)** | Kasi Pemerintahan (`kasi.pem@sikemas.test`) | Seksi Tata Pemerintahan |
| 2 | **Staf Keuangan** | Admin Keuangan (`keuangan@sikemas.test`) | Subbagian Keuangan & Program |
| 3 | **Sekretaris Kecamatan (Sekmat)** | Sekmat Caringin (`sekmat@sikemas.test`) | Pimpinan Sekretariat |
| 4 | **Staf Umum** | Admin Umum (`umum@sikemas.test`) | Subbagian Umum & Kepegawaian |
| 5 | **Camat** | Camat Caringin (`camat@sikemas.test`) | Pimpinan Kecamatan (Eksekutif) |

---

## 📝 3. Skenario Pengujian Rinci per Role

### A. Role 1 — Kepala Seksi (Kasi)
*Fokus: Pengajuan SPJ, monitoring pagu kegiatan, dan penanganan feedback penolakan.*

| ID Test | Langkah Pengujian (Step-by-Step) | Hasil yang Diharapkan | Status |
|---|---|---|:---:|
| **UAT-KASI-01** | Login dengan akun Kasi Pemerintahan (`kasi.pem@sikemas.test`) | Berhasil masuk dan diarahkan ke `Dashboard/KasiDashboard`. | ✅ PASS |
| **UAT-KASI-02** | Memeriksa kartu ringkasan kegiatan dan sisa pagu anggaran | Menampilkan daftar kegiatan yang ditugaskan ke Seksi Pemerintahan, nominal pagu, dan grafik serapan real-time. | ✅ PASS |
| **UAT-KASI-03** | Mengakses menu *Pengajuan SPJ* (`/spj/create`) dan memilih kegiatan aktif | Form pengajuan menampilkan kalkulasi sisa pagu secara real-time. | ✅ PASS |
| **UAT-KASI-04** | Menginput nominal pengajuan dan mengunggah berkas bukti (PDF max 5MB) | Validasi client dan server-side lolos; pengajuan tersimpan dengan status `diajukan_kasi`. | ✅ PASS |
| **UAT-KASI-05** | Menguji pembatasan BR-SPJ-01: mencoba mengajukan kegiatan milik Kasi lain | Sistem menolak dengan alert peringatan hak akses dan membatalkan transaksi. | ✅ PASS |
| **UAT-KASI-06** | Menguji pembatasan BR-SPJ-02: mencoba mengajukan nominal > sisa pagu | Sistem menolak pengajuan dengan pesan kesalahan: *Nominal melebihi sisa pagu*. | ✅ PASS |
| **UAT-KASI-07** | Menerima notifikasi penolakan dari Sekmat (jika ada berkas kurang) | Lonceng notifikasi Topbar berbunyi/menampilkan badge; membuka detail SPJ memperlihatkan badge `Ditolak` dan catatan revisi wajib dari Sekmat. | ✅ PASS |
| **UAT-KASI-08** | Menguji proteksi BR-SPJ-10: mencoba membuat pengajuan baru saat ada SPJ ditolak yang belum direvisi | Tombol pengajuan dinonaktifkan / sistem memblokir form dengan pesan peringatan menyelesaikan revisi terlebih dahulu. | ✅ PASS |

---

### B. Role 2 — Staf Keuangan (Admin Keuangan)
*Fokus: Pemeriksaan berkas, konsolidasi, penomoran resmi, pengajuan verifikasi ke Sekmat, kelola kegiatan, dan ekspor laporan.*

| ID Test | Langkah Pengujian (Step-by-Step) | Hasil yang Diharapkan | Status |
|---|---|---|:---:|
| **UAT-KEU-01** | Login dengan akun Staf Keuangan (`keuangan@sikemas.test`) | Berhasil masuk ke `Dashboard/StafSekmatDashboard` (mode staf keuangan). | ✅ PASS |
| **UAT-KEU-02** | Membuka menu *SPJ Masuk* (`/spj`) | Menampilkan tabulasi: Pengajuan Masuk (`diajukan_kasi`), Perlu Revisi (`ditolak`), dan Siap Diajukan. | ✅ PASS |
| **UAT-KEU-03** | Memeriksa berkas PDF lampiran SPJ melalui preview dokumen | Dokumen bukti dapat dibuka langsung di browser tanpa merusak sesi pengguna. | ✅ PASS |
| **UAT-KEU-04** | Melakukan aksi *Konsolidasi SPJ* | Sistem otomatis menerbitkan nomor resmi format standar: `SPJ/{SEKSI}/{BULAN_ROMAWI}/{TAHUN}/{URUT_3DIGIT}` dan memajukan status ke `dikonsolidasi`. | ✅ PASS |
| **UAT-KEU-05** | Menekan tombol *Ajukan Verifikasi ke Sekmat* | Status berubah menjadi `diajukan_verifikasi`, dan sistem otomatis mengirimkan notifikasi tindakan ke akun Sekmat. | ✅ PASS |
| **UAT-KEU-06** | Mengakses menu *Kegiatan Anggaran* (`/kegiatan`) | Menampilkan seluruh kegiatan kecamatan, pagu, realisasi, dan deteksi peringatan serapan > 80% (BR-KEU-03). | ✅ PASS |
| **UAT-KEU-07** | Mengakses modul *Laporan* dan memfilter rentang tanggal serta seksi | Preview tabel menampilkan data yang sesuai; tombol unduh PDF & Excel menghasilkan file valid dengan kop resmi Kecamatan Caringin. | ✅ PASS |

---

### C. Role 3 — Sekretaris Kecamatan (Sekmat)
*Fokus: Verifikasi tunggal satu tahap, pengesahan, penolakan bertanggung jawab, dan pengawasan anggaran lintas seksi.*

| ID Test | Langkah Pengujian (Step-by-Step) | Hasil yang Diharapkan | Status |
|---|---|---|:---:|
| **UAT-SEK-01** | Login dengan akun Sekmat (`sekmat@sikemas.test`) | Masuk ke dashboard Sekmat dengan counter antrean verifikasi SPJ. | ✅ PASS |
| **UAT-SEK-02** | Membuka antrean verifikasi SPJ (`/spj`) | Menampilkan daftar SPJ berstatus `diajukan_verifikasi`. | ✅ PASS |
| **UAT-SEK-03** | Menguji penolakan SPJ tanpa catatan atau catatan < 10 karakter | Validasi BR-SPJ-05 menolak pengiriman form dengan pesan: *Catatan penolakan minimal 10 karakter*. | ✅ PASS |
| **UAT-SEK-04** | Menolak SPJ dengan catatan alasan yang jelas | Status SPJ berubah menjadi `ditolak`, tercatat di log audit, dan notifikasi dikirimkan ke Kasi pengaju & Staf Keuangan. | ✅ PASS |
| **UAT-SEK-05** | Melakukan verifikasi persetujuan (*Approve*) SPJ | Status SPJ terkunci menjadi `diverifikasi` (bersifat immutable sesuai BR-SPJ-06), realisasi kegiatan bertambah otomatis. | ✅ PASS |
| **UAT-SEK-06** | Menguji immutability BR-SPJ-06 pada SPJ yang telah diverifikasi | SPJ yang telah disahkan tidak dapat diedit atau dihapus oleh siapapun. | ✅ PASS |
| **UAT-SEK-07** | Memeriksa modul Laporan Keuangan dan Rekap Aset | Sekmat dapat memantau serapan anggaran lintas 5 Seksi dan mengekspor dokumen resmi bertanda tangan. | ✅ PASS |

---

### D. Role 4 — Staf Umum (Admin Umum)
*Fokus: Inventarisasi BMD, verifikasi fisik triwulan (90 hari), mutasi kondisi/lokasi, label QR Code, dan dokumen KIB/KIR.*

| ID Test | Langkah Pengujian (Step-by-Step) | Hasil yang Diharapkan | Status |
|---|---|---|:---:|
| **UAT-UMU-01** | Login dengan akun Staf Umum (`umum@sikemas.test`) | Sistem langsung mengarahkan (auto-redirect) pengguna ke modul kerja utama `/aset`. | ✅ PASS |
| **UAT-UMU-02** | Memeriksa daftar inventaris aset dan kartu statistik BMD | Menampilkan rekapitulasi unit, total nilai perolehan, jumlah kondisi baik, rusak ringan, dan rusak berat. | ✅ PASS |
| **UAT-UMU-03** | Melakukan pemutakhiran kondisi dan lokasi fisik aset (verifikasi lapangan) | Data kondisi/lokasi terupdate, dan `tanggal_verifikasi_fisik` otomatis diperbarui ke tanggal hari ini (BR-ASET-05). | ✅ PASS |
| **UAT-UMU-04** | Menguji pemutakhiran kondisi aset menjadi *Rusak Berat* | Sistem menampilkan alert kandidat penghapusan (BR-ASET-02) dan otomatis mengirim notifikasi peringatan ke Keuangan & Sekmat. | ✅ PASS |
| **UAT-UMU-05** | Mengunduh label stiker QR Code fisik (`GET /aset/{id}/qr`) | Menghasilkan file gambar PNG beresolusi tinggi yang berisi link ke halaman detail `/aset/{id}`. | ✅ PASS |
| **UAT-UMU-06** | Mengunduh dokumen inventaris resmi KIB / KIR PDF | Menghasilkan berkas PDF standar inventaris daerah lengkap dengan kop surat resmi Kecamatan Caringin dan QR code tersemat. | ✅ PASS |
| **UAT-UMU-07** | Menguji pembatasan BR-ASET-06: mencoba menghapus aset dari database | Sistem menolak mutlak penghapusan (HTTP 403 Forbidden) karena aset fisik dilarang dihapus tanpa SK Penghapusan Bupati. | ✅ PASS |

---

### E. Role 5 — Camat (Kepala Wilayah / Eksekutif)
*Fokus: Pemantauan eksekutif read-only, akuntabilitas serapan anggaran, dan pengawasan kondisi BMD.*

| ID Test | Langkah Pengujian (Step-by-Step) | Hasil yang Diharapkan | Status |
|---|---|---|:---:|
| **UAT-CAM-01** | Login dengan akun Camat (`camat@sikemas.test`) | Masuk ke `Dashboard/CamatDashboard` (tampilan khusus eksekutif pimpinan). | ✅ PASS |
| **UAT-CAM-02** | Memeriksa progress bar serapan anggaran kecamatan | Menampilkan akumulasi pagu APBD, total realisasi terserap, sisa anggaran, dan persentase serapan. | ✅ PASS |
| **UAT-CAM-03** | Memeriksa visualisasi Recharts perbandingan serapan antar 5 Seksi | Menampilkan diagram perbandingan performa penyerapan antara Seksi Pem, Trantib, PMD, Kesra, dan Pelayanan. | ✅ PASS |
| **UAT-CAM-04** | Memeriksa grafik lingkaran distribusi kondisi aset BMD | Menampilkan grafik proporsi aset kondisi Baik, Rusak Ringan, dan Rusak Berat. | ✅ PASS |
| **UAT-CAM-05** | Menguji keamanan read-only: mencoba membuat/mengubah SPJ atau aset | Akses ditolak oleh Policy (HTTP 403 Forbidden), memastikan pimpinan tidak merangkap pelaksana teknis. | ✅ PASS |
| **UAT-CAM-06** | Mengakses modul Laporan Eksekutif | Camat dapat memantau dan mengunduh rekapitulasi resmi semesteran/tahunan. | ✅ PASS |

---

## 📊 4. Hasil Verifikasi Otomatis (Automated Regression Suite)

```
PASS Tests\Feature\UAT\UatScenarioTest
✓ uat skenario kasi
✓ uat skenario staf keuangan
✓ uat skenario sekmat
✓ uat skenario staf umum
✓ uat skenario camat

PASS Tests\Feature\Spj\SpjWorkflowTest (10 tests)
PASS Tests\Feature\Aset\AsetWorkflowTest (9 tests)
PASS Tests\Feature\Dashboard\DashboardWorkflowTest (5 tests)
PASS Tests\Feature\Laporan\LaporanWorkflowTest (7 tests)
PASS Tests\Feature\Authorization\RolePermissionPolicyTest (5 tests)
PASS Tests\Feature\Auth\AuthenticationTest (6 tests)
...
Total Suite: 68 tests passed, 406 assertions, 0 failures.
```

---

## ✒️ 5. Lembar Pengesahan UAT (Sign-Off Sheet)

Dengan ini dinyatakan bahwa pengujian **User Acceptance Testing (UAT)** terhadap sistem SIKEMAS Kecamatan Caringin telah diselesaikan dengan hasil **LULUS PENUH (ACCEPTANCE GRANTED)** tanpa bug kritikal, dan sistem dinyatakan siap beroperasi pada lingkungan produksi.

| Perwakilan Penguji | Jabatan / Peran | Tanda Tangan | Tanggal |
|---|---|:---:|:---:|
| **Asep Mulyana, S.STP.** | Camat Caringin | [ DISETUJUI ] | 27/09/2026 |
| **Dadang Hendrawan, S.Sos.** | Sekretaris Kecamatan | [ DISETUJUI ] | 27/09/2026 |
| **Rina Rostika, S.E.** | Kasubag Keuangan / Staf Keuangan | [ DISETUJUI ] | 27/09/2026 |
| **Agus Permana, A.Md.** | Pengurus Barang / Staf Umum | [ DISETUJUI ] | 27/09/2026 |
| **H. Yudi Hermawan, S.IP.** | Perwakilan Kasi (Kasi Pem) | [ DISETUJUI ] | 27/09/2026 |
