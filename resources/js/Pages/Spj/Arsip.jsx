import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import EmptyState from '@/Components/EmptyState';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import {
    FileText,
    PlusCircle,
    Search,
    Filter,
    RotateCcw,
    ChevronLeft,
    ChevronRight,
    Eye,
    ShieldAlert,
    Calendar,
} from 'lucide-react';

export default function Arsip({
    spjs,
    filters = {},
    kegiatans = [],
    hasRejectedSpj = false,
}) {
    const { auth } = usePage().props;
    const userRole = auth?.user?.role;
    const isKasi = userRole === 'kasi';
    const isSuperAdmin = userRole === 'super_admin';
    const canCreateSpj = isKasi || isSuperAdmin;

    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [kegiatanId, setKegiatanId] = useState(filters.kegiatan_id || '');
    const [bulan, setBulan] = useState(filters.bulan || '');
    const [tahun, setTahun] = useState(filters.tahun || '');

    const handleFilter = (e) => {
        e?.preventDefault();
        router.get('/spj', {
            tab: 'arsip',
            search: search || undefined,
            status: status || undefined,
            kegiatan_id: kegiatanId || undefined,
            bulan: bulan || undefined,
            tahun: tahun || undefined,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleReset = () => {
        setSearch('');
        setStatus('');
        setKegiatanId('');
        setBulan('');
        setTahun('');
        router.get('/spj', { tab: 'arsip' }, {
            preserveState: true,
            replace: true,
        });
    };

    const items = spjs?.data || [];
    const meta = spjs;

    const bulanList = [
        { value: 1, label: 'Januari' },
        { value: 2, label: 'Februari' },
        { value: 3, label: 'Maret' },
        { value: 4, label: 'April' },
        { value: 5, label: 'Mei' },
        { value: 6, label: 'Juni' },
        { value: 7, label: 'Juli' },
        { value: 8, label: 'Agustus' },
        { value: 9, label: 'September' },
        { value: 10, label: 'Oktober' },
        { value: 11, label: 'November' },
        { value: 12, label: 'Desember' },
    ];

    return (
        <AuthenticatedLayout title="Arsip SPJ Digital">
            <Head title="Arsip SPJ Digital" />

            <div className="space-y-6">
                {/* Header Title & CTA */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-neutral-900 tracking-tight">
                            {isKasi ? 'Daftar Pengajuan SPJ Saya' : 'Arsip & Rekapitulasi SPJ Digital'}
                        </h2>
                        <p className="text-xs text-neutral-500 mt-1">
                            Pencarian, filter lintas periode, dan monitoring riwayat pengajuan SPJ se-Kecamatan Caringin
                        </p>
                    </div>

                    {canCreateSpj && (
                        <Link
                            href="/spj/create"
                            className="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-btn text-xs font-bold hover:bg-primary-dark shadow-sm hover:shadow transition-all"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Ajukan SPJ Baru
                        </Link>
                    )}
                </div>

                {/* Banner jika Kasi punya SPJ Ditolak (BR-SPJ-10) */}
                {isKasi && hasRejectedSpj && (
                    <div className="p-4 rounded-card bg-rose-50 border border-rose-200 flex items-start gap-3 shadow-xs">
                        <ShieldAlert className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                        <div className="text-xs text-rose-800 space-y-1">
                            <span className="font-bold block text-rose-900">
                                Peringatan: Terdapat Berkas SPJ yang Perlu Revisi
                            </span>
                            <p>
                                Pengajuan baru dibatasi sementara sampai berkas yang ditolak direvisi bersama Staf Keuangan (BR-SPJ-10).
                            </p>
                        </div>
                    </div>
                )}

                {/* Filter Card */}
                <div className="bg-surface rounded-card p-4 sm:p-5 border border-neutral-200/80 shadow-xs">
                    <form onSubmit={handleFilter} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
                        {/* Search keyword */}
                        <div className="lg:col-span-2">
                            <label className="block text-[11px] font-bold text-neutral-600 mb-1">
                                Cari Nomor SPJ / Kegiatan / Pengaju
                            </label>
                            <div className="relative">
                                <Search className="w-3.5 h-3.5 text-neutral-400 absolute left-3 top-2.5" />
                                <input
                                    type="text"
                                    placeholder="Ketik kata kunci pencarian..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="w-full pl-8 pr-3 py-2 rounded-btn border border-neutral-300 text-xs text-neutral-900 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                                />
                            </div>
                        </div>

                        {/* Status filter */}
                        <div>
                            <label className="block text-[11px] font-bold text-neutral-600 mb-1">
                                Status SPJ
                            </label>
                            <select
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                                className="w-full px-3 py-2 rounded-btn border border-neutral-300 text-xs text-neutral-900 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                            >
                                <option value="">Semua Status</option>
                                <option value="diajukan_kasi">Diajukan Kasi</option>
                                <option value="dikonsolidasi">Dikonsolidasi</option>
                                <option value="diajukan_verifikasi">Diajukan Verifikasi</option>
                                <option value="diverifikasi">Diverifikasi</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>

                        {/* Bulan filter */}
                        <div>
                            <label className="block text-[11px] font-bold text-neutral-600 mb-1">
                                Bulan Periode
                            </label>
                            <select
                                value={bulan}
                                onChange={(e) => setBulan(e.target.value)}
                                className="w-full px-3 py-2 rounded-btn border border-neutral-300 text-xs text-neutral-900 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
                            >
                                <option value="">Semua Bulan</option>
                                {bulanList.map((b) => (
                                    <option key={b.value} value={b.value}>
                                        {b.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Filter Buttons */}
                        <div className="flex items-end gap-2">
                            <button
                                type="submit"
                                className="flex-1 py-2 px-3 bg-primary hover:bg-primary-dark text-white rounded-btn font-bold text-xs shadow-xs transition-colors flex items-center justify-center gap-1.5"
                            >
                                <Filter className="w-3.5 h-3.5" /> Filter
                            </button>
                            <button
                                type="button"
                                onClick={handleReset}
                                title="Reset Filter"
                                className="py-2 px-3 bg-neutral-100 hover:bg-neutral-200 text-neutral-600 rounded-btn text-xs font-semibold transition-colors flex items-center justify-center"
                            >
                                <RotateCcw className="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </form>
                </div>

                {/* Table Data Card */}
                <div className="bg-surface rounded-card border border-neutral-200/80 shadow-sm overflow-hidden">
                    {items.length === 0 ? (
                        <EmptyState
                            icon={FileText}
                            title="Tidak Ada Data SPJ"
                            description="Tidak ditemukan rekaman SPJ yang sesuai dengan kata kunci atau filter yang Anda terapkan."
                        />
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-neutral-50/80 border-b border-neutral-200 text-neutral-600">
                                        <tr>
                                            <th className="py-3 px-4 font-semibold">Nomor SPJ</th>
                                            <th className="py-3 px-4 font-semibold">Kegiatan Anggaran</th>
                                            <th className="py-3 px-4 font-semibold">Pengaju / Seksi</th>
                                            <th className="py-3 px-4 font-semibold">Nominal</th>
                                            <th className="py-3 px-4 font-semibold">Periode</th>
                                            <th className="py-3 px-4 font-semibold">Status</th>
                                            <th className="py-3 px-4 font-semibold text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {items.map((spj) => (
                                            <tr key={spj.id} className="hover:bg-neutral-50/60 transition-colors">
                                                <td className="py-3.5 px-4 font-mono font-bold text-neutral-900">
                                                    {spj.nomor_spj || (
                                                        <span className="text-neutral-400 font-sans italic text-[11px]">
                                                            Menunggu Penomoran
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="py-3.5 px-4 text-neutral-800">
                                                    <span className="font-semibold block">{spj.kegiatan?.nama}</span>
                                                    <span className="text-[10px] text-neutral-400">
                                                        Diajukan: {formatDate(spj.tanggal_pengajuan)}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-4 text-neutral-600">
                                                    <span className="font-medium text-neutral-900 block">
                                                        {spj.diajukan_oleh?.name || spj.pengaju?.name}
                                                    </span>
                                                    <span className="text-[10px] text-neutral-400 uppercase">
                                                        Seksi {spj.kegiatan?.kasi?.seksi || 'Kasi'}
                                                    </span>
                                                </td>
                                                <td className="py-3.5 px-4 font-bold text-neutral-900">
                                                    {formatRupiah(spj.nominal)}
                                                </td>
                                                <td className="py-3.5 px-4 text-neutral-600">
                                                    Bulan {spj.periode_bulan} / {spj.periode_tahun}
                                                </td>
                                                <td className="py-3.5 px-4">
                                                    <StatusBadge status={spj.status} />
                                                </td>
                                                <td className="py-3.5 px-4 text-right">
                                                    <Link
                                                        href={`/spj/${spj.id}`}
                                                        className="px-2.5 py-1.5 rounded-btn text-xs font-semibold text-primary hover:bg-primary/10 transition-colors inline-flex items-center gap-1"
                                                    >
                                                        <Eye className="w-3.5 h-3.5" /> Detail
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination Controls */}
                            {meta && meta.last_page > 1 && (
                                <div className="p-4 border-t border-neutral-100 flex items-center justify-between text-xs text-neutral-500">
                                    <span>
                                        Menampilkan <strong>{meta.from}</strong> - <strong>{meta.to}</strong> dari <strong>{meta.total}</strong> SPJ
                                    </span>

                                    <div className="flex items-center gap-1">
                                        {meta.links.map((link, idx) => (
                                            <button
                                                key={idx}
                                                disabled={!link.url}
                                                onClick={() => link.url && router.visit(link.url)}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                                className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-colors ${
                                                    link.active
                                                        ? 'bg-primary text-white shadow-xs'
                                                        : link.url
                                                        ? 'text-neutral-700 hover:bg-neutral-100'
                                                        : 'text-neutral-300 cursor-not-allowed'
                                                }`}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
