import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import KondisiBadge from '@/Components/KondisiBadge';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import { toast } from 'sonner';
import {
    TrendingUp,
    FileSpreadsheet,
    FileText,
    Filter,
    RotateCcw,
    Download,
    Calendar,
    Briefcase,
    Building2,
    Package,
    ArrowUpRight,
    Wallet,
    Layers,
    Search,
} from 'lucide-react';

export default function Index({
    reportData = {},
    kegiatanOptions = [],
    seksiOptions = [],
}) {
    const { auth } = usePage().props;
    const canExport = auth?.user?.permissions?.includes('laporan.export');

    const filters = reportData.filters || {};
    const activeJenis = filters.jenis_laporan || 'keuangan';

    const [formFilters, setFormFilters] = useState({
        jenis_laporan: activeJenis,
        tanggal_mulai: filters.tanggal_mulai || '',
        tanggal_selesai: filters.tanggal_selesai || '',
        kegiatan_id: filters.kegiatan_id || '',
        seksi: filters.seksi || '',
        kondisi: filters.kondisi || '',
        lokasi: filters.lokasi || '',
    });

    const handleTabChange = (jenis) => {
        const next = { ...formFilters, jenis_laporan: jenis };
        setFormFilters(next);
        router.get('/laporan', next, { preserveState: true, replace: true });
    };

    const handleApplyFilter = (e) => {
        e?.preventDefault();
        router.get('/laporan', formFilters, { preserveState: true, replace: true });
        toast.info('Filter laporan diterapkan');
    };

    const handleResetFilter = () => {
        const reset = {
            jenis_laporan: activeJenis,
            tanggal_mulai: '',
            tanggal_selesai: '',
            kegiatan_id: '',
            seksi: '',
            kondisi: '',
            lokasi: '',
        };
        setFormFilters(reset);
        router.get('/laporan', { jenis_laporan: activeJenis }, { preserveState: true, replace: true });
        toast.info('Filter dibersihkan');
    };

    const buildExportUrl = (type) => {
        const params = new URLSearchParams();
        Object.entries(formFilters).forEach(([key, val]) => {
            if (val) params.append(key, val);
        });
        return `/laporan/export/${type}?${params.toString()}`;
    };

    const handleExport = (type) => {
        const url = buildExportUrl(type);
        toast.success(`Memproses unduhan file ${type.toUpperCase()}...`);
        window.location.href = url;
    };

    const keuangan = reportData.keuangan || {};
    const keuanganSummary = keuangan.summary || {};
    const kegiatanList = keuangan.kegiatanList || [];
    const spjList = keuangan.spjList || [];

    const aset = reportData.aset || {};
    const asetSummary = aset.summary || {};
    const asetList = aset.asetList || [];

    return (
        <AuthenticatedLayout title="Pusat Laporan & Ekspor Data">
            <Head title="Pusat Laporan & Ekspor" />

            <div className="space-y-6">
                {/* Header & Export Actions */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-neutral-900 flex items-center gap-2">
                            <TrendingUp className="w-5 h-5 text-primary" />
                            Pusat Laporan & Rekapitulasi Data
                        </h2>
                        <p className="text-xs text-neutral-500 mt-1">
                            Laporan resmi realisasi kegiatan anggaran dan inventarisasi Barang Milik Daerah (BMD) Kecamatan Caringin
                        </p>
                    </div>

                    {canExport && (
                        <div className="flex items-center gap-2 shrink-0">
                            <button
                                onClick={() => handleExport('excel')}
                                className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 text-white rounded-btn text-xs font-semibold hover:bg-emerald-700 shadow-sm transition-colors"
                            >
                                <FileSpreadsheet className="w-4 h-4" />
                                Ekspor Excel
                            </button>
                            <button
                                onClick={() => handleExport('pdf')}
                                className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-600 text-white rounded-btn text-xs font-semibold hover:bg-rose-700 shadow-sm transition-colors"
                            >
                                <Download className="w-4 h-4" />
                                Ekspor PDF
                            </button>
                        </div>
                    )}
                </div>

                {/* Tab Switcher */}
                <div className="flex gap-2 border-b border-neutral-200">
                    <button
                        onClick={() => handleTabChange('keuangan')}
                        className={`px-4 py-2.5 text-xs font-bold transition-colors border-b-2 flex items-center gap-2 ${
                            activeJenis === 'keuangan'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-neutral-500 hover:text-neutral-800'
                        }`}
                    >
                        <Wallet className="w-4 h-4" />
                        Laporan Realisasi Keuangan & SPJ
                    </button>
                    <button
                        onClick={() => handleTabChange('aset')}
                        className={`px-4 py-2.5 text-xs font-bold transition-colors border-b-2 flex items-center gap-2 ${
                            activeJenis === 'aset'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-neutral-500 hover:text-neutral-800'
                        }`}
                    >
                        <Package className="w-4 h-4" />
                        Rekapitulasi Inventaris Aset (BMD)
                    </button>
                </div>

                {/* Filter Form Card */}
                <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm space-y-4">
                    <div className="flex items-center justify-between">
                        <span className="text-xs font-bold uppercase tracking-wider text-neutral-600 flex items-center gap-1.5">
                            <Filter className="w-3.5 h-3.5 text-primary" />
                            Parameter Filter Laporan
                        </span>
                        <span className="text-[11px] text-neutral-400">
                            {reportData.periodeText}
                        </span>
                    </div>

                    <form onSubmit={handleApplyFilter} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                        {/* Date Filters */}
                        <div>
                            <label className="block text-[11px] font-semibold text-neutral-600 mb-1">
                                Tanggal Mulai
                            </label>
                            <input
                                type="date"
                                value={formFilters.tanggal_mulai}
                                onChange={(e) => setFormFilters({ ...formFilters, tanggal_mulai: e.target.value })}
                                className="w-full text-xs rounded-btn border-neutral-300 focus:border-primary focus:ring-primary shadow-sm"
                            />
                        </div>

                        <div>
                            <label className="block text-[11px] font-semibold text-neutral-600 mb-1">
                                Tanggal Selesai
                            </label>
                            <input
                                type="date"
                                value={formFilters.tanggal_selesai}
                                onChange={(e) => setFormFilters({ ...formFilters, tanggal_selesai: e.target.value })}
                                className="w-full text-xs rounded-btn border-neutral-300 focus:border-primary focus:ring-primary shadow-sm"
                            />
                        </div>

                        {/* Keuangan-specific filters */}
                        {activeJenis === 'keuangan' && (
                            <>
                                <div>
                                    <label className="block text-[11px] font-semibold text-neutral-600 mb-1">
                                        Pilih Kegiatan
                                    </label>
                                    <select
                                        value={formFilters.kegiatan_id}
                                        onChange={(e) => setFormFilters({ ...formFilters, kegiatan_id: e.target.value })}
                                        className="w-full text-xs rounded-btn border-neutral-300 focus:border-primary focus:ring-primary shadow-sm"
                                    >
                                        <option value="">Semua Kegiatan</option>
                                        {kegiatanOptions.map((opt) => (
                                            <option key={opt.id} value={opt.id}>
                                                {opt.nama}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-[11px] font-semibold text-neutral-600 mb-1">
                                        Pilih Seksi Pengampu
                                    </label>
                                    <select
                                        value={formFilters.seksi}
                                        onChange={(e) => setFormFilters({ ...formFilters, seksi: e.target.value })}
                                        className="w-full text-xs rounded-btn border-neutral-300 focus:border-primary focus:ring-primary shadow-sm"
                                    >
                                        <option value="">Semua Seksi</option>
                                        {seksiOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            </>
                        )}

                        {/* Aset-specific filters */}
                        {activeJenis === 'aset' && (
                            <>
                                <div>
                                    <label className="block text-[11px] font-semibold text-neutral-600 mb-1">
                                        Kondisi Fisik
                                    </label>
                                    <select
                                        value={formFilters.kondisi}
                                        onChange={(e) => setFormFilters({ ...formFilters, kondisi: e.target.value })}
                                        className="w-full text-xs rounded-btn border-neutral-300 focus:border-primary focus:ring-primary shadow-sm"
                                    >
                                        <option value="">Semua Kondisi</option>
                                        <option value="baik">Baik</option>
                                        <option value="rusak_ringan">Rusak Ringan</option>
                                        <option value="rusak_berat">Rusak Berat</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-[11px] font-semibold text-neutral-600 mb-1">
                                        Ruangan / Lokasi
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="Cari nama ruangan..."
                                        value={formFilters.lokasi}
                                        onChange={(e) => setFormFilters({ ...formFilters, lokasi: e.target.value })}
                                        className="w-full text-xs rounded-btn border-neutral-300 focus:border-primary focus:ring-primary shadow-sm"
                                    />
                                </div>
                            </>
                        )}

                        {/* Actions */}
                        <div className="sm:col-span-2 lg:col-span-4 flex items-center justify-end gap-2 pt-2 border-t border-neutral-100">
                            <button
                                type="button"
                                onClick={handleResetFilter}
                                className="inline-flex items-center gap-1.5 px-3 py-1.5 border border-neutral-300 text-neutral-600 rounded-btn text-xs font-semibold hover:bg-neutral-50 transition-colors"
                            >
                                <RotateCcw className="w-3.5 h-3.5" />
                                Reset
                            </button>
                            <button
                                type="submit"
                                className="inline-flex items-center gap-1.5 px-4 py-1.5 bg-primary text-white rounded-btn text-xs font-semibold hover:bg-primary-dark shadow transition-colors"
                            >
                                <Filter className="w-3.5 h-3.5" />
                                Terapkan Filter
                            </button>
                        </div>
                    </form>
                </div>

                {/* Report Content: Keuangan */}
                {activeJenis === 'keuangan' && (
                    <div className="space-y-6">
                        {/* Summary Metrics */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="bg-surface rounded-card p-4 border border-neutral-200/80 shadow-sm flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                                    <Wallet className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-[11px] text-neutral-500">Pagu Anggaran</p>
                                    <h4 className="text-base font-bold text-neutral-900">
                                        {formatRupiah(keuanganSummary.total_pagu || 0)}
                                    </h4>
                                </div>
                            </div>

                            <div className="bg-surface rounded-card p-4 border border-neutral-200/80 shadow-sm flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                                    <ArrowUpRight className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-[11px] text-neutral-500">Realisasi SPJ</p>
                                    <h4 className="text-base font-bold text-emerald-700">
                                        {formatRupiah(keuanganSummary.total_realisasi || 0)}
                                    </h4>
                                    <span className="text-[10px] text-emerald-600 font-semibold">
                                        {keuanganSummary.persen_realisasi || 0}% serapan
                                    </span>
                                </div>
                            </div>

                            <div className="bg-surface rounded-card p-4 border border-neutral-200/80 shadow-sm flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                    <Briefcase className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-[11px] text-neutral-500">Sisa Pagu</p>
                                    <h4 className="text-base font-bold text-indigo-700">
                                        {formatRupiah(keuanganSummary.sisa_pagu || 0)}
                                    </h4>
                                </div>
                            </div>

                            <div className="bg-surface rounded-card p-4 border border-neutral-200/80 shadow-sm flex items-center gap-3">
                                <div className="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                                    <FileText className="w-5 h-5" />
                                </div>
                                <div>
                                    <p className="text-[11px] text-neutral-500">Berkas SPJ</p>
                                    <h4 className="text-base font-bold text-neutral-900">
                                        {keuanganSummary.total_spj || 0} Berkas
                                    </h4>
                                </div>
                            </div>
                        </div>

                        {/* Kegiatan Table Preview */}
                        <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                                <h3 className="font-bold text-neutral-900 text-sm">
                                    I. Rekapitulasi Realisasi per Kegiatan Anggaran
                                </h3>
                                <span className="text-xs text-neutral-500">
                                    Total {kegiatanList.length} Kegiatan
                                </span>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-neutral-200 text-neutral-500 bg-neutral-50/50">
                                            <th className="py-2.5 px-3 font-semibold">No</th>
                                            <th className="py-2.5 px-3 font-semibold">Kode Rekening</th>
                                            <th className="py-2.5 px-3 font-semibold">Nama Kegiatan</th>
                                            <th className="py-2.5 px-3 font-semibold">Seksi Pengampu</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Pagu</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Realisasi</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Sisa Pagu</th>
                                            <th className="py-2.5 px-3 font-semibold text-center">%</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {kegiatanList.length === 0 ? (
                                            <tr>
                                                <td colSpan="8" className="py-6 text-center text-neutral-400">
                                                    Tidak ada data kegiatan anggaran untuk filter ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            kegiatanList.map((keg, idx) => (
                                                <tr key={keg.id} className="hover:bg-neutral-50/60">
                                                    <td className="py-3 px-3 text-neutral-500">{idx + 1}</td>
                                                    <td className="py-3 px-3 font-mono text-neutral-600">{keg.kode_rekening}</td>
                                                    <td className="py-3 px-3 font-semibold text-neutral-900">{keg.nama}</td>
                                                    <td className="py-3 px-3 text-neutral-600">
                                                        {keg.kasi_nama} ({ucfirst(keg.seksi)})
                                                    </td>
                                                    <td className="py-3 px-3 text-right text-neutral-800 font-medium">
                                                        {formatRupiah(keg.pagu)}
                                                    </td>
                                                    <td className="py-3 px-3 text-right text-emerald-700 font-bold">
                                                        {formatRupiah(keg.realisasi)}
                                                    </td>
                                                    <td className="py-3 px-3 text-right text-blue-700 font-medium">
                                                        {formatRupiah(keg.sisa_pagu)}
                                                    </td>
                                                    <td className="py-3 px-3 text-center font-bold">
                                                        <span className={keg.persen > 80 ? 'text-amber-600' : 'text-neutral-700'}>
                                                            {keg.persen}%
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {/* SPJ Table Preview */}
                        <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                                <h3 className="font-bold text-neutral-900 text-sm">
                                    II. Rincian Berkas Pertanggungjawaban (SPJ)
                                </h3>
                                <span className="text-xs text-neutral-500">
                                    Total {spjList.length} Berkas
                                </span>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-neutral-200 text-neutral-500 bg-neutral-50/50">
                                            <th className="py-2.5 px-3 font-semibold">No</th>
                                            <th className="py-2.5 px-3 font-semibold">Nomor SPJ</th>
                                            <th className="py-2.5 px-3 font-semibold">Nama Kegiatan</th>
                                            <th className="py-2.5 px-3 font-semibold">Tanggal</th>
                                            <th className="py-2.5 px-3 font-semibold">Diajukan Oleh</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Nominal</th>
                                            <th className="py-2.5 px-3 font-semibold text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {spjList.length === 0 ? (
                                            <tr>
                                                <td colSpan="7" className="py-6 text-center text-neutral-400">
                                                    Tidak ada rincian berkas SPJ pada periode ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            spjList.map((spj, idx) => (
                                                <tr key={spj.id} className="hover:bg-neutral-50/60">
                                                    <td className="py-3 px-3 text-neutral-500">{idx + 1}</td>
                                                    <td className="py-3 px-3 font-semibold text-neutral-900">
                                                        {spj.nomor_spj || 'Draft / Belum Bernomor'}
                                                    </td>
                                                    <td className="py-3 px-3 text-neutral-700">{spj.kegiatan?.nama}</td>
                                                    <td className="py-3 px-3 text-neutral-500">{formatDate(spj.tanggal_pengajuan)}</td>
                                                    <td className="py-3 px-3 text-neutral-600">{spj.diajukan_oleh?.name || '-'}</td>
                                                    <td className="py-3 px-3 text-right font-bold text-neutral-900">
                                                        {formatRupiah(spj.nominal)}
                                                    </td>
                                                    <td className="py-3 px-3 text-center">
                                                        <StatusBadge status={spj.status} />
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}

                {/* Report Content: Aset */}
                {activeJenis === 'aset' && (
                    <div className="space-y-6">
                        {/* Summary Metrics */}
                        <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <div className="bg-surface rounded-card p-4 border border-neutral-200/80 shadow-sm text-center">
                                <span className="text-xs text-neutral-500">Total Unit Aset</span>
                                <h4 className="text-xl font-bold text-neutral-900 mt-1">
                                    {asetSummary.total_aset || 0} Unit
                                </h4>
                            </div>

                            <div className="bg-surface rounded-card p-4 border border-neutral-200/80 shadow-sm text-center">
                                <span className="text-xs text-neutral-500">Total Nilai BMD</span>
                                <h4 className="text-base font-bold text-primary mt-1">
                                    {formatRupiah(asetSummary.total_nilai || 0)}
                                </h4>
                            </div>

                            <div className="bg-surface rounded-card p-4 border border-neutral-200/80 shadow-sm text-center">
                                <span className="text-xs text-emerald-700 font-semibold">Kondisi Baik</span>
                                <h4 className="text-xl font-bold text-emerald-700 mt-1">
                                    {asetSummary.baik || 0} Unit
                                </h4>
                            </div>

                            <div className="bg-surface rounded-card p-4 border border-neutral-200/80 shadow-sm text-center">
                                <span className="text-xs text-rose-700 font-semibold">Rusak Berat (Kandidat)</span>
                                <h4 className="text-xl font-bold text-rose-700 mt-1">
                                    {asetSummary.rusak_berat || 0} Unit
                                </h4>
                            </div>
                        </div>

                        {/* Aset Table Preview */}
                        <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                            <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                                <h3 className="font-bold text-neutral-900 text-sm">
                                    Rekapitulasi Inventaris Barang Milik Daerah (BMD)
                                </h3>
                                <span className="text-xs text-neutral-500">
                                    Total {asetList.length} Item
                                </span>
                            </div>

                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="border-b border-neutral-200 text-neutral-500 bg-neutral-50/50">
                                            <th className="py-2.5 px-3 font-semibold">No</th>
                                            <th className="py-2.5 px-3 font-semibold">Kode Barang</th>
                                            <th className="py-2.5 px-3 font-semibold">Nama Barang & Spesifikasi</th>
                                            <th className="py-2.5 px-3 font-semibold text-center">Tahun</th>
                                            <th className="py-2.5 px-3 font-semibold text-center">Kondisi</th>
                                            <th className="py-2.5 px-3 font-semibold">Ruangan / Lokasi</th>
                                            <th className="py-2.5 px-3 font-semibold text-right">Nilai (Rp)</th>
                                            <th className="py-2.5 px-3 font-semibold">Penanggung Jawab</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {asetList.length === 0 ? (
                                            <tr>
                                                <td colSpan="8" className="py-6 text-center text-neutral-400">
                                                    Tidak ada data aset BMD untuk filter ini.
                                                </td>
                                            </tr>
                                        ) : (
                                            asetList.map((item, idx) => (
                                                <tr key={item.id} className="hover:bg-neutral-50/60">
                                                    <td className="py-3 px-3 text-neutral-500">{idx + 1}</td>
                                                    <td className="py-3 px-3 font-mono font-semibold text-neutral-700">
                                                        {item.kode_barang}
                                                    </td>
                                                    <td className="py-3 px-3">
                                                        <span className="font-bold text-neutral-900">{item.nama}</span>
                                                        {item.merk_type && (
                                                            <span className="block text-[11px] text-neutral-500">
                                                                {item.merk_type}
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-3 px-3 text-center text-neutral-600">
                                                        {item.tahun_perolehan}
                                                    </td>
                                                    <td className="py-3 px-3 text-center">
                                                        <KondisiBadge kondisi={item.kondisi} />
                                                    </td>
                                                    <td className="py-3 px-3 text-neutral-700">{item.lokasi}</td>
                                                    <td className="py-3 px-3 text-right font-bold text-neutral-900">
                                                        {formatRupiah(item.nilai)}
                                                    </td>
                                                    <td className="py-3 px-3 text-neutral-600">
                                                        {item.penanggung_jawab?.name || '-'}
                                                    </td>
                                                </tr>
                                            ))
                                        )}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
