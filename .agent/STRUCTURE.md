# 🏛️ SIKEMAS Architecture & Structure Map

Sistem Informasi Keuangan dan Aset Terintegrasi — Kecamatan Caringin

## 📁 System Architecture Overview

```
pkp-caringin/
├── .agent/
│   ├── STRUCTURE.md            # Peta navigasi arsitektur dan modul
│   └── PROJECT_LOG.md          # Timeline atomic log aktivitas AI
├── app/
│   ├── Enums/                  # PHP 8.2 Backed Enums type-safe
│   │   ├── UserRole.php        # Updated: added OPERATOR
│   │   ├── JenisBelanja.php    # V2: cetak, mamin, perdin, atk, lainnya
│   │   ├── JenisDokumen.php    # V2: nota, kwitansi, faktur, kontrak, lainnya
│   │   ├── StatusDokumen.php   # V2: belum_lengkap, lengkap
│   │   ├── StatusVerifikasi.php# V2: draft, diajukan, diverifikasi_sekmat, dikembalikan_sekmat, disetujui_camat, dikembalikan_camat
│   │   ├── SpjStatus.php       # Legacy V1
│   │   ├── KondisiAset.php     # Legacy V1 (BMD Hidden)
│   │   ├── JenisKibKir.php     # Legacy V1 (BMD Hidden)
│   │   ├── SeksiType.php
│   │   ├── SumberDana.php
│   │   ├── StatusKegiatan.php
│   │   ├── CaraPerolehan.php
│   │   └── NotifikasiTipe.php
│   ├── Exceptions/             # Custom Domain Exceptions (BR Enforcement)
│   │   ├── SpjStatusTransitionException.php
│   │   ├── PaguExceededException.php
│   │   └── PendingRejectedSpjException.php
│   ├── Http/
│   │   ├── Controllers/        # Request handling & Inertia responses
│   │   │   ├── Auth/           # Breeze authentication controllers
│   │   │   ├── DashboardController.php # V2: simple statistics metrics
│   │   │   ├── ProgramController.php   # V2: CRUD Program/Kegiatan/SubKegiatan
│   │   │   ├── BelanjaController.php   # V2: CRUD Belanja & multi-filter
│   │   │   ├── DokumenBuktiController.php # V2: Multi-file evidence manager
│   │   │   ├── VerifikasiController.php   # V2: Verifikasi Sekmat & Persetujuan Camat
│   │   │   ├── SpjController.php       # Legacy V1
│   │   │   ├── KegiatanController.php  # Legacy V1
│   │   │   ├── AsetController.php      # Legacy V1 (BMD Hidden)
│   │   │   ├── LaporanController.php
│   │   │   └── NotifikasiController.php
│   │   ├── Middleware/
│   │   │   ├── CheckUserActive.php       # Inactive user guard
│   │   │   └── HandleInertiaRequests.php # Shared auth, badges & flash
│   │   ├── Requests/           # Form validation & authorization
│   │   │   ├── StoreProgramRequest.php     # V2
│   │   │   ├── StoreKegiatanRapRequest.php # V2
│   │   │   ├── StoreSubKegiatanRequest.php # V2
│   │   │   ├── StoreBelanjaRequest.php     # V2
│   │   │   ├── UpdateBelanjaRequest.php    # V2
│   │   │   ├── UploadDokumenRequest.php    # V2
│   │   │   ├── StoreSpjRequest.php
│   │   │   ├── VerifikasiSpjRequest.php
│   │   │   ├── Kegiatan/
│   │   │   │   ├── StoreKegiatanRequest.php
│   │   │   │   └── UpdateKegiatanRequest.php
│   │   │   └── Aset/
│   │   │       ├── StoreAsetRequest.php
│   │   │       └── UpdateAsetRequest.php
│   │   └── Resources/          # API/Inertia data transformation
│   ├── Models/                 # Eloquent models, casts & relations
│   │   ├── Program.php         # V2: Hierarki Level 1
│   │   ├── KegiatanRap.php     # V2: Hierarki Level 2
│   │   ├── SubKegiatan.php     # V2: Hierarki Level 3
│   │   ├── Belanja.php         # V2: Entitas utama Uraian Belanja
│   │   ├── DokumenBukti.php    # V2: Multi-file bukti per belanja
│   │   ├── RiwayatProses.php   # V2: Audit trail verifikasi & status
│   │   ├── User.php
│   │   ├── Kegiatan.php        # Legacy V1
│   │   ├── Spj.php             # Legacy V1
│   │   ├── Aset.php            # Legacy V1 (BMD Hidden)
│   │   ├── KibKir.php          # Legacy V1 (BMD Hidden)
│   │   ├── ArsipDigital.php
│   │   ├── Notifikasi.php
│   │   ├── Pengaturan.php
│   │   └── LogAktivitas.php
│   ├── Repositories/           # Database abstraction layer
│   │   ├── Contracts/          # Repository interfaces
│   │   ├── ProgramRepository.php      # V2: Program, KegiatanRap, SubKegiatan
│   │   ├── BelanjaRepository.php      # V2: Belanja filtering, antrean & stats
│   │   ├── DokumenBuktiRepository.php # V2: Dokumen CRUD & lock check
│   │   ├── KegiatanRepository.php
│   │   ├── SpjRepository.php
│   │   ├── AsetRepository.php
│   │   └── NotifikasiRepository.php
│   ├── Services/               # Pure business logic layer
│   │   ├── ProgramService.php      # V2: Hierarki CRUD & safe deletion
│   │   ├── BelanjaService.php      # V2: Belanja state machine & workflow
│   │   ├── DokumenBuktiService.php # V2: Upload/delete with BR enforcement
│   │   ├── DashboardService.php    # Real-time multi-role dashboard analytics
│   │   ├── LaporanService.php      # Filtered reporting, PDF & Excel export
│   │   ├── KegiatanService.php
│   │   ├── SpjService.php
│   │   ├── AsetService.php
│   │   └── NotifikasiService.php
│   ├── Exports/                # Maatwebsite Excel spreadsheet exports
│   │   ├── LaporanKeuanganExport.php
│   │   └── RekapAsetExport.php
│   ├── Policies/               # Granular authorization
│   │   ├── ProgramPolicy.php       # V2
│   │   ├── BelanjaPolicy.php       # V2
│   │   ├── SpjPolicy.php
│   │   ├── AsetPolicy.php
│   │   └── KegiatanPolicy.php
│   └── Observers/              # Side-effects & audit trail
│       ├── SpjObserver.php
│       └── AsetObserver.php
├── database/
│   ├── factories/              # Eloquent model factories
│   │   ├── UserFactory.php
│   │   └── AsetFactory.php
│   ├── migrations/             # Database schema migrations
│   └── seeders/                # Database seeders (Users, Kegiatan, Aset, Pengaturan)
├── resources/
│   ├── views/
│   │   ├── exports/            # Excel Blade view templates
│   │   │   ├── laporan_keuangan.blade.php
│   │   │   └── rekap_aset.blade.php
│   │   └── pdf/                # DomPDF Blade templates
│   │       ├── kib.blade.php   # Kartu Inventaris Barang (KIB)
│   │       ├── kir.blade.php   # Kartu Inventaris Ruangan (KIR)
│   │       ├── laporan_keuangan.blade.php # Laporan Realisasi Keuangan & SPJ
│   │       └── rekap_aset.blade.php       # Rekapitulasi BMD
│   └── js/                     # Frontend Inertia + React (Atomic Design)
│       ├── Components/         # Sidebar, Topbar, StatusBadge, KondisiBadge, ConfirmModal, EmptyState, LoadingSkeleton, QrDownloadButton
│       ├── Layouts/            # AuthenticatedLayout, GuestLayout
│       └── Pages/              # Role-specific Dashboards (Recharts), Spj, Aset, Laporan (Index)
├── app/Console/Commands/       # Artisan automation commands
│   ├── BackupDatabaseCommand.php # Scheduled daily backup 02:00 WIB with 30-day retention
│   └── AuditSummaryCommand.php  # 14-day post go-live audit trail analysis
├── deployment/                 # Production deployment scripts & server configs
│   ├── nginx.conf              # Nginx server block with SSL, rate limiting & security headers
│   ├── backup.sh               # Shell script for Linux cron job & file upload sync
│   └── deploy.sh               # One-click zero-downtime production deployment script
├── docs/                       # Official operational documentation
│   ├── UAT_CHECKLIST.md        # Comprehensive UAT scenario checklist & sign-off sheet
│   ├── SOP_PENGGUNAAN_SIKEMAS.md # Standard Operating Procedures per role (Kasi, Keuangan, Sekmat, Umum, Camat)
│   ├── CHECKLIST_ONBOARDING_STAF.md # New staff onboarding guide & compliance checklist
│   └── PANDUAN_DEPLOYMENT_VPS.md # Step-by-step VPS Ubuntu 22.04 LTS deployment manual
├── backup/                     # Database automated backup dumps storage (.sql)
└── tests/
    └── Feature/
        ├── V2/
        │   ├── ProgramHierarchyCrudTest.php # 5 tests, 32 assertions (Program/Kegiatan/SubKegiatan)
        │   ├── BelanjaCrudWorkflowTest.php  # 5 tests, 60 assertions (Belanja, Dokumen, Immutability)
        │   └── VerifikasiWorkflowTest.php   # 1 test, 35 assertions (Operator -> Sekmat -> Camat Lifecycle)
        ├── UAT/
        │   └── UatScenarioTest.php        # 5 tests, 129 assertions, Full E2E per-role testing
        ├── Aset/
        │   └── AsetWorkflowTest.php       # 11 tests, BR-ASET-01 s/d BR-ASET-06 & QR/PDF
        ├── Spj/
        │   └── SpjWorkflowTest.php        # 12 tests, state machine & BR-SPJ-01 s/d BR-SPJ-10
        ├── Dashboard/
        │   └── DashboardWorkflowTest.php  # 5 tests, role routing, unified Dashboard/Index & redirects
        ├── Laporan/
        │   └── LaporanWorkflowTest.php    # 7 tests, auth, preview, PDF & Excel exports
        ├── Kegiatan/
        │   └── KegiatanWorkflowTest.php   # 7 tests, CRUD kegiatan, validation, & audit log
        └── Authorization/
            └── RolePermissionPolicyTest.php # 6 tests, RBAC permissions
```

## 🔄 Core Pattern Flow

```
HTTP Request
     │
     ▼
Route (`routes/web.php`)
     │
     ▼
FormRequest (Validation & Policy Auth)
     │
     ▼
Controller (Receives input, calls Service, renders Inertia/JSON)
     │
     ▼
Service (Pure business logic, state machines, transactions)
     │
     ▼
Repository (Database abstraction, eager loading, query scopes)
     │
     ▼
Model (Type-safe casts via Enums, relationships, observers)
     │
     ▼
Database (MySQL 8.0+)
```
