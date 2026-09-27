import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ConfirmModal from '@/Components/ConfirmModal';
import EmptyState from '@/Components/EmptyState';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import {
    CheckCircle2,
    XCircle,
    FileText,
    ExternalLink,
    Clock,
    Check,
    AlertTriangle,
    Eye,
    Archive,
} from 'lucide-react';
import { toast } from 'sonner';

export default function VerifikasiIndex({ antrean = [], stats = {} }) {
    const [selectedSpj, setSelectedSpj] = useState(null);
    const [actionType, setActionType] = useState(null); // 'approve' | 'reject'
    const [rejectCatatan, setRejectCatatan] = useState('');
    const [loading, setLoading] = useState(false);

    const handleOpenApprove = (spj) => {
        setSelectedSpj(spj);
        setActionType('approve');
        setRejectCatatan('');
    };

    const handleOpenReject = (spj) => {
        setSelectedSpj(spj);
        setActionType('reject');
        setRejectCatatan('');
    };

    const handleClose = () => {
        setSelectedSpj(null);
        setActionType(null);
        setRejectCatatan('');
    };

    const handleExecute = () => {
        if (!selectedSpj || !actionType) return;

        if (actionType === 'reject' && (!rejectCatatan || rejectCatatan.trim().length < 10)) {
            toast.error('Catatan penolakan wajib diisi minimal 10 karakter (BR-SPJ-05).');
            return;
        }

        setLoading(true);
        router.put(`/spj/${selectedSpj.id}/verifikasi`, {
            approved: actionType === 'approve',
            catatan: actionType === 'reject' ? rejectCatatan : null,
        }, {
            onSuccess: () => {
                if (actionType === 'approve') {
                    toast.success(`SPJ ${selectedSpj.nomor_spj} berhasil disetujui & diverifikasi.`);
                } else {
                    toast.success(`SPJ ${selectedSpj.nomor_spj} berhasil ditolak dengan catatan.`);
                }
                handleClose();
            },
            onError: (err) => {
                const msg = err?.catatan || err?.error || 'Gagal memproses verifikasi SPJ.';
                toast.error(msg);
            },
            onFinish: () => setLoading(false),
        });
    };

    return (
        <AuthenticatedLayout title="Verifikasi SPJ - Sekmat">
            <Head title="Verifikasi SPJ Sekmat" />

            <div className="space-y-6">
                {/* Header Title */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 className="text-xl font-bold text-neutral-900 tracking-tight">
                            Antrean Verifikasi SPJ
                        </h2>
                        <p className="text-xs text-neutral-500 mt-1">
                            Persetujuan satu tahap (Sekmat) untuk pencairan dan pengesahan bukti pertanggungjawaban anggaran
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

                {/* Stats Summary Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div className="bg-surface p-5 rounded-card border border-neutral-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <span className="text-xs font-medium text-neutral-500 block">
                                Menunggu Verifikasi
                            </span>
                            <span className="text-2xl font-bold text-amber-600 mt-1 block">
                                {stats.menunggu ?? antrean.length}
                            </span>
                        </div>
                        <div className="p-3 rounded-xl bg-amber-50 text-amber-600">
                            <Clock className="w-6 h-6" />
                        </div>
                    </div>

                    <div className="bg-surface p-5 rounded-card border border-neutral-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <span className="text-xs font-medium text-neutral-500 block">
                                Disetujui Bulan Ini
                            </span>
                            <span className="text-2xl font-bold text-emerald-600 mt-1 block">
                                {stats.diverifikasi_bulan_ini ?? 0}
                            </span>
                        </div>
                        <div className="p-3 rounded-xl bg-emerald-50 text-emerald-600">
                            <Check className="w-6 h-6" />
                        </div>
                    </div>

                    <div className="bg-surface p-5 rounded-card border border-neutral-200/80 shadow-xs flex items-center justify-between">
                        <div>
                            <span className="text-xs font-medium text-neutral-500 block">
                                Ditolak Bulan Ini
                            </span>
                            <span className="text-2xl font-bold text-rose-600 mt-1 block">
                                {stats.ditolak_bulan_ini ?? 0}
                            </span>
                        </div>
                        <div className="p-3 rounded-xl bg-rose-50 text-rose-600">
                            <AlertTriangle className="w-6 h-6" />
                        </div>
                    </div>
                </div>

                {/* Antrean Table */}
                <div className="bg-surface rounded-card border border-neutral-200/80 shadow-sm overflow-hidden">
                    <div className="p-4 border-b border-neutral-200/80 bg-neutral-50/50 flex justify-between items-center">
                        <span className="text-xs font-bold text-neutral-800">
                            Daftar Pengajuan SPJ Siap Diverifikasi ({antrean.length})
                        </span>
                        <span className="text-[11px] text-neutral-500">
                            Urut berdasarkan tanggal pengajuan terlama
                        </span>
                    </div>

                    {antrean.length === 0 ? (
                        <EmptyState
                            icon={CheckCircle2}
                            title="Semua SPJ Telah Diverifikasi"
                            description="Tidak ada berkas SPJ yang tertahan di antrean verifikasi saat ini. Pekerjaan Anda telah tuntas!"
                        />
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="bg-neutral-50/90 border-b border-neutral-200 text-neutral-600">
                                    <tr>
                                        <th className="py-3 px-4 font-semibold">Nomor SPJ</th>
                                        <th className="py-3 px-4 font-semibold">Seksi & Pengaju</th>
                                        <th className="py-3 px-4 font-semibold">Kegiatan Anggaran</th>
                                        <th className="py-3 px-4 font-semibold">Nominal</th>
                                        <th className="py-3 px-4 font-semibold">Dokumen Bukti</th>
                                        <th className="py-3 px-4 font-semibold text-right">Keputusan Verifikasi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-100">
                                    {antrean.map((spj) => (
                                        <tr key={spj.id} className="hover:bg-neutral-50/60 transition-colors">
                                            <td className="py-3.5 px-4 font-mono font-bold text-neutral-900">
                                                {spj.nomor_spj}
                                                <span className="block text-[10px] font-sans font-normal text-neutral-400">
                                                    Dikonsolidasi: {formatDate(spj.tanggal_konsolidasi)}
                                                </span>
                                            </td>
                                            <td className="py-3.5 px-4">
                                                <span className="font-semibold text-neutral-900 block">
                                                    {spj.diajukan_oleh?.name || spj.pengaju?.name}
                                                </span>
                                                <span className="text-[11px] text-neutral-500 capitalize">
                                                    Seksi {spj.kegiatan?.kasi?.seksi || 'Kasi'}
                                                </span>
                                            </td>
                                            <td className="py-3.5 px-4 text-neutral-800">
                                                <span className="font-semibold block">{spj.kegiatan?.nama}</span>
                                                <span className="text-[10px] text-neutral-500">
                                                    Periode: Bulan {spj.periode_bulan} / {spj.periode_tahun}
                                                </span>
                                            </td>
                                            <td className="py-3.5 px-4 font-bold text-neutral-900 text-sm">
                                                {formatRupiah(spj.nominal)}
                                            </td>
                                            <td className="py-3.5 px-4">
                                                {spj.file_bukti ? (
                                                    <a
                                                        href={`/storage/${spj.file_bukti}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded bg-neutral-100 hover:bg-neutral-200 text-neutral-700 text-[11px] font-medium transition-colors"
                                                    >
                                                        <ExternalLink className="w-3 h-3 text-neutral-500" />
                                                        Buka Berkas
                                                    </a>
                                                ) : (
                                                    <span className="text-neutral-400">-</span>
                                                )}
                                            </td>
                                            <td className="py-3.5 px-4 text-right space-x-2">
                                                <Link
                                                    href={`/spj/${spj.id}`}
                                                    className="px-2.5 py-1.5 rounded-btn text-xs font-semibold text-neutral-600 hover:bg-neutral-100 transition-colors inline-flex items-center gap-1"
                                                >
                                                    <Eye className="w-3.5 h-3.5" /> Detail
                                                </Link>

                                                <button
                                                    onClick={() => handleOpenReject(spj)}
                                                    className="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-btn text-xs font-bold transition-colors inline-flex items-center gap-1"
                                                >
                                                    <XCircle className="w-3.5 h-3.5" /> Tolak
                                                </button>

                                                <button
                                                    onClick={() => handleOpenApprove(spj)}
                                                    className="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-btn text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1"
                                                >
                                                    <CheckCircle2 className="w-3.5 h-3.5" /> Setujui
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Konfirmasi Persetujuan (Approve) */}
            <ConfirmModal
                isOpen={!!selectedSpj && actionType === 'approve'}
                isLoading={loading}
                onClose={handleClose}
                onConfirm={handleExecute}
                title="Persetujuan & Pengesahan SPJ"
                message={`Anda akan menyetujui pengesahan SPJ Nomor ${selectedSpj?.nomor_spj} sebesar ${formatRupiah(selectedSpj?.nominal)}. Pengesahan bersifat final dan tidak dapat dibatalkan (BR-SPJ-06).`}
                confirmText="Sah & Setujui SPJ"
                variant="success"
            />

            {/* Modal Penolakan (Reject) dengan Catatan Wajib BR-SPJ-05 */}
            <ConfirmModal
                isOpen={!!selectedSpj && actionType === 'reject'}
                isLoading={loading}
                onClose={handleClose}
                onConfirm={handleExecute}
                title="Tolak Pengajuan SPJ"
                message={`SPJ Nomor ${selectedSpj?.nomor_spj} akan ditolak dan dikembalikan ke Staf Keuangan untuk revisi. Berikan alasan penolakan secara jelas.`}
                confirmText="Tolak & Kembalikan"
                variant="danger"
            >
                <div className="mt-3 space-y-1.5 text-left">
                    <label className="block text-xs font-bold text-neutral-800">
                        Catatan Alasan Penolakan <span className="text-rose-500">* (Min. 10 karakter)</span>
                    </label>
                    <textarea
                        rows="3"
                        value={rejectCatatan}
                        onChange={(e) => setRejectCatatan(e.target.value)}
                        placeholder="Contoh: Bukti kuitansi pada lampiran halaman 2 tidak bertanda tangan basah dan belum terdapat cap stempel toko."
                        className="w-full p-2.5 rounded-lg border border-neutral-300 text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all placeholder:text-neutral-400"
                    />
                    <div className="flex justify-between items-center text-[10px] text-neutral-500">
                        <span>Minimal 10 karakter sesuai BR-SPJ-05</span>
                        <span className={rejectCatatan.length < 10 ? 'text-rose-500 font-bold' : 'text-emerald-600 font-bold'}>
                            {rejectCatatan.length} / 10 karakter
                        </span>
                    </div>
                </div>
            </ConfirmModal>
        </AuthenticatedLayout>
    );
}
