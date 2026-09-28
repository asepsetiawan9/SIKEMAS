# 📋 Catatan Pengembangan SIMPEL KAN v2.0 — Hasil Rapat 28 September 2026

> **Dokumen ini adalah panduan utama untuk AI/Developer dalam melakukan refactoring besar-besaran.**
> Semua instruksi di bawah ini bersifat **WAJIB** dan harus dipatuhi tanpa penafsiran sendiri.

---

## 🔴 PERUBAHAN PARADIGMA (CRITICAL)

### Kondisi Sebelumnya (v1.x)
Sistem dibangun sebagai **aplikasi keuangan yang sangat kompleks** dengan:
- State machine SPJ berlapis (draft → diajukan_kasi → dikonsolidasi → diajukan_verifikasi → diverifikasi/ditolak)
- Modul BMD/Aset dengan QR Code, KIB/KIR, verifikasi fisik
- Kalkulasi pagu, sisa anggaran, threshold 80%
- Dashboard Recharts penuh grafik analitik per role
- 6+ role dengan permission granular berbeda-beda
- Sistem nomor SPJ otomatis dengan format romawi
- Laporan PDF/Excel kompleks

### Kondisi Baru (v2.0 — Hasil Rapat)

> **"Saya punya data RAP/SPJ dan banyak bukti belanja. Saya ingin semua bukti tersebut tersimpan rapi berdasarkan belanjanya, bisa dilihat kembali dengan cepat, dan proses pemeriksaannya bisa dilakukan oleh Sekmat kemudian disetujui Camat."**

Sistem harus menjadi **arsip digital bukti belanja SPJ yang simpel**, dengan:
- Hierarki sederhana: **Program → Kegiatan → Sub Kegiatan → Uraian Belanja → Bukti Belanja**
- Fokus utama: **penyimpanan dan pencarian bukti/dokumen belanja digital**
- Alur verifikasi simpel: **Operator → Sekmat → Camat**
- Dashboard minimalis (statistik angka, bukan chart kompleks)
- **Fitur BMD/Aset: HIDDEN** (tidak dihapus, hanya disembunyikan dari UI & routes)

---

## 📐 ARSITEKTUR DATA BARU

### Hierarki Entitas (Top-Down)

```
Program (BARU)
   ↓ has many
Kegiatan (BARU — bukan kegiatan lama)
   ↓ has many
Sub Kegiatan (BARU)
   ↓ has many
Belanja / Uraian Belanja (BARU — pengganti SPJ lama)
   ↓ has many
Dokumen Bukti (BARU — multi-file per belanja)
```

### Tabel Database Baru yang HARUS Dibuat

#### 1. `program`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | Auto increment |
| `kode` | string, unique | Contoh: `7.01.06.01` |
| `nama` | string | Contoh: `Program Penunjang Urusan Pemerintahan Daerah Kabupaten/Kota` |
| `tahun_anggaran` | year, indexed | Tahun anggaran program |
| `deskripsi` | text, nullable | Keterangan tambahan |
| `created_by` | FK → users | Operator yang input |
| `timestamps` | | |

#### 2. `kegiatan_rap` (nama baru, hindari bentrok dengan `kegiatan` lama)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | Auto increment |
| `program_id` | FK → program | Parent program |
| `kode` | string | Contoh: `7.01.06.01.2.01` |
| `nama` | string | Contoh: `Perencanaan, Penganggaran, dan Evaluasi Kinerja Perangkat Daerah` |
| `deskripsi` | text, nullable | |
| `timestamps` | | |

#### 3. `sub_kegiatan`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | Auto increment |
| `kegiatan_rap_id` | FK → kegiatan_rap | Parent kegiatan |
| `kode` | string | Contoh: `7.01.01.2.01.0002` |
| `nama` | string | Contoh: `Koordinasi dan Penyusunan Dokumen RKA-SKPD` |
| `deskripsi` | text, nullable | |
| `timestamps` | | |

#### 4. `belanja` (core entity — pengganti tabel `spj` lama)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | Auto increment |
| `sub_kegiatan_id` | FK → sub_kegiatan | Parent sub kegiatan |
| `jenis_belanja` | string, indexed | Enum: `cetak`, `mamin`, `perdin`, `atk`, `lainnya` |
| `uraian` | string | Contoh: `Photo Copy B/W` |
| `spesifikasi` | text, nullable | Contoh: `800 lembar` |
| `harga_satuan` | decimal(15,2) | Harga per unit |
| `volume` | decimal(10,2), default 1 | Jumlah/kuantitas |
| `satuan` | string, nullable | Contoh: `lembar`, `orang`, `hari` |
| `total_nilai` | decimal(15,2) | = harga_satuan × volume |
| `tanggal_belanja` | date | Tanggal transaksi belanja |
| `status_dokumen` | string, default 'belum_lengkap' | `belum_lengkap`, `lengkap` |
| `status_verifikasi` | string, default 'draft' | `draft`, `diajukan`, `diverifikasi_sekmat`, `dikembalikan_sekmat`, `disetujui_camat`, `dikembalikan_camat` |
| `catatan_sekmat` | text, nullable | Catatan verifikasi/penolakan Sekmat |
| `catatan_camat` | text, nullable | Catatan persetujuan/penolakan Camat |
| `diajukan_pada` | datetime, nullable | Timestamp pengajuan ke Sekmat |
| `diverifikasi_sekmat_pada` | datetime, nullable | Timestamp verifikasi Sekmat |
| `disetujui_camat_pada` | datetime, nullable | Timestamp persetujuan Camat |
| `created_by` | FK → users | Operator yang input |
| `diverifikasi_oleh` | FK → users, nullable | Sekmat yang verifikasi |
| `disetujui_oleh` | FK → users, nullable | Camat yang approve |
| `timestamps` | | |

#### 5. `dokumen_bukti` (multi-file per belanja)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | Auto increment |
| `belanja_id` | FK → belanja | Parent belanja |
| `jenis_dokumen` | string, indexed | `nota`, `kwitansi`, `faktur`, `kontrak`, `lainnya` |
| `nama_dokumen` | string | Untuk jenis `lainnya`, user isi sendiri. Untuk standar: otomatis |
| `file_path` | string | Path ke file di storage |
| `file_name` | string | Nama file asli yang diupload |
| `file_size` | bigint | Ukuran file dalam bytes |
| `file_type` | string | MIME type (application/pdf, image/jpeg, dll) |
| `nomor_dokumen` | string, nullable | Nomor referensi pada dokumen |
| `uploaded_by` | FK → users | User yang upload |
| `timestamps` | | |

#### 6. `riwayat_proses` (audit trail per belanja)
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | bigint PK | Auto increment |
| `belanja_id` | FK → belanja | Belanja terkait |
| `aksi` | string | `input_data`, `upload_dokumen`, `ajukan_verifikasi`, `verifikasi_sekmat`, `tolak_sekmat`, `setujui_camat`, `tolak_camat`, `perbaikan`, `upload_ulang` |
| `keterangan` | text | Deskripsi lengkap apa yang dilakukan |
| `user_id` | FK → users | Pelaku aksi |
| `timestamps` | | |

---

## 🎭 ROLE & ALUR KERJA BARU

### Role yang Aktif

| Role | Tugas di Sistem Baru |
|------|---------------------|
| `super_admin` | Akses penuh seluruh sistem |
| `operator` | **(BARU — gabungan staf_keuangan + staf_umum)** Input Program/Kegiatan/SubKegiatan/Belanja, Upload bukti belanja, Ajukan verifikasi |
| `sekmat` | Verifikasi dokumen belanja, Approve/Kembalikan dengan catatan |
| `camat` | Persetujuan final, Approve/Kembalikan dengan catatan |

> **PENTING**: Role `kasi`, `staf_keuangan`, `staf_umum` dari sistem lama **digabung menjadi `operator`**.
> Jika Mr Zeps menginginkan pembagian lebih spesifik, ini bisa disesuaikan kemudian.

### Alur Verifikasi (State Machine Baru — SIMPEL)

```
                    ┌──────────────────────────────────┐
                    │                                  │
                    ▼                                  │
   ┌─────────┐  ┌──────────┐  ┌─────────────────────┐ │
   │  DRAFT  │→ │ DIAJUKAN │→ │ DIVERIFIKASI SEKMAT │ │
   └─────────┘  └──────────┘  └─────────────────────┘ │
       ▲             │                    │            │
       │             │                    ▼            │
       │             │         ┌──────────────────┐   │
       │             │         │ DISETUJUI CAMAT  │   │
       │             │         └──────────────────┘   │
       │             │                                │
       │             ▼                                │
       │    ┌────────────────────┐                    │
       └────│ DIKEMBALIKAN       │────────────────────┘
            │ (Sekmat / Camat)   │
            └────────────────────┘
```

**Transisi yang diizinkan:**
1. `draft` → `diajukan` (Operator mengajukan ke Sekmat)
2. `diajukan` → `diverifikasi_sekmat` (Sekmat approve)
3. `diajukan` → `dikembalikan_sekmat` (Sekmat kembalikan + catatan wajib)
4. `diverifikasi_sekmat` → `disetujui_camat` (Camat approve — **FINAL**)
5. `diverifikasi_sekmat` → `dikembalikan_camat` (Camat kembalikan + catatan wajib)
6. `dikembalikan_sekmat` → `draft` (Operator perbaiki lalu ajukan ulang)
7. `dikembalikan_camat` → `draft` (Operator perbaiki lalu ajukan ulang)

---

## 🖥️ HALAMAN & KOMPONEN UI BARU

### Navigasi Sidebar Baru

```
📊 Dashboard
📁 Program & Kegiatan
📝 Belanja & Bukti
   ├── Semua Belanja
   ├── Menunggu Verifikasi (Sekmat only)
   └── Menunggu Persetujuan (Camat only)
📋 Laporan
```

> **Aset BMD**: Semua menu terkait Aset BMD, KIB/KIR, QR Code **DIHAPUS dari sidebar**.
> Routes `/aset/*` tetap ada di backend tapi **tidak ditampilkan** di navigasi.

### Halaman yang HARUS Dibuat

#### 1. Dashboard (Simpel)
```
┌─────────────────────────────────────────────────┐
│  TOTAL BELANJA    BUKTI LENGKAP    BELUM LENGKAP │
│      125              98               27        │
├─────────────────────────────────────────────────┤
│  MENUNGGU SEKMAT  MENUNGGU CAMAT   DISETUJUI    │
│       15                8              80        │
├─────────────────────────────────────────────────┤
│  Daftar belanja terbaru...                      │
└─────────────────────────────────────────────────┘
```
- **BUKAN** chart Recharts yang kompleks
- Angka statistik besar yang jelas dan mudah dibaca
- Quick-access ke daftar belanja terbaru di bawahnya
- Warna: hijau = lengkap/setujui, kuning = menunggu, merah = belum lengkap

#### 2. Program & Kegiatan (`/program`)
- Tampilan hierarkis accordion/tree:
  - Program → expand → Kegiatan → expand → Sub Kegiatan
- CRUD untuk masing-masing level
- Operator bisa input Program, Kegiatan, dan Sub Kegiatan
- Format kode mengikuti nomenklatur pemerintah (contoh: `7.01.06.01`)

#### 3. Daftar Belanja (`/belanja`)
- Tabel daftar semua belanja dengan kolom:
  - No | Sub Kegiatan | Uraian | Jenis | Nilai | Status Dokumen | Status Verifikasi | Aksi
- **Status Dokumen** menampilkan badge:
  - ✅ Lengkap (hijau) / ❌ Belum Lengkap (merah) + jumlah dokumen
- **Filter & Pencarian** (WAJIB — ini fitur kunci):
  - Tahun anggaran
  - Program
  - Kegiatan
  - Sub Kegiatan
  - Jenis belanja (cetak/mamin/perdin/dll)
  - Status dokumen (lengkap/belum)
  - Status verifikasi
  - Tanggal (range picker)
  - Pencarian teks bebas (uraian, nomor dokumen)
- Pagination server-side

#### 4. Detail Belanja (`/belanja/{id}`)
Ini adalah **halaman terpenting** di seluruh aplikasi.

```
┌──────────────────────────────────────────────────────┐
│ DETAIL BELANJA                                       │
├──────────────────────────────────────────────────────┤
│ Program    : 7.01.06.01 Program Penunjang Urusan...  │
│ Kegiatan   : 7.01.06.01.2.01 Perencanaan...         │
│ Sub Keg.   : 7.01.01.2.01.0002 Koordinasi...        │
│ Uraian     : Photo Copy B/W                          │
│ Spesifikasi: 800 lembar                              │
│ Harga      : Rp300 × 800 = Rp240.000                │
│ Tanggal    : 15 September 2026                       │
├──────────────────────────────────────────────────────┤
│ BUKTI BELANJA                          Status: ❌     │
├──────────────────────────────────────────────────────┤
│ ✅ Nota           nota-fotokopi.pdf     [Lihat][Hapus]│
│ ✅ Kwitansi       kwitansi.pdf          [Lihat][Hapus]│
│ ✅ Faktur         faktur-fc.pdf         [Lihat][Hapus]│
│ ❌ Dokumen Kontrak   —                  [Upload]      │
│                                                      │
│ Dokumen Lainnya:                                     │
│ ✅ Surat Pesanan  sp-001.pdf            [Lihat][Hapus]│
│ ✅ BAST           bast-fc.pdf           [Lihat][Hapus]│
│                        [+ Tambah Dokumen Lainnya]    │
├──────────────────────────────────────────────────────┤
│ RIWAYAT PROSES                                       │
├──────────────────────────────────────────────────────┤
│ 28-09-2026 14:00  Operator memasukkan data           │
│ 28-09-2026 14:05  Operator upload nota dan kwitansi  │
│ 28-09-2026 15:00  Operator mengajukan verifikasi     │
│ 28-09-2026 16:30  Sekmat: "Kontrak belum ada"        │
│ 28-09-2026 17:00  Operator upload kontrak            │
│ 29-09-2026 09:00  Sekmat menyetujui                  │
│ 29-09-2026 10:00  Camat menyetujui                   │
├──────────────────────────────────────────────────────┤
│      [Ajukan Verifikasi]  (jika draft/dikembalikan)  │
│ atau                                                 │
│ [Setujui] [Kembalikan]    (jika role Sekmat/Camat)   │
└──────────────────────────────────────────────────────┘
```

#### 5. Form Input Belanja (`/belanja/create`)
- Dropdown cascading: Program → Kegiatan → Sub Kegiatan
- Input: uraian, spesifikasi, harga satuan, volume, satuan
- Auto-hitung total nilai
- Langsung bisa upload dokumen bukti dari form ini (opsional, bisa nanti)

#### 6. Upload Bukti Belanja (di dalam Detail Belanja)
- Per jenis dokumen: Nota, Kwitansi, Faktur, Kontrak, Lainnya
- Drag & drop atau klik upload
- Preview langsung setelah upload (PDF viewer / image viewer)
- Untuk "Dokumen Lainnya": ada input nama dokumen + file picker
- Accept: PDF, JPG, JPEG, PNG (max 10MB per file)

#### 7. Antrean Verifikasi Sekmat (`/verifikasi`)
- Daftar belanja yang statusnya `diajukan`
- Sekmat bisa klik → lihat detail + semua dokumen → Setujui / Kembalikan
- Jika kembalikan: **WAJIB** isi catatan (textarea, min 10 karakter)

#### 8. Antrean Persetujuan Camat (`/persetujuan`)
- Daftar belanja yang statusnya `diverifikasi_sekmat`
- Camat bisa klik → lihat detail + semua dokumen + catatan Sekmat → Setujui / Kembalikan
- Jika kembalikan: **WAJIB** isi catatan

---

## 🚫 FITUR YANG DI-HIDE (BUKAN DIHAPUS)

### Modul BMD / Aset
**Instruksi**: Semua file backend terkait Aset BMD **TETAP ADA** di codebase, tapi:

1. **Sidebar**: Hapus semua menu `Aset BMD`, `KIB`, `KIR` dari navigasi
2. **Routes**: Comment out atau wrap dengan `if(false)` routes `/aset/*` (agar tidak accessible via URL)
3. **Dashboard**: Hapus widget/card statistik aset dari semua dashboard
4. **Laporan**: Hapus tab "Rekap Aset" dari halaman laporan
5. **HandleInertiaRequests**: Hapus shared data terkait aset dari middleware

**File-file yang TIDAK dihapus** (tetap di codebase untuk kemungkinan aktivasi ulang):
- `app/Http/Controllers/AsetController.php`
- `app/Services/AsetService.php`
- `app/Repositories/AsetRepository.php`
- `app/Models/Aset.php`, `KibKir.php`
- `app/Policies/AsetPolicy.php`
- `app/Observers/AsetObserver.php`
- `resources/js/Pages/Aset/*`
- `database/migrations/*aset*`, `*kib_kir*`
- `tests/Feature/Aset/*`

---

## 🗄️ MAPPING DARI SISTEM LAMA KE BARU

| Komponen Lama | Aksi | Komponen Baru |
|---------------|------|---------------|
| `kegiatan` table | **TETAP** tapi tidak jadi parent SPJ lagi | Bisa digunakan sebagai referensi, atau di-hide juga |
| `spj` table | **REPLACE** dengan `belanja` table | Konsep berubah total |
| `SpjStatus` enum | **REPLACE** dengan `StatusVerifikasi` enum | State machine disederhanakan |
| `SpjService` | **REPLACE** dengan `BelanjaService` | Business logic baru |
| `SpjController` | **REPLACE** dengan `BelanjaController` | |
| `SpjRepository` | **REPLACE** dengan `BelanjaRepository` | |
| `SpjPolicy` | **REPLACE** dengan `BelanjaPolicy` | |
| `Pages/Spj/*` | **REPLACE** dengan `Pages/Belanja/*` | UI baru |
| `Pages/Kegiatan/*` | **REPLACE** dengan `Pages/Program/*` | Hierarki baru |
| `aset` table | **HIDE** | Tetap di DB, hide dari UI |
| `kib_kir` table | **HIDE** | Tetap di DB, hide dari UI |
| `arsip_digital` table | **EVALUASI** | Mungkin bisa reuse untuk `dokumen_bukti` |
| `DashboardService` | **REWRITE** | Dashboard simpel (angka, bukan chart) |
| Role `kasi` | **MERGE** → `operator` | Atau tetap jika Mr Zeps ingin |
| Role `staf_keuangan` | **MERGE** → `operator` | Atau tetap jika Mr Zeps ingin |
| Role `staf_umum` | **MERGE** → `operator` | Atau tetap jika Mr Zeps ingin |

---

## 🏗️ CLEAN ARCHITECTURE BARU

### Backend (Laravel)

```
app/
├── Enums/
│   ├── JenisBelanja.php          # BARU: cetak, mamin, perdin, atk, lainnya
│   ├── JenisDokumen.php          # BARU: nota, kwitansi, faktur, kontrak, lainnya
│   ├── StatusDokumen.php         # BARU: belum_lengkap, lengkap
│   ├── StatusVerifikasi.php      # BARU: draft, diajukan, diverifikasi_sekmat,
│   │                             #        dikembalikan_sekmat, disetujui_camat,
│   │                             #        dikembalikan_camat
│   ├── UserRole.php              # UPDATE: tambah operator, evaluasi role lama
│   └── ... (enum lama tetap ada)
│
├── Models/
│   ├── Program.php               # BARU
│   ├── KegiatanRap.php           # BARU
│   ├── SubKegiatan.php           # BARU
│   ├── Belanja.php               # BARU (pengganti Spj.php)
│   ├── DokumenBukti.php          # BARU
│   ├── RiwayatProses.php         # BARU
│   └── ... (model lama tetap ada, hide saja)
│
├── Http/Controllers/
│   ├── DashboardController.php   # REWRITE
│   ├── ProgramController.php     # BARU
│   ├── BelanjaController.php     # BARU (pengganti SpjController)
│   ├── DokumenBuktiController.php # BARU (upload/download/delete dokumen)
│   ├── VerifikasiController.php  # BARU (khusus antrean Sekmat & Camat)
│   └── ... (controller lama tetap ada)
│
├── Http/Requests/
│   ├── Belanja/
│   │   ├── StoreBelanjaRequest.php
│   │   └── UpdateBelanjaRequest.php
│   ├── Program/
│   │   ├── StoreProgramRequest.php
│   │   └── ...
│   └── DokumenBukti/
│       └── UploadDokumenRequest.php
│
├── Services/
│   ├── DashboardService.php      # REWRITE (statistik simpel)
│   ├── ProgramService.php        # BARU
│   ├── BelanjaService.php        # BARU
│   ├── DokumenBuktiService.php   # BARU
│   ├── VerifikasiService.php     # BARU
│   └── ... (service lama tetap)
│
├── Repositories/
│   ├── ProgramRepository.php     # BARU
│   ├── BelanjaRepository.php     # BARU
│   ├── DokumenBuktiRepository.php # BARU
│   └── ... (repo lama tetap)
│
└── Policies/
    ├── BelanjaPolicy.php         # BARU
    ├── ProgramPolicy.php         # BARU
    └── ... (policy lama tetap)
```

### Frontend (React/Inertia)

```
resources/js/Pages/
├── Dashboard/
│   └── Index.jsx                 # REWRITE — statistik angka simpel
│
├── Program/
│   ├── Index.jsx                 # BARU — tree/accordion Program → Kegiatan → Sub Kegiatan
│   └── Form.jsx                  # BARU — CRUD form per level
│
├── Belanja/
│   ├── Index.jsx                 # BARU — tabel belanja + filter canggih + pencarian
│   ├── Create.jsx                # BARU — form input belanja + cascading dropdown
│   ├── Edit.jsx                  # BARU — form edit belanja
│   └── Detail.jsx                # BARU — halaman detail + bukti + riwayat (**HALAMAN TERPENTING**)
│
├── Verifikasi/
│   ├── SekmatIndex.jsx           # BARU — antrean verifikasi Sekmat
│   └── CamatIndex.jsx            # BARU — antrean persetujuan Camat
│
├── Laporan/
│   └── Index.jsx                 # UPDATE — hanya laporan belanja, hapus tab aset
│
└── Aset/ (HIDE — folder tetap ada, tidak ditampilkan di navigasi)
```

---

## 📜 BUSINESS RULES BARU

### BR-DOK: Aturan Dokumen
| ID | Aturan |
|----|--------|
| BR-DOK-01 | Satu belanja bisa memiliki BANYAK dokumen bukti (one-to-many) |
| BR-DOK-02 | Jenis dokumen standar: Nota, Kwitansi, Faktur, Dokumen Kontrak |
| BR-DOK-03 | "Dokumen Lainnya" harus memiliki nama yang diisi oleh pengguna |
| BR-DOK-04 | File yang diterima: PDF, JPG, JPEG, PNG — maksimal 10MB per file |
| BR-DOK-05 | Status dokumen otomatis menjadi "Lengkap" jika minimal Nota + Kwitansi ada |
| BR-DOK-06 | Dokumen yang sudah diupload bisa diganti/upload ulang |
| BR-DOK-07 | Setiap upload/hapus dokumen dicatat di `riwayat_proses` |

### BR-VER: Aturan Verifikasi
| ID | Aturan |
|----|--------|
| BR-VER-01 | Operator hanya bisa mengajukan verifikasi jika minimal ada 1 dokumen bukti |
| BR-VER-02 | Sekmat WAJIB mengisi catatan jika mengembalikan (min 10 karakter) |
| BR-VER-03 | Camat WAJIB mengisi catatan jika mengembalikan (min 10 karakter) |
| BR-VER-04 | Status `disetujui_camat` bersifat **FINAL** — tidak bisa diubah lagi |
| BR-VER-05 | Belanja yang sudah `disetujui_camat`, dokumennya tidak bisa dihapus/diubah (locked) |
| BR-VER-06 | Operator bisa memperbaiki dan mengajukan ulang belanja yang dikembalikan |
| BR-VER-07 | Setiap transisi status dicatat di `riwayat_proses` |

### BR-CARI: Aturan Pencarian
| ID | Aturan |
|----|--------|
| BR-CARI-01 | Filter wajib tersedia: Tahun, Program, Kegiatan, Sub Kegiatan, Jenis Belanja, Tanggal, Status |
| BR-CARI-02 | Pencarian teks bebas mencakup: uraian belanja, nomor dokumen, spesifikasi |
| BR-CARI-03 | Hasil pencarian harus bisa langsung diklik untuk membuka detail beserta dokumennya |

---

## ⚡ PRIORITAS PENGEMBANGAN (FASE-FASE)

### Fase 1: Database & Foundation (PRIORITAS TERTINGGI)
1. Buat migrasi baru: `program`, `kegiatan_rap`, `sub_kegiatan`, `belanja`, `dokumen_bukti`, `riwayat_proses`
2. Buat Enums baru: `JenisBelanja`, `JenisDokumen`, `StatusDokumen`, `StatusVerifikasi`
3. Buat Models baru dengan relasi lengkap
4. Buat Repositories, Services, FormRequests
5. Update `UserRole` enum (tambah `operator`)
6. Buat Seeders dengan data contoh sesuai format rapat

### Fase 2: CRUD Program & Hierarki
1. `ProgramController` + `ProgramService` + `ProgramRepository`
2. UI: Halaman Program/Kegiatan/SubKegiatan dengan tree/accordion
3. CRUD lengkap untuk ketiga level

### Fase 3: Belanja & Upload Bukti (CORE FEATURE)
1. `BelanjaController` + `BelanjaService` + `BelanjaRepository`
2. `DokumenBuktiController` + `DokumenBuktiService`
3. UI: Form input belanja, detail belanja, upload/preview dokumen
4. Implementasi status kelengkapan dokumen otomatis
5. Pencarian & filter multi-kriteria

### Fase 4: Verifikasi & Persetujuan
1. `VerifikasiController` + `VerifikasiService`
2. UI: Antrean Sekmat, antrean Camat
3. Modal approve/kembalikan dengan catatan wajib
4. Riwayat proses (audit trail)

### Fase 5: Dashboard & Laporan
1. Rewrite Dashboard — statistik angka simpel
2. Update Laporan — hanya belanja (hapus tab aset)
3. Export PDF/Excel belanja

### Fase 6: Hide BMD & Cleanup
1. Sembunyikan semua menu Aset dari Sidebar
2. Comment out routes Aset
3. Hapus shared data Aset dari HandleInertiaRequests
4. Bersihkan dashboard dari widget aset
5. Testing regression

---

## 🎯 PRINSIP DESAIN UI — DARI HASIL RAPAT

> **"Tampilan utama jangan terlalu ramai."**
> **"User bisa cepat masuk ke data dan menemukan dokumen."**

1. **Statistik pakai angka besar** — bukan pie chart atau bar chart
2. **Akses cepat ke dokumen** — dari daftar klik langsung ke detail, dari detail langsung lihat file
3. **Status terlihat jelas** — badge warna (✅ hijau, ❌ merah, 🟡 kuning)
4. **Filter yang powerful tapi simpel** — dropdown dan search bar, bukan form filter yang panjang
5. **Upload yang intuitif** — drag & drop, preview langsung, progress indicator
6. **Riwayat yang transparan** — timeline vertikal sederhana, siapa-kapan-apa

---

## 🔒 KEAMANAN & OTORISASI

| Aksi | Operator | Sekmat | Camat | Super Admin |
|------|----------|--------|-------|-------------|
| Input Program/Kegiatan/SubKegiatan | ✅ | ❌ | ❌ | ✅ |
| Input Belanja | ✅ | ❌ | ❌ | ✅ |
| Upload Dokumen Bukti | ✅ | ❌ | ❌ | ✅ |
| Ajukan Verifikasi | ✅ | ❌ | ❌ | ✅ |
| Verifikasi (Approve/Kembalikan) | ❌ | ✅ | ❌ | ✅ |
| Persetujuan (Approve/Kembalikan) | ❌ | ❌ | ✅ | ✅ |
| Lihat Semua Data | ✅ | ✅ | ✅ | ✅ |
| Lihat Dokumen | ✅ | ✅ | ✅ | ✅ |
| Hapus Dokumen (sebelum final) | ✅ | ❌ | ❌ | ✅ |
| Akses Laporan | ✅ | ✅ | ✅ | ✅ |

---

## 📌 CATATAN KRITIS UNTUK AI

> [!CAUTION]
> **JANGAN membuat sistem yang terlalu kompleks.** Ini adalah pesan paling penting dari hasil rapat.
> Jangan menambahkan fitur yang tidak diminta. Jangan membuat kalkulasi anggaran yang rumit.
> Fokus pada: **INPUT DATA → UPLOAD BUKTI → CARI DOKUMEN → VERIFIKASI → SELESAI.**

> [!IMPORTANT]
> **Fitur BMD/Aset hanya di-HIDE, bukan dihapus.** Semua file backend tetap ada.
> Hanya navigasi, routes, dan shared data yang dinonaktifkan.

> [!NOTE]
> **Dokumen = File fisik yang di-digitalisasi.**
> Bukan metadata. Bukan checkbox "ada/tidak ada". Tapi file PDF/gambar yang benar-benar tersimpan di sistem.
> Inilah pembeda utama aplikasi ini: **bukti belanja tersimpan digital dan bisa dicari kembali.**

---

*Catatan ini dibuat berdasarkan hasil rapat tanggal 28 September 2026.*
*Diarsipkan sebagai panduan pengembangan SIMPEL KAN v2.0 — Kecamatan Caringin.*
