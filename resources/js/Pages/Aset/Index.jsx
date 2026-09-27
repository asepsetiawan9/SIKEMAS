import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import KondisiBadge from '@/Components/KondisiBadge';
import EmptyState from '@/Components/EmptyState';
import QrDownloadButton from '@/Components/QrDownloadButton';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import {
    Package,
    PlusCircle,
    Search,
    Filter,
    RotateCcw,
    AlertTriangle,
    CheckCircle2,
    Calendar,
    MapPin,
    QrCode,
    FileSpreadsheet,
    FileText,
    Eye,
    Edit3,
    Clock,
    AlertCircle
} from 'lucide-react';

export default function Index({
    assets,
    statistics = {},
    distinctLokasi = [],
    distinctTahun = [],
    filters = {}
}) {
    const { auth } = usePage().props;
    const userRole = auth?.user?.role;
    const permissions = auth?.user?.permissions || auth?.permissions || [];
    const canCreate = permissions.includes('aset.create') || ['staf_keuangan', 'super_admin'].includes(userRole);
    const canUpdate = permissions.includes('aset.update') || ['staf_keuangan', 'staf_umum', 'super_admin'].includes(userRole);

    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [kondisi, setKondisi] = useState(filters.kondisi || '');
    const [lokasi, setLokasi] = useState(filters.lokasi || '');
    const [tahun, setTahun] = useState(filters.tahun || '');
    const [overdue, setOverdue] = useState(Boolean(filters.overdue));

    // Debounced search
    useEffect(() => {
        const timeout = setTimeout(() => {
            if (searchTerm !== (filters.search || '')) {
                applyFilters({ search: searchTerm });
            }
        }, 350);

        return () => clearTimeout(timeout);
    }, [searchTerm]);

    const currentTab = filters.tab || '';

    const applyFilters = (newFilters = {}) => {
        router.get(
            '/aset',
            {
                tab: currentTab || undefined,
                search: searchTerm,
                kondisi,
                lokasi,
                tahun,
                overdue: overdue ? 1 : undefined,
                ...newFilters,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    const handleTabChange = (newTab) => {
        applyFilters({ tab: newTab || undefined });
    };

    const handleKondisiChange = (e) => {
        const val = e.target.value;
        setKondisi(val);
        applyFilters({ kondisi: val });
    };

    const handleLokasiChange = (e) => {
        const val = e.target.value;
        setLokasi(val);
        applyFilters({ lokasi: val });
    };

    const handleTahunChange = (e) => {
        const val = e.target.value;
        setTahun(val);
        applyFilters({ tahun: val });
    };

    const handleOverdueToggle = () => {
        const nextVal = !overdue;
        setOverdue(nextVal);
        applyFilters({ overdue: nextVal ? 1 : undefined });
    };

    const handleResetFilters = () => {
        setSearchTerm('');
        setKondisi('');
        setLokasi('');
        setTahun('');
        setOverdue(false);
        router.get('/aset', currentTab ? { tab: currentTab } : {}, { preserveState: true, replace: true });
    };

    const hasActiveFilters = Boolean(searchTerm || kondisi || lokasi || tahun || overdue);
    const items = assets?.data || [];

    return (
        <AuthenticatedLayout title="Inventarisasi BMD & Aset">
            <Head title="Aset & BMD - Kecamatan Caringin" />

            <div className="space-y-6">
                {/* Header section */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-surface p-5 rounded-card border border-neutral-200/80 shadow-sm">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="p-2 rounded-lg bg-primary/10 text-primary">
                                <Package className="w-5 h-5" />
                            </span>
                            <h2 className="text-xl font-bold text-neutral-900 tracking-tight">
                                Inventarisasi Barang Milik Daerah (BMD)
                            </h2>
                        </div>
                        <p className="text-xs text-neutral-500 mt-1.5 ml-9">
                            Database aset tetap, pelabelan QR Code otomatis, pencatatan mutasi fisik, dan kartu inventaris KIB/KIR.
                        </p>
                    </div>

                    {canCreate && (
                        <Link
                            href="/aset/create"
                            className="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-btn text-xs font-semibold hover:bg-primary-dark shadow-sm transition-all duration-150 active:scale-[0.98]"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Pendaftaran Aset Baru
                        </Link>
                    )}
                </div>

                {/* Banner Peringatan BR-ASET-02 jika ada aset rusak berat */}
                {Number(statistics.total_rusak_berat || 0) > 0 && (
                    <div className="flex items-start gap-3 p-4 rounded-card bg-amber-50 border border-amber-200 text-amber-900 shadow-sm">
                        <AlertTriangle className="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                        <div className="text-xs">
                            <strong className="font-semibold block text-amber-800">
                                Peringatan Inventaris (BR-ASET-02):
                            </strong>
                            Terdapat <strong>{statistics.total_rusak_berat} aset</strong> berkondisi <strong>Rusak Berat</strong>. Aset ini otomatis tercatat sebagai kandidat penghapusan barang inventaris daerah.
                            <button
                                type="button"
                                onClick={() => {
                                    setKondisi('rusak_berat');
                                    applyFilters({ kondisi: 'rusak_berat' });
                                }}
                                className="ml-2 font-semibold underline text-amber-800 hover:text-amber-950"
                            >
                                Tampilkan Hanya Aset Rusak Berat →
                            </button>
                        </div>
                    </div>
                )}

                {/* Statistics Cards */}
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-sm">
                        <span className="text-[11px] font-medium text-neutral-500 block">Total Aset</span>
                        <div className="mt-1 flex items-baseline gap-1.5">
                            <span className="text-2xl font-bold text-neutral-900">{statistics.total_aset || 0}</span>
                            <span className="text-[10px] text-neutral-400">unit</span>
                        </div>
                    </div>

                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-sm col-span-2 md:col-span-1 lg:col-span-2">
                        <span className="text-[11px] font-medium text-neutral-500 block">Total Nilai BMD</span>
                        <div className="mt-1">
                            <span className="text-lg font-bold text-primary font-mono truncate block">
                                {formatRupiah(statistics.total_nilai || 0)}
                            </span>
                        </div>
                    </div>

                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-sm">
                        <span className="text-[11px] font-medium text-neutral-500 flex items-center gap-1">
                            <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" /> Kondisi Baik
                        </span>
                        <div className="mt-1 flex items-baseline gap-1.5">
                            <span className="text-xl font-bold text-emerald-600">{statistics.total_baik || 0}</span>
                            <span className="text-[10px] text-neutral-400">unit</span>
                        </div>
                    </div>

                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-sm">
                        <span className="text-[11px] font-medium text-neutral-500 flex items-center gap-1">
                            <AlertCircle className="w-3.5 h-3.5 text-amber-500" /> Rusak Ringan
                        </span>
                        <div className="mt-1 flex items-baseline gap-1.5">
                            <span className="text-xl font-bold text-amber-600">{statistics.total_rusak_ringan || 0}</span>
                            <span className="text-[10px] text-neutral-400">unit</span>
                        </div>
                    </div>

                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-sm">
                        <span className="text-[11px] font-medium text-neutral-500 flex items-center gap-1">
                            <Clock className="w-3.5 h-3.5 text-rose-500" /> Overdue &gt;90h
                        </span>
                        <div className="mt-1 flex items-baseline gap-1.5">
                            <span className="text-xl font-bold text-rose-600">{statistics.total_overdue || 0}</span>
                            <span className="text-[10px] text-neutral-400">unit</span>
                        </div>
                    </div>
                </div>

                {/* Tab Filter KIB / KIR (UX-01) */}
                <div className="flex items-center gap-2 border-b border-neutral-200">
                    <button
                        onClick={() => handleTabChange('')}
                        className={`px-4 py-2.5 text-xs font-bold rounded-t-lg transition-colors border-b-2 inline-flex items-center gap-1.5 ${
                            !currentTab || currentTab === 'semua'
                                ? 'border-primary text-primary bg-primary/5'
                                : 'border-transparent text-neutral-500 hover:text-neutral-900'
                        }`}
                    >
                        <Package className="w-3.5 h-3.5" />
                        <span>Semua Aset</span>
                        <span className="text-[10px] px-1.5 py-0.5 rounded-full bg-neutral-200 text-neutral-700">
                            {statistics.total_aset || 0}
                        </span>
                    </button>
                    <button
                        onClick={() => handleTabChange('kib')}
                        className={`px-4 py-2.5 text-xs font-bold rounded-t-lg transition-colors border-b-2 inline-flex items-center gap-1.5 ${
                            currentTab === 'kib' || currentTab === 'kib_kir'
                                ? 'border-primary text-primary bg-primary/5'
                                : 'border-transparent text-neutral-500 hover:text-neutral-900'
                        }`}
                    >
                        <FileSpreadsheet className="w-3.5 h-3.5" />
                        <span>Dokumen KIB (Aset Tetap / Peralatan)</span>
                    </button>
                    <button
                        onClick={() => handleTabChange('kir')}
                        className={`px-4 py-2.5 text-xs font-bold rounded-t-lg transition-colors border-b-2 inline-flex items-center gap-1.5 ${
                            currentTab === 'kir'
                                ? 'border-primary text-primary bg-primary/5'
                                : 'border-transparent text-neutral-500 hover:text-neutral-900'
                        }`}
                    >
                        <FileText className="w-3.5 h-3.5" />
                        <span>Dokumen KIR (Inventaris Ruangan)</span>
                    </button>
                </div>

                {/* Filter and Search Bar */}
                <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-sm space-y-3">
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
                        {/* Search input */}
                        <div className="lg:col-span-4 relative">
                            <Search className="w-4 h-4 text-neutral-400 absolute left-3 top-1/2 -translate-y-1/2" />
                            <input
                                type="text"
                                placeholder="Cari nama, kode barang, merk, nomor register..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-full pl-9 pr-3 py-2 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20"
                            />
                        </div>

                        {/* Kondisi filter */}
                        <div className="lg:col-span-2">
                            <select
                                value={kondisi}
                                onChange={handleKondisiChange}
                                className="w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 text-neutral-700 font-medium"
                            >
                                <option value="">Semua Kondisi</option>
                                <option value="baik">Baik</option>
                                <option value="rusak_ringan">Rusak Ringan</option>
                                <option value="rusak_berat">Rusak Berat</option>
                            </select>
                        </div>

                        {/* Lokasi filter */}
                        <div className="lg:col-span-2">
                            <select
                                value={lokasi}
                                onChange={handleLokasiChange}
                                className="w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 text-neutral-700"
                            >
                                <option value="">Semua Ruangan/Lokasi</option>
                                {distinctLokasi.map((loc) => (
                                    <option key={loc} value={loc}>
                                        {loc}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Tahun filter */}
                        <div className="lg:col-span-2">
                            <select
                                value={tahun}
                                onChange={handleTahunChange}
                                className="w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 text-neutral-700"
                            >
                                <option value="">Semua Tahun</option>
                                {distinctTahun.map((yr) => (
                                    <option key={yr} value={yr}>
                                        {yr}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Overdue check & Reset */}
                        <div className="lg:col-span-2 flex items-center justify-between sm:justify-end gap-3">
                            <label className="inline-flex items-center gap-1.5 cursor-pointer text-xs text-neutral-700 select-none">
                                <input
                                    type="checkbox"
                                    checked={overdue}
                                    onChange={handleOverdueToggle}
                                    className="rounded border-neutral-300 text-rose-600 focus:ring-rose-500 w-3.5 h-3.5"
                                />
                                <span className={overdue ? 'font-semibold text-rose-600' : ''}>Overdue</span>
                            </label>

                            {hasActiveFilters && (
                                <button
                                    type="button"
                                    onClick={handleResetFilters}
                                    className="inline-flex items-center gap-1 text-xs text-neutral-500 hover:text-neutral-800 underline"
                                >
                                    <RotateCcw className="w-3 h-3" /> Reset
                                </button>
                            )}
                        </div>
                    </div>
                </div>

                {/* DataTable */}
                <div className="bg-surface rounded-card border border-neutral-200/80 shadow-sm overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-neutral-50 border-b border-neutral-200 text-neutral-600 uppercase tracking-wider font-semibold text-[11px]">
                                <tr>
                                    <th className="py-3 px-4">Barang / BMD</th>
                                    <th className="py-3 px-4">Kode & Register</th>
                                    <th className="py-3 px-4">Nilai Perolehan</th>
                                    <th className="py-3 px-4">Kondisi</th>
                                    <th className="py-3 px-4">Lokasi & Verifikasi</th>
                                    <th className="py-3 px-4">KIB / KIR</th>
                                    <th className="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-100">
                                {items.length === 0 ? (
                                    <tr>
                                        <td colSpan="7" className="p-8">
                                            <EmptyState
                                                title="Tidak ada aset ditemukan"
                                                description={
                                                    hasActiveFilters
                                                        ? 'Tidak ada data aset yang cocok dengan filter yang Anda gunakan.'
                                                        : 'Belum ada aset BMD yang didaftarkan pada sistem.'
                                                }
                                                action={
                                                    hasActiveFilters ? (
                                                        <button
                                                            onClick={handleResetFilters}
                                                            className="text-xs font-semibold text-primary underline"
                                                        >
                                                            Reset Filter
                                                        </button>
                                                    ) : canCreate ? (
                                                        <Link
                                                            href="/aset/create"
                                                            className="inline-flex items-center gap-1.5 px-3.5 py-2 bg-primary text-white rounded-btn text-xs font-semibold hover:bg-primary-dark"
                                                        >
                                                            <PlusCircle className="w-4 h-4" /> Daftarkan Aset Sekarang
                                                        </Link>
                                                    ) : null
                                                }
                                            />
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((aset) => (
                                        <tr key={aset.id} className="hover:bg-neutral-50/70 transition-colors">
                                            {/* Nama & Foto */}
                                            <td className="py-3 px-4">
                                                <div className="flex items-center gap-3">
                                                    {aset.foto_url ? (
                                                        <img
                                                            src={aset.foto_url}
                                                            alt={aset.nama}
                                                            className="w-10 h-10 rounded-lg object-cover border border-neutral-200"
                                                        />
                                                    ) : (
                                                        <div className="w-10 h-10 rounded-lg bg-neutral-100 border border-neutral-200 flex items-center justify-center text-neutral-400">
                                                            <Package className="w-5 h-5" />
                                                        </div>
                                                    )}
                                                    <div>
                                                        <Link
                                                            href={`/aset/${aset.id}`}
                                                            className="font-semibold text-neutral-900 hover:text-primary transition-colors block"
                                                        >
                                                            {aset.nama}
                                                        </Link>
                                                        <span className="text-[11px] text-neutral-500">
                                                            {aset.merk_type || 'Tanpa Merk'} • Thn {aset.tahun_perolehan}
                                                        </span>
                                                    </div>
                                                </div>
                                            </td>

                                            {/* Kode Barang & Register */}
                                            <td className="py-3 px-4">
                                                <span className="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-neutral-100 text-neutral-800 border border-neutral-200 block w-max">
                                                    {aset.kode_barang}
                                                </span>
                                                {aset.nomor_register && (
                                                    <span className="text-[11px] text-neutral-400 mt-0.5 block">
                                                        Reg: {aset.nomor_register}
                                                    </span>
                                                )}
                                            </td>

                                            {/* Nilai Aset */}
                                            <td className="py-3 px-4 font-mono font-semibold text-neutral-900">
                                                {formatRupiah(aset.nilai)}
                                            </td>

                                            {/* Kondisi Badge */}
                                            <td className="py-3 px-4">
                                                <KondisiBadge kondisi={aset.kondisi} />
                                            </td>

                                            {/* Lokasi & Verifikasi */}
                                            <td className="py-3 px-4">
                                                <div className="flex items-center gap-1 font-medium text-neutral-800">
                                                    <MapPin className="w-3 h-3 text-neutral-400 shrink-0" />
                                                    <span className="truncate max-w-[150px]">{aset.lokasi}</span>
                                                </div>
                                                <div className="flex items-center gap-1 text-[11px] mt-0.5">
                                                    <Clock className="w-3 h-3 text-neutral-400 shrink-0" />
                                                    <span className={aset.is_overdue_verifikasi ? 'text-rose-600 font-semibold' : 'text-neutral-500'}>
                                                        {formatDate(aset.tanggal_verifikasi_fisik)}
                                                        {aset.is_overdue_verifikasi && ' (!)'}
                                                    </span>
                                                </div>
                                            </td>

                                            {/* KIB / KIR badge */}
                                            <td className="py-3 px-4">
                                                <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-primary/10 text-primary border border-primary/20">
                                                    <FileText className="w-3 h-3" />
                                                    {aset.kib_kir?.jenis || 'KIB'}
                                                </span>
                                            </td>

                                            {/* Actions */}
                                            <td className="py-3 px-4 text-right">
                                                <div className="inline-flex items-center gap-1.5">
                                                    <Link
                                                        href={`/aset/${aset.id}`}
                                                        className="p-1.5 text-neutral-600 hover:text-primary hover:bg-neutral-100 rounded-btn transition-colors"
                                                        title="Lihat Detail & Riwayat"
                                                    >
                                                        <Eye className="w-4 h-4" />
                                                    </Link>

                                                    {canUpdate && (
                                                        <Link
                                                            href={`/aset/${aset.id}/edit`}
                                                            className="p-1.5 text-neutral-600 hover:text-amber-600 hover:bg-neutral-100 rounded-btn transition-colors"
                                                            title="Edit Informasi Aset"
                                                        >
                                                            <Edit3 className="w-4 h-4" />
                                                        </Link>
                                                    )}

                                                    <a
                                                        href={`/aset/${aset.id}/qr`}
                                                        className="p-1.5 text-neutral-600 hover:text-emerald-600 hover:bg-neutral-100 rounded-btn transition-colors"
                                                        title="Unduh Label QR Code"
                                                    >
                                                        <QrCode className="w-4 h-4" />
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {assets?.links && assets.links.length > 3 && (
                        <div className="p-4 border-t border-neutral-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-neutral-600">
                            <div>
                                Menampilkan <strong>{assets.from || 0}</strong> - <strong>{assets.to || 0}</strong> dari <strong>{assets.total || 0}</strong> aset
                            </div>
                            <div className="flex items-center gap-1">
                                {assets.links.map((link, idx) => (
                                    <Link
                                        key={idx}
                                        href={link.url || '#'}
                                        preserveState
                                        preserveScroll
                                        className={`px-3 py-1.5 rounded-btn text-xs font-semibold transition-colors ${
                                            link.active
                                                ? 'bg-primary text-white'
                                                : link.url
                                                ? 'bg-white hover:bg-neutral-100 text-neutral-700 border border-neutral-200'
                                                : 'text-neutral-300 pointer-events-none'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
