# 📋 CHECKLIST ONBOARDING PENGGUNA BARU SIKEMAS
## KECAMATAN CARINGIN — KABUPATEN GARUT

---

Checklist ini wajib diikuti oleh Subbagian Umum & Kepegawaian serta Subbagian Keuangan setiap kali ada pegawai/pejabat baru yang bertugas di lingkungan Pemerintah Kecamatan Caringin.

---

## 👤 Informasi Pegawai Baru

| Atribut | Keterangan Data |
|---|---|
| **Nama Lengkap & Gelar** | ................................................................ |
| **NIP (18 Digit)** | ................................................................ |
| **Pangkat / Golongan** | ................................................................ |
| **Jabatan Dinas** | ................................................................ |
| **Unit Kerja / Seksi** | [ ] Pemerintahan [ ] Trantib [ ] PMD [ ] Kesra [ ] Pelayanan [ ] Sekretariat |
| **Role SIKEMAS** | [ ] Kasi [ ] Staf Keuangan [ ] Sekmat [ ] Staf Umum [ ] Camat |
| **Tanggal Efektif Bertugas**| ..... / ..... / 2026 |

---

## 🛠️ Tahap 1: Administrasi & Pembuatan Akun Sistem

Dilakukan oleh Administrator Sistem (Staf Umum / Keuangan):

- [ ] **1. Verifikasi SK Penempatan / Mutasi**: Memastikan Surat Keputusan penempatan atau pelantikan pejabat telah diverifikasi.
- [ ] **2. Pendaftaran Pengguna**:
  - Input NIP resmi (18 digit tanpa spasi).
  - Input Nama lengkap dengan gelar.
  - Input Email kedinasan / resmi kecamatan.
  - Set status `is_active = true`.
- [ ] **3. Penetapan Role & Seksi (RBAC)**:
  - Berikan peran (*Role*) yang sesuai: `kasi`, `staf_keuangan`, `sekmat`, `staf_umum`, atau `camat`.
  - Jika role adalah **Kasi**, wajib tetapkan atribut `seksi`: `pemerintahan`, `trantib`, `pmd`, `kesra`, atau `pelayanan`.
- [ ] **4. Penyerahan Kredensial Awal**:
  - Password default acak yang aman diserahkan secara tertutup kepada pegawai yang bersangkutan.
  - Berikan tautan resmi aplikasi: `https://sikemas.caringin.go.id`.

---

## 📖 Tahap 2: Sosialisasi SOP & Modul Kerja

Dilakukan oleh Mentor / Pendamping (Kasubag Terkait):

- [ ] **1. Penyerahan Dokumen SOP**:
  - Berikan salinan digital dokumen [SOP Penggunaan SIKEMAS](file:///docs/SOP_PENGGUNAAN_SIKEMAS.md).
  - Jelaskan alur kerja spesifik sesuai peran pegawai (contoh: Kasi memahami alur pengajuan SPJ dan batasan pagu; Staf Umum memahami alur label QR & KIR).
- [ ] **2. Penjelasan Business Rules Penting**:
  - Batasan nominal belanja tidak boleh melebihi sisa pagu (BR-SPJ-02).
  - Ketentuan SPJ Ditolak wajib diselesaikan sebelum pengajuan baru (BR-SPJ-10).
  - Dokumen SPJ yang telah diverifikasi bersifat kekal / tidak dapat dihapus (BR-SPJ-06).
  - Seluruh aset dilarang dihapus tanpa SK Bupati (BR-ASET-06).
  - Kewajiban verifikasi fisik aset berkala tiap 90 hari (BR-ASET-05).

---

## 💻 Tahap 3: Uji Coba Simulasi (Hands-On di Staging)

Pegawai baru mempraktikkan langsung alur kerja:

- [ ] **1. Login & Pembaruan Password**:
  - Pegawai berhasil masuk ke aplikasi.
  - Melakukan penggantian password awal ke password pribadi pada menu *Profil*.
- [ ] **2. Eksplorasi Dashboard**:
  - Memastikan kartu statistik dan data anggaran/kegiatan yang muncul relevan dengan Seksi/Tugasnya.
- [ ] **3. Simulasi Transaksi Kerja**:
  - *Jika Kasi:* Simulasi mengisi form pengajuan SPJ, mengunggah file bukti PDF, dan mengecek status antrean.
  - *Jika Staf Keuangan:* Simulasi review berkas bukti, menjalankan konsolidasi (penomoran otomatis), dan mengajukan verifikasi ke Sekmat.
  - *Jika Sekmat:* Simulasi memeriksa kelengkapan SPJ, memberikan catatan penolakan jika berkas kurang, dan menyetujui SPJ.
  - *Jika Staf Umum:* Simulasi mendaftar aset baru, mengunduh file QR PNG, mencetak KIB/KIR, dan memperbarui status kondisi/lokasi fisik.
  - *Jika Camat:* Eksplorasi diagram penyerapan per seksi dan grafik kondisi aset di dashboard eksekutif.

---

## 🛡️ Tahap 4: Pernyataan Kepatuhan Keamanan Data

- [ ] Pegawai memahami bahwa akun SIKEMAS bersifat personal dan **DILARANG KERAS** membagikan username & password kepada pihak lain.
- [ ] Pegawai menyetujui bahwa seluruh aksi pencatatan, perubahan status SPJ, dan mutasi aset terekam secara permanen pada jejak audit (`log_aktivitas`) lengkap dengan alamat IP dan stempel waktu.

---

## ✒️ Lembar Tanda Tangan Selesai Onboarding

| Pegawai Baru | Pendamping / Kasubag |
|:---:|:---:|
| <br><br><br> ( .................................................... ) <br> NIP. | <br><br><br> ( .................................................... ) <br> NIP. |
| Tanggal: ..... / ..... / 2026 | Tanggal: ..... / ..... / 2026 |
