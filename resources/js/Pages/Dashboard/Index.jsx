import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import {
    ReceiptText,
    CheckCircle,
    Clock,
    AlertTriangle,
    Plus,
    FolderTree,
    ArrowUpRight,
    Paperclip,
    ShieldCheck,
    Calendar,
    ChevronRight,
    Sparkles,
} from 'lucide-react';

export default function DashboardIndex({ stats = {}, recentBelanja = [], userRole = 'operator' }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });
    };

    return (
        <AuthenticatedLayout title="Dashboard SPJ & Bukti Belanja">
            <Head title="Dashboard Ringkasan" />

            {/* Welcome Banner */}
            <div className="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white rounded-2xl p-6 sm:p-8 shadow-lg mb-6 relative overflow-hidden">
                <div className="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none" />
                
                <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 text-indigo-200 text-xs font-medium mb-3 backdrop-blur border border-white/10">
                            <Sparkles className="w-3.5 h-3.5 text-amber-300" />
                            <span>Kecamatan Caringin — SIMPEL KAN v2.0</span>
                        </div>
                        <h1 className="text-xl sm:text-2xl font-bold tracking-tight">
                            Selamat Datang, {user?.name || 'Pengguna'}!
                        </h1>
                        <p className="text-xs sm:text-sm text-neutral-300 mt-1 max-w-xl">
                            Aplikasi pengarsipan digital bukti belanja RAP/SPJ. Mudah dicari, cepat diverifikasi, dan aman diaudit.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        {userRole === 'sekmat' ? (
                            <Link
                                href="/verifikasi/sekmat"
                                className="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition-all"
                            >
                                <Clock className="w-4 h-4" />
                                Antrean Verifikasi ({stats.menunggu_sekmat || 0})
                            </Link>
                        ) : userRole === 'camat' ? (
                            <Link
                                href="/verifikasi/camat"
                                className="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl shadow-md transition-all"
                            >
                                <ShieldCheck className="w-4 h-4" />
                                Antrean Persetujuan ({stats.menunggu_camat || 0})
                            </Link>
                        ) : (
                            <Link
                                href="/belanja/create"
                                className="inline-flex items-center gap-2 bg-indigo-500 hover:bg-indigo-600 text-white font-semibold text-xs px-4 py-2.5 rounded-xl shadow-md transition-all"
                            >
                                <Plus className="w-4 h-4" />
                                Catat Belanja Baru
                            </Link>
                        )}

                        <Link
                            href="/program"
                            className="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-medium px-3.5 py-2.5 rounded-xl backdrop-blur border border-white/10 transition-colors"
                        >
                            <FolderTree className="w-3.5 h-3.5" />
                            Hierarki RAP
                        </Link>
                    </div>
                </div>
            </div>

            {/* 4 Summary Stat Cards (Angka Ringkasan Sesuai Mandat Rapat) */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                {/* 1. Total Belanja */}
                <div className="bg-white rounded-xl p-5 border border-neutral-200/80 shadow-sm hover:shadow transition-shadow">
                    <div className="flex items-center justify-between text-neutral-500 mb-2">
                        <span className="text-xs font-semibold uppercase tracking-wider">Total Belanja</span>
                        <div className="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                            <ReceiptText className="w-4 h-4" />
                        </div>
                    </div>
                    <div className="text-2xl font-extrabold text-neutral-900 font-mono">
                        {stats.total_belanja || 0}
                    </div>
                    <div className="text-xs text-neutral-500 mt-1 flex items-center justify-between">
                        <span>Nilai Total:</span>
                        <span className="font-semibold text-neutral-800 font-mono">{formatRupiah(stats.total_nominal)}</span>
                    </div>
                </div>

                {/* 2. Disetujui Camat */}
                <div className="bg-white rounded-xl p-5 border border-neutral-200/80 shadow-sm hover:shadow transition-shadow">
                    <div className="flex items-center justify-between text-neutral-500 mb-2">
                        <span className="text-xs font-semibold uppercase tracking-wider">Disetujui Resmi</span>
                        <div className="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                            <CheckCircle className="w-4 h-4" />
                        </div>
                    </div>
                    <div className="text-2xl font-extrabold text-emerald-700 font-mono">
                        {stats.disetujui || 0}
                    </div>
                    <div className="text-xs text-neutral-500 mt-1 flex items-center justify-between">
                        <span>Nilai Disetujui:</span>
                        <span className="font-semibold text-emerald-700 font-mono">{formatRupiah(stats.nominal_disetujui)}</span>
                    </div>
                </div>

                {/* 3. Menunggu Verifikasi */}
                <div className="bg-white rounded-xl p-5 border border-neutral-200/80 shadow-sm hover:shadow transition-shadow">
                    <div className="flex items-center justify-between text-neutral-500 mb-2">
                        <span className="text-xs font-semibold uppercase tracking-wider">Menunggu Verifikasi</span>
                        <div className="p-2 rounded-lg bg-amber-50 text-amber-600">
                            <Clock className="w-4 h-4" />
                        </div>
                    </div>
                    <div className="text-2xl font-extrabold text-amber-700 font-mono">
                        {(stats.menunggu_sekmat || 0) + (stats.menunggu_camat || 0)}
                    </div>
                    <div className="text-[11px] text-neutral-500 mt-1 flex items-center justify-between">
                        <span>Sekmat: <strong>{stats.menunggu_sekmat || 0}</strong></span>
                        <span>Camat: <strong>{stats.menunggu_camat || 0}</strong></span>
                    </div>
                </div>

                {/* 4. Bukti Belum Lengkap / Revisi */}
                <div className="bg-white rounded-xl p-5 border border-neutral-200/80 shadow-sm hover:shadow transition-shadow">
                    <div className="flex items-center justify-between text-neutral-500 mb-2">
                        <span className="text-xs font-semibold uppercase tracking-wider">Perlu Tindakan</span>
                        <div className="p-2 rounded-lg bg-rose-50 text-rose-600">
                            <AlertTriangle className="w-4 h-4" />
                        </div>
                    </div>
                    <div className="text-2xl font-extrabold text-rose-600 font-mono">
                        {(stats.belum_lengkap || 0) + (stats.dikembalikan || 0)}
                    </div>
                    <div className="text-[11px] text-neutral-500 mt-1 flex items-center justify-between">
                        <span>Bukti Kurang: <strong>{stats.belum_lengkap || 0}</strong></span>
                        <span>Revisi: <strong>{stats.dikembalikan || 0}</strong></span>
                    </div>
                </div>
            </div>

            {/* Recent Belanja Activity Table */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 overflow-hidden mb-6">
                <div className="p-5 border-b border-neutral-100 flex items-center justify-between">
                    <div>
                        <h2 className="text-sm font-bold text-neutral-900 flex items-center gap-2">
                            <ReceiptText className="w-4 h-4 text-indigo-600" />
                            Belanja Terbaru & Status Dokumen Bukti
                        </h2>
                        <p className="text-xs text-neutral-500 mt-0.5">
                            Daftar transaksi belanja terakhir yang dicatat dalam sistem.
                        </p>
                    </div>

                    <Link
                        href="/belanja"
                        className="text-xs font-semibold text-indigo-600 hover:text-indigo-700 inline-flex items-center gap-1"
                    >
                        Lihat Semua Belanja &rarr;
                    </Link>
                </div>

                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-neutral-50 text-neutral-600 uppercase tracking-wider text-[10px] font-semibold border-b border-neutral-200">
                            <tr>
                                <th className="px-4 py-3">Tanggal</th>
                                <th className="px-4 py-3">Uraian Belanja</th>
                                <th className="px-4 py-3">Jenis</th>
                                <th className="px-4 py-3 text-right">Nominal</th>
                                <th className="px-4 py-3 text-center">Bukti Belanja</th>
                                <th className="px-4 py-3 text-center">Status Verifikasi</th>
                                <th className="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-100">
                            {recentBelanja.length === 0 ? (
                                <tr>
                                    <td colSpan="7" className="p-8 text-center text-neutral-400">
                                        Belum ada data belanja yang dicatat.
                                    </td>
                                </tr>
                            ) : (
                                recentBelanja.map((item) => (
                                    <tr key={item.id} className="hover:bg-neutral-50/70 transition-colors">
                                        <td className="px-4 py-3 whitespace-nowrap text-neutral-600 font-medium">
                                            {formatDate(item.tanggal_belanja)}
                                        </td>
                                        <td className="px-4 py-3 max-w-xs">
                                            <div className="font-semibold text-neutral-900 truncate">
                                                {item.uraian}
                                            </div>
                                            <div className="text-[10px] text-neutral-400 truncate">
                                                {item.sub_kegiatan?.nama || '-'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700">
                                                {item.jenis_belanja}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-right font-mono font-bold text-neutral-900">
                                            {formatRupiah(item.nominal)}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-center">
                                            <div className="inline-flex items-center gap-1 text-[11px] text-neutral-600">
                                                <Paperclip className="w-3 h-3 text-neutral-400" />
                                                <span>{item.dokumen_bukti_count || 0} berkas</span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-center">
                                            <StatusBadge status={item.status_verifikasi} />
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap text-right">
                                            <Link
                                                href={`/belanja/${item.id}`}
                                                className="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-medium text-xs"
                                            >
                                                <span>Detail</span>
                                                <ChevronRight className="w-3.5 h-3.5" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
