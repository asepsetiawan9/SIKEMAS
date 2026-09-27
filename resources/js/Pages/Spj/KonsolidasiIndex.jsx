import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import ConfirmModal from '@/Components/ConfirmModal';
import EmptyState from '@/Components/EmptyState';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import {
    FileText,
    Send,
    FileCheck2,
    RotateCcw,
    Eye,
    ExternalLink,
    AlertCircle,
    Archive,
    Check,
} from 'lucide-react';
import { toast } from 'sonner';

export default function KonsolidasiIndex({
    pengajuanMasuk = [],
    perluRevisi = [],
    dikonsolidasi = [],
}) {
    const [activeTab, setActiveTab] = useState('pengajuan'); // 'pengajuan' | 'revisi' | 'siap'
    const [selectedSpj, setSelectedSpj] = useState(null);
    const [confirmAction, setConfirmAction] = useState(null); // 'konsolidasi' | 'ajukanVerifikasi'
    const [loading, setLoading] = useState(false);

    const handleOpenConfirm = (spj, action) => {
        setSelectedSpj(spj);
        setConfirmAction(action);
    };

    const handleCloseConfirm = () => {
        setSelectedSpj(null);
        setConfirmAction(null);
    };

    const executeAction = () => {
        if (!selectedSpj || !confirmAction) return;

        setLoading(true);
        if (confirmAction === 'konsolidasi') {
            router.put(`/spj/${selectedSpj.id}/konsolidasi`, {}, {
                onSuccess: () => {
                    toast.success('SPJ berhasil dikonsolidasi & diterbitkan nomor resmi.');
                    handleCloseConfirm();
                },
                onError: (err) => {
                    toast.error(err?.error || 'Gagal mengonsolidasi SPJ.');
                },
                onFinish: () => setLoading(false),
            });
        } else if (confirmAction === 'ajukanVerifikasi') {
            router.put(`/spj/${selectedSpj.id}/ajukan-verifikasi`, {}, {
                onSuccess: () => {
                    toast.success('SPJ berhasil diajukan untuk verifikasi Sekmat.');
                    handleCloseConfirm();
                },
                onError: (err) => {
                    toast.error(err?.error || 'Gagal mengajukan SPJ ke Sekmat.');
                },
                onFinish: () => setLoading(false),
            });
        }
    };

    return (
        <AuthenticatedLayout title="Konsolidasi SPJ Digital">
            <Head title="Konsolidasi SPJ - Staf Keuangan" />

            <div className="space-y-6">
                {/* Header Title & Top Navigation */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-neutral-900 tracking-tight">
                            Pengelolaan & Konsolidasi SPJ
                        </h2>
                        <p className="text-xs text-neutral-500 mt-1">
                            Pemeriksaan berkas pengajuan seksi, penerbitan nomor SPJ resmi, dan pengajuan verifikasi ke Sekmat
                        </p>
                    </div>

                    <Link
                        href="/spj?tab=arsip"
                        className="inline-flex items-center gap-2 px-3.5 py-2 rounded-btn bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-xs font-semibold transition-colors"
                    >
                        <Archive className="w-4 h-4 text-neutral-500" />
                        Buka Arsip SPJ Seluruhnya
                    </Link>
                </div>

                {/* Status Tabs Navigation */}
                <div className="flex items-center gap-2 border-b border-neutral-200 overflow-x-auto pb-px">
                    <button
                        onClick={() => setActiveTab('pengajuan')}
                        className={`inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-all shrink-0 ${
                            activeTab === 'pengajuan'
                                ? 'border-primary text-primary bg-primary/5 rounded-t-lg'
                                : 'border-transparent text-neutral-500 hover:text-neutral-800'
                        }`}
                    >
                        <FileText className="w-4 h-4" />
                        Pengajuan Masuk
                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                            pengajuanMasuk.length > 0
                                ? 'bg-primary text-white'
                                : 'bg-neutral-200 text-neutral-600'
                        }`}>
                            {pengajuanMasuk.length}
                        </span>
                    </button>

                    <button
                        onClick={() => setActiveTab('revisi')}
                        className={`inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-all shrink-0 ${
                            activeTab === 'revisi'
                                ? 'border-rose-600 text-rose-700 bg-rose-50/60 rounded-t-lg'
                                : 'border-transparent text-neutral-500 hover:text-neutral-800'
                        }`}
                    >
                        <RotateCcw className="w-4 h-4" />
                        Perlu Revisi (Ditolak)
                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                            perluRevisi.length > 0
                                ? 'bg-rose-600 text-white'
                                : 'bg-neutral-200 text-neutral-600'
                        }`}>
                            {perluRevisi.length}
                        </span>
                    </button>

                    <button
                        onClick={() => setActiveTab('siap')}
                        className={`inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold border-b-2 transition-all shrink-0 ${
                            activeTab === 'siap'
                                ? 'border-amber-500 text-amber-800 bg-amber-50/60 rounded-t-lg'
                                : 'border-transparent text-neutral-500 hover:text-neutral-800'
                        }`}
                    >
                        <Send className="w-4 h-4" />
                        Siap Diajukan ke Sekmat
                        <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${
                            dikonsolidasi.length > 0
                                ? 'bg-amber-500 text-neutral-900'
                                : 'bg-neutral-200 text-neutral-600'
                        }`}>
                            {dikonsolidasi.length}
                        </span>
                    </button>
                </div>

                {/* Tab 1: Pengajuan Masuk (Diajukan Kasi) */}
                {activeTab === 'pengajuan' && (
                    <div className="bg-surface rounded-card border border-neutral-200/80 shadow-sm overflow-hidden">
                        {pengajuanMasuk.length === 0 ? (
                            <EmptyState
                                icon={FileCheck2}
                                title="Tidak Ada Pengajuan Masuk"
                                description="Saat ini belum ada pengajuan SPJ baru dari para Kasi yang menunggu proses konsolidasi."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-neutral-50/80 border-b border-neutral-200 text-neutral-600">
                                        <tr>
                                            <th className="py-3 px-4 font-semibold">Tgl Pengajuan</th>
                                            <th className="py-3 px-4 font-semibold">Seksi / Pengaju</th>
                                            <th className="py-3 px-4 font-semibold">Kegiatan</th>
                                            <th className="py-3 px-4 font-semibold">Nominal</th>
                                            <th className="py-3 px-4 font-semibold">Berkas Bukti</th>
                                            <th className="py-3 px-4 font-semibold text-right">Aksi Konsolidasi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {pengajuanMasuk.map((spj) => (
                                            <tr key={spj.id} className="hover:bg-neutral-50/60 transition-colors">
                                                <td className="py-3 px-4 font-medium text-neutral-600">
                                                    {formatDate(spj.tanggal_pengajuan)}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <span className="font-bold text-neutral-900 block">
                                                        {spj.diajukan_oleh?.name || spj.pengaju?.name}
                                                    </span>
                                                    <span className="text-[11px] text-neutral-500 uppercase tracking-wider">
                                                        Seksi {spj.kegiatan?.kasi?.seksi || 'Kasi'}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 text-neutral-800">
                                                    <span className="font-semibold block">{spj.kegiatan?.nama}</span>
                                                    <span className="text-[10px] text-neutral-400">
                                                        Bulan {spj.periode_bulan} / {spj.periode_tahun}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 font-bold text-neutral-900">
                                                    {formatRupiah(spj.nominal)}
                                                </td>
                                                <td className="py-3 px-4">
                                                    {spj.file_bukti ? (
                                                        <a
                                                            href={`/storage/${spj.file_bukti}`}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-[11px] font-medium transition-colors"
                                                        >
                                                            <ExternalLink className="w-3 h-3 text-neutral-500" />
                                                            Lihat Bukti
                                                        </a>
                                                    ) : (
                                                        <span className="text-neutral-400">-</span>
                                                    )}
                                                </td>
                                                <td className="py-3 px-4 text-right space-x-2">
                                                    <Link
                                                        href={`/spj/${spj.id}`}
                                                        className="px-2.5 py-1.5 rounded-btn text-xs font-semibold text-neutral-600 hover:bg-neutral-100 transition-colors inline-flex items-center gap-1"
                                                    >
                                                        <Eye className="w-3.5 h-3.5" /> Detail
                                                    </Link>

                                                    <button
                                                        onClick={() => handleOpenConfirm(spj, 'konsolidasi')}
                                                        className="px-3 py-1.5 bg-primary hover:bg-primary-dark text-white rounded-btn text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
                                                    >
                                                        <FileCheck2 className="w-3.5 h-3.5" /> Konsolidasi & Terbitkan Nomor
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* Tab 2: Perlu Revisi (Ditolak Sekmat - BR-SPJ-03) */}
                {activeTab === 'revisi' && (
                    <div className="bg-surface rounded-card border border-neutral-200/80 shadow-sm overflow-hidden">
                        {perluRevisi.length === 0 ? (
                            <EmptyState
                                icon={Check}
                                title="Tidak Ada SPJ Ditolak"
                                description="Seluruh pengajuan SPJ berjalan lancar tanpa ada catatan penolakan yang perlu diperbaiki."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-rose-50/50 border-b border-rose-100 text-rose-800">
                                        <tr>
                                            <th className="py-3 px-4 font-semibold">Nomor SPJ</th>
                                            <th className="py-3 px-4 font-semibold">Kegiatan / Pengaju</th>
                                            <th className="py-3 px-4 font-semibold">Nominal</th>
                                            <th className="py-3 px-4 font-semibold">Catatan Penolakan Sekmat</th>
                                            <th className="py-3 px-4 font-semibold text-right">Aksi Revisi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {perluRevisi.map((spj) => (
                                            <tr key={spj.id} className="hover:bg-rose-50/20 transition-colors">
                                                <td className="py-3 px-4 font-mono font-bold text-neutral-900">
                                                    {spj.nomor_spj || `SPJ #${spj.id}`}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <span className="font-semibold text-neutral-900 block">{spj.kegiatan?.nama}</span>
                                                    <span className="text-[11px] text-neutral-500">
                                                        Diajukan oleh: {spj.diajukan_oleh?.name || spj.pengaju?.name}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 font-bold text-neutral-900">
                                                    {formatRupiah(spj.nominal)}
                                                </td>
                                                <td className="py-3 px-4 max-w-xs">
                                                    <div className="p-2 rounded bg-rose-50 border border-rose-200 text-rose-700 text-[11px] leading-relaxed">
                                                        <span className="font-bold block text-rose-800">Alasan:</span>
                                                        {spj.catatan_verifikasi || 'Berkas kelengkapan belum terpenuhi.'}
                                                    </div>
                                                </td>
                                                <td className="py-3 px-4 text-right space-x-2">
                                                    <Link
                                                        href={`/spj/${spj.id}`}
                                                        className="px-2.5 py-1.5 rounded-btn text-xs font-semibold text-neutral-600 hover:bg-neutral-100 transition-colors inline-flex items-center gap-1"
                                                    >
                                                        <Eye className="w-3.5 h-3.5" /> Detail
                                                    </Link>

                                                    <button
                                                        onClick={() => handleOpenConfirm(spj, 'konsolidasi')}
                                                        className="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-btn text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
                                                    >
                                                        <RotateCcw className="w-3.5 h-3.5" /> Konsolidasi Ulang
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}

                {/* Tab 3: Siap Diajukan ke Sekmat (Dikonsolidasi) */}
                {activeTab === 'siap' && (
                    <div className="bg-surface rounded-card border border-neutral-200/80 shadow-sm overflow-hidden">
                        {dikonsolidasi.length === 0 ? (
                            <EmptyState
                                icon={Send}
                                title="Tidak Ada SPJ Siap Diajukan"
                                description="Belum ada SPJ bernomor resmi yang sedang menunggu pengajuan verifikasi ke Sekmat."
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs">
                                    <thead className="bg-neutral-50/80 border-b border-neutral-200 text-neutral-600">
                                        <tr>
                                            <th className="py-3 px-4 font-semibold">Nomor SPJ Resmi</th>
                                            <th className="py-3 px-4 font-semibold">Kegiatan</th>
                                            <th className="py-3 px-4 font-semibold">Nominal</th>
                                            <th className="py-3 px-4 font-semibold">Tgl Konsolidasi</th>
                                            <th className="py-3 px-4 font-semibold">Status</th>
                                            <th className="py-3 px-4 font-semibold text-right">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-neutral-100">
                                        {dikonsolidasi.map((spj) => (
                                            <tr key={spj.id} className="hover:bg-neutral-50/60 transition-colors">
                                                <td className="py-3 px-4 font-mono font-bold text-neutral-900">
                                                    {spj.nomor_spj}
                                                </td>
                                                <td className="py-3 px-4 text-neutral-800">
                                                    <span className="font-semibold block">{spj.kegiatan?.nama}</span>
                                                    <span className="text-[11px] text-neutral-500">
                                                        {spj.diajukan_oleh?.name || spj.pengaju?.name}
                                                    </span>
                                                </td>
                                                <td className="py-3 px-4 font-bold text-neutral-900">
                                                    {formatRupiah(spj.nominal)}
                                                </td>
                                                <td className="py-3 px-4 text-neutral-600">
                                                    {formatDate(spj.tanggal_konsolidasi)}
                                                </td>
                                                <td className="py-3 px-4">
                                                    <StatusBadge status={spj.status} />
                                                </td>
                                                <td className="py-3 px-4 text-right space-x-2">
                                                    <Link
                                                        href={`/spj/${spj.id}`}
                                                        className="px-2.5 py-1.5 rounded-btn text-xs font-semibold text-neutral-600 hover:bg-neutral-100 transition-colors inline-flex items-center gap-1"
                                                    >
                                                        <Eye className="w-3.5 h-3.5" /> Detail
                                                    </Link>

                                                    <button
                                                        onClick={() => handleOpenConfirm(spj, 'ajukanVerifikasi')}
                                                        className="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-neutral-950 rounded-btn text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
                                                    >
                                                        <Send className="w-3.5 h-3.5" /> Ajukan ke Sekmat
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Confirmation Modal */}
            <ConfirmModal
                isOpen={!!selectedSpj}
                isLoading={loading}
                onClose={handleCloseConfirm}
                onConfirm={executeAction}
                title={
                    confirmAction === 'konsolidasi'
                        ? 'Konfirmasi Konsolidasi SPJ'
                        : 'Ajukan SPJ ke Sekmat'
                }
                message={
                    confirmAction === 'konsolidasi'
                        ? `Sistem akan menerbitkan nomor SPJ resmi untuk pengajuan ini dan mengunci status ke 'dikonsolidasi'. Apakah Anda yakin berkas telah lengkap?`
                        : `SPJ dengan nomor ${selectedSpj?.nomor_spj} akan diteruskan ke antrean verifikasi Sekretaris Kecamatan (Sekmat). Lanjutkan pengajuan?`
                }
                confirmText={
                    confirmAction === 'konsolidasi'
                        ? 'Terbitkan & Konsolidasi'
                        : 'Kirim ke Sekmat'
                }
                variant={confirmAction === 'konsolidasi' ? 'primary' : 'warning'}
            />
        </AuthenticatedLayout>
    );
}
