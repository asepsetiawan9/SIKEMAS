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
│   │   ├── UserRole.php
│   │   ├── SpjStatus.php
│   │   ├── KondisiAset.php
│   │   ├── JenisKibKir.php
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
│   │   │   ├── DashboardController.php
│   │   │   ├── SpjController.php
│   │   │   ├── KegiatanController.php
│   │   │   ├── AsetController.php
│   │   │   ├── LaporanController.php
│   │   │   └── NotifikasiController.php
│   │   ├── Middleware/
│   │   │   ├── CheckUserActive.php       # Inactive user guard
│   │   │   └── HandleInertiaRequests.php # Shared auth, badges & flash
│   │   ├── Requests/           # Form validation & authorization
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
│   │   ├── User.php
│   │   ├── Kegiatan.php
│   │   ├── Spj.php
│   │   ├── Aset.php
│   │   ├── KibKir.php
│   │   ├── ArsipDigital.php
│   │   ├── Notifikasi.php
│   │   ├── Pengaturan.php
│   │   └── LogAktivitas.php
│   ├── Repositories/           # Database abstraction layer
│   │   ├── Contracts/          # Repository interfaces
│   │   ├── KegiatanRepository.php
│   │   ├── SpjRepository.php
│   │   ├── AsetRepository.php
│   │   └── NotifikasiRepository.php
│   ├── Services/               # Pure business logic layer
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
        ├── UAT/
        │   └── UatScenarioTest.php        # 5 tests, 129 assertions, Full E2E per-role testing
        ├── Aset/
        │   └── AsetWorkflowTest.php       # 9 tests, BR-ASET-01 s/d BR-ASET-06 & QR/PDF
        ├── Spj/
        │   └── SpjWorkflowTest.php        # 10 tests, state machine & BR-SPJ-01 s/d BR-SPJ-10
        ├── Dashboard/
        │   └── DashboardWorkflowTest.php  # 5 tests, role routing, charts & BR-KEU-03 warnings
        ├── Laporan/
        │   └── LaporanWorkflowTest.php    # 7 tests, auth, preview, PDF & Excel exports
        ├── Kegiatan/
        │   └── KegiatanWorkflowTest.php   # 7 tests, CRUD kegiatan, validation, & audit log
        └── Authorization/
            └── RolePermissionPolicyTest.php # 5 tests, RBAC permissions
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
