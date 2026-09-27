import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import ConfirmModal from '@/Components/ConfirmModal';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import {
    ArrowLeft,
    Calendar,
    User,
    FileText,
    ExternalLink,
    Clock,
    CheckCircle2,
    XCircle,
    Send,
    RotateCcw,
    AlertCircle,
    Building2,
    FileSpreadsheet,
    ShieldAlert,
    UploadCloud,
    X,
} from 'lucide-react';
import { toast } from 'sonner';

export default function Show({ spj, logs = [], sisa_pagu_kegiatan = 0 }) {
    const { auth } = usePage().props;
    const userRole = auth?.user?.role;
    const currentUserId = auth?.user?.id;

    const [modalAction, setModalAction] = useState(null); // 'konsolidasi' | 'ajukanVerifikasi' | 'approve' | 'reject'
    const [rejectCatatan, setRejectCatatan] = useState('');
    const [loading, setLoading] = useState(false);

    // FLOW-01: State untuk modal unggah bukti revisi
    const [revisiModalOpen, setRevisiModalOpen] = useState(false);
    const [revisiFile, setRevisiFile] = useState(null);
    const [revisiCatatan, setRevisiCatatan] = useState('');
    const [revisiLoading, setRevisiLoading] = useState(false);

    const isKasiPengaju = userRole === 'kasi' && currentUserId === spj.diajukan_oleh;
    const isStafKeuangan = userRole === 'staf_keuangan';
    const isSekmat = userRole === 'sekmat';

    const handleRevisiSubmit = (e) => {
        e.preventDefault();
        if (!revisiFile) {
            toast.error('Pilih berkas bukti dokumen yang telah diperbaiki.');
            return;
        }

        const formData = new FormData();
        formData.append('file_bukti', revisiFile);
        if (revisiCatatan) {
            formData.append('catatan', revisiCatatan);
        }

        setRevisiLoading(true);
        router.post(`/spj/${spj.id}/revisi-bukti`, formData, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Berkas bukti revisi berhasil diunggah.');
                setRevisiModalOpen(false);
                setRevisiFile(null);
                setRevisiCatatan('');
            },
            onError: (err) => {
                toast.error(err?.file_bukti || err?.error || 'Gagal mengunggah berkas revisi.');
            },
            onFinish: () => setRevisiLoading(false),
        });
    };

    const handleExecuteAction = () => {
        if (!modalAction) return;

        if (modalAction === 'reject' && (!rejectCatatan || rejectCatatan.trim().length < 10)) {
            toast.error('Catatan penolakan wajib diisi minimal 10 karakter (BR-SPJ-05).');
            return;
        }

        setLoading(true);

        if (modalAction === 'konsolidasi') {
            router.put(`/spj/${spj.id}/konsolidasi`, {}, {
                onSuccess: () => {
                    toast.success('SPJ berhasil dikonsolidasi.');
                    setModalAction(null);
                },
                onError: (err) => toast.error(err?.error || 'Gagal konsolidasi SPJ.'),
                onFinish: () => setLoading(false),
            });
        } else if (modalAction === 'ajukanVerifikasi') {
            router.put(`/spj/${spj.id}/ajukan-verifikasi`, {}, {
                onSuccess: () => {
                    toast.success('SPJ berhasil diajukan untuk verifikasi Sekmat.');
                    setModalAction(null);
                },
                onError: (err) => toast.error(err?.error || 'Gagal mengajukan SPJ.'),
                onFinish: () => setLoading(false),
            });
        } else if (modalAction === 'approve' || modalAction === 'reject') {
            router.put(`/spj/${spj.id}/verifikasi`, {
                approved: modalAction === 'approve',
                catatan: modalAction === 'reject' ? rejectCatatan : null,
            }, {
                onSuccess: () => {
                    toast.success(modalAction === 'approve' ? 'SPJ berhasil disahkan.' : 'SPJ berhasil ditolak.');
                    setModalAction(null);
                },
                onError: (err) => toast.error(err?.catatan || err?.error || 'Gagal verifikasi SPJ.'),
                onFinish: () => setLoading(false),
            });
        }
    };

    return (
        <AuthenticatedLayout title={`SPJ: ${spj?.nomor_spj || 'Detail Pengajuan'}`}>
            <Head title={`SPJ - ${spj?.nomor_spj || 'Detail'}`} />

            <div className="max-w-5xl mx-auto space-y-6">
                {/* Top Action Bar */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <Link
                        href="/spj"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" /> Kembali ke Daftar SPJ
                    </Link>

                    {/* Role-Specific Contextual Actions */}
                    <div className="flex flex-wrap items-center gap-2">
                        {(isKasiPengaju || isStafKeuangan) && spj.status === 'ditolak' && (
                            <button
                                onClick={() => setRevisiModalOpen(true)}
                                className="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-btn text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
                            >
                                <UploadCloud className="w-3.5 h-3.5" />
                                Unggah Berkas Bukti Revisi
                            </button>
                        )}

                        {isStafKeuangan && (spj.status === 'diajukan_kasi' || spj.status === 'ditolak') && (
                            <button
                                onClick={() => setModalAction('konsolidasi')}
                                className="px-3.5 py-2 bg-primary hover:bg-primary-dark text-white rounded-btn text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
                            >
                                <RotateCcw className="w-3.5 h-3.5" />
                                {spj.status === 'ditolak' ? 'Konsolidasi Ulang SPJ' : 'Konsolidasi & Terbitkan Nomor'}
                            </button>
                        )}

                        {isStafKeuangan && spj.status === 'dikonsolidasi' && (
                            <button
                                onClick={() => setModalAction('ajukanVerifikasi')}
                                className="px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-neutral-950 rounded-btn text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
                            >
                                <Send className="w-3.5 h-3.5" /> Ajukan ke Sekmat
                            </button>
                        )}

                        {isSekmat && spj.status === 'diajukan_verifikasi' && (
                            <>
                                <button
                                    onClick={() => setModalAction('reject')}
                                    className="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-btn text-xs font-bold transition-colors inline-flex items-center gap-1.5"
                                >
                                    <XCircle className="w-3.5 h-3.5" /> Tolak SPJ
                                </button>
                                <button
                                    onClick={() => setModalAction('approve')}
                                    className="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-btn text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
                                >
                                    <CheckCircle2 className="w-3.5 h-3.5" /> Setujui & Sahkan
                                </button>
                            </>
                        )}
                    </div>
                </div>

                {/* Primary Card */}
                <div className="bg-surface rounded-card p-6 sm:p-8 border border-neutral-200/80 shadow-sm space-y-6">
                    {/* Header info */}
                    <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-neutral-100 pb-5">
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-xs font-mono text-neutral-500">
                                    ID SPJ: #{spj.id}
                                </span>
                                {spj.nomor_spj && (
                                    <span className="px-2 py-0.5 rounded bg-primary/10 text-primary text-[11px] font-mono font-bold">
                                        Resmi
                                    </span>
                                )}
                            </div>
                            <h1 className="text-xl sm:text-2xl font-bold text-neutral-900 tracking-tight mt-1">
                                {spj.nomor_spj || 'Menunggu Penomoran (Draft Pengajuan)'}
                            </h1>
                            <p className="text-xs text-neutral-500 mt-1">
                                Kegiatan: <span className="font-semibold text-neutral-700">{spj.kegiatan?.nama}</span>
                            </p>
                        </div>
                        <StatusBadge status={spj.status} />
                    </div>

                    {/* Catatan Penolakan jika ada */}
                    {spj.status === 'ditolak' && (
                        <div className="p-4 rounded-xl bg-rose-50 border border-rose-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div className="flex items-start gap-3">
                                <ShieldAlert className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                                <div className="text-xs text-rose-800 space-y-1">
                                    <span className="font-bold block text-rose-900">
                                        Catatan Penolakan dari Sekmat (Perlu Revisi Dokumen):
                                    </span>
                                    <p className="leading-relaxed whitespace-pre-wrap">{spj.catatan_verifikasi || 'Dokumen bukti belum lengkap atau terdapat ketidaksesuaian.'}</p>
                                </div>
                            </div>
                            {(isKasiPengaju || isStafKeuangan) && (
                                <button
                                    onClick={() => setRevisiModalOpen(true)}
                                    className="shrink-0 px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-btn text-xs font-bold shadow-sm transition-all inline-flex items-center gap-1.5"
                                >
                                    <UploadCloud className="w-4 h-4" />
                                    Unggah Bukti Revisi
                                </button>
                            )}
                        </div>
                    )}

                    {/* 2-Column Info Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                        {/* Left column: Detail Keuangan */}
                        <div className="space-y-4">
                            <h3 className="font-bold text-neutral-900 uppercase tracking-wider text-[11px] border-b border-neutral-100 pb-2">
                                Informasi Anggaran & Realisasi
                            </h3>

                            <div className="p-4 rounded-xl bg-neutral-50 border border-neutral-100 space-y-3">
                                <div className="flex justify-between items-center">
                                    <span className="text-neutral-500">Nominal Pertanggungjawaban:</span>
                                    <span className="text-base font-bold text-neutral-900">
                                        {formatRupiah(spj.nominal)}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center pt-2 border-t border-neutral-200/60">
                                    <span className="text-neutral-500">Periode Anggaran:</span>
                                    <span className="font-semibold text-neutral-800">
                                        Bulan {spj.periode_bulan} / {spj.periode_tahun}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center pt-2 border-t border-neutral-200/60">
                                    <span className="text-neutral-500">Jenis Belanja / Kebutuhan:</span>
                                    <span className="font-semibold text-neutral-800 text-right">
                                        {spj.jenis_belanja || 'Belanja Operasional / Barang Jasa'}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center pt-2 border-t border-neutral-200/60">
                                    <span className="text-neutral-500">Sisa Pagu Kegiatan Terkini:</span>
                                    <span className="font-bold text-emerald-700">
                                        {formatRupiah(sisa_pagu_kegiatan)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Right column: Para Pihak & Timeline Tanggal */}
                        <div className="space-y-4">
                            <h3 className="font-bold text-neutral-900 uppercase tracking-wider text-[11px] border-b border-neutral-100 pb-2">
                                Para Pihak & Verifikasi
                            </h3>

                            <div className="space-y-3">
                                <div className="flex items-start gap-3 p-3 rounded-lg bg-neutral-50 border border-neutral-100">
                                    <User className="w-4 h-4 text-neutral-500 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="text-[10px] text-neutral-400 uppercase tracking-wider block">
                                            Diajukan Oleh (Kasi)
                                        </span>
                                        <span className="font-bold text-neutral-900">
                                            {spj.diajukan_oleh?.name || spj.pengaju?.name}
                                        </span>
                                        <span className="text-[11px] text-neutral-500 block">
                                            Tanggal: {formatDate(spj.tanggal_pengajuan)}
                                        </span>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3 p-3 rounded-lg bg-neutral-50 border border-neutral-100">
                                    <FileSpreadsheet className="w-4 h-4 text-neutral-500 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="text-[10px] text-neutral-400 uppercase tracking-wider block">
                                            Dikonsolidasi Oleh (Staf Keuangan)
                                        </span>
                                        <span className="font-bold text-neutral-900">
                                            {spj.dikonsolidasi_oleh?.name || (spj.tanggal_konsolidasi ? 'Staf Keuangan' : '-')}
                                        </span>
                                        <span className="text-[11px] text-neutral-500 block">
                                            Tanggal: {formatDate(spj.tanggal_konsolidasi)}
                                        </span>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3 p-3 rounded-lg bg-neutral-50 border border-neutral-100">
                                    <CheckCircle2 className="w-4 h-4 text-neutral-500 shrink-0 mt-0.5" />
                                    <div>
                                        <span className="text-[10px] text-neutral-400 uppercase tracking-wider block">
                                            Diverifikasi Oleh (Sekmat)
                                        </span>
                                        <span className="font-bold text-neutral-900">
                                            {spj.diverifikasi_oleh?.name || (spj.tanggal_verifikasi ? 'Sekretaris Kecamatan' : '-')}
                                        </span>
                                        <span className="text-[11px] text-neutral-500 block">
                                            Tanggal: {formatDate(spj.tanggal_verifikasi)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Dokumen Bukti Fisik Pertanggungjawaban */}
                    <div className="pt-4 border-t border-neutral-100 space-y-3">
                        <h3 className="font-bold text-neutral-900 uppercase tracking-wider text-[11px]">
                            Dokumen Bukti Pertanggungjawaban Fisik
                        </h3>

                        {spj.file_bukti ? (
                            <div className="p-4 rounded-xl bg-neutral-50 border border-neutral-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div className="flex items-center gap-3">
                                    <div className="p-2.5 rounded-lg bg-primary/10 text-primary">
                                        <FileText className="w-6 h-6" />
                                    </div>
                                    <div>
                                        <span className="font-bold text-xs text-neutral-900 block">
                                            Berkas Scan Bukti SPJ
                                        </span>
                                        <span className="text-[11px] text-neutral-500">
                                            Tersimpan pada server lokal terproteksi
                                        </span>
                                    </div>
                                </div>

                                <a
                                    href={`/storage/${spj.file_bukti}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="px-4 py-2 bg-neutral-900 hover:bg-neutral-800 text-white rounded-btn text-xs font-semibold shadow-xs transition-colors inline-flex items-center gap-1.5"
                                >
                                    <ExternalLink className="w-3.5 h-3.5" />
                                    Buka Dokumen Bukti
                                </a>
                            </div>
                        ) : (
                            <p className="text-xs text-neutral-400 italic">
                                Belum ada berkas dokumen bukti yang dilampirkan.
                            </p>
                        )}
                    </div>

                    {/* Riwayat Jejak Audit / Log Aktivitas Transisi */}
                    <div className="pt-6 border-t border-neutral-100 space-y-3">
                        <h3 className="font-bold text-neutral-900 uppercase tracking-wider text-[11px] flex items-center gap-1.5">
                            <Clock className="w-4 h-4 text-neutral-500" />
                            Riwayat Transisi Status & Jejak Audit (Log Aktivitas)
                        </h3>

                        {logs.length === 0 ? (
                            <p className="text-xs text-neutral-400 italic">
                                Belum ada catatan riwayat aktivitas untuk berkas ini.
                            </p>
                        ) : (
                            <div className="relative pl-6 border-l-2 border-neutral-200 space-y-4 my-2">
                                {logs.map((log) => (
                                    <div key={log.id} className="relative">
                                        {/* Timeline Dot */}
                                        <div className="absolute -left-[31px] top-1 w-3 h-3 rounded-full bg-primary border-2 border-white shadow-xs" />
                                        <div className="text-xs">
                                            <div className="flex items-center gap-2">
                                                <span className="font-bold text-neutral-900">
                                                    {log.user?.name || 'Sistem Otomatis'}
                                                </span>
                                                <span className="text-[10px] text-neutral-400 font-mono">
                                                    {formatDate(log.created_at)}
                                                </span>
                                            </div>
                                            <p className="text-neutral-600 mt-0.5 leading-relaxed">
                                                {log.keterangan}
                                            </p>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Modal Dialog Tindakan */}
            <ConfirmModal
                isOpen={!!modalAction}
                isLoading={loading}
                onClose={() => setModalAction(null)}
                onConfirm={handleExecuteAction}
                title={
                    modalAction === 'konsolidasi'
                        ? 'Konfirmasi Konsolidasi SPJ'
                        : modalAction === 'ajukanVerifikasi'
                        ? 'Ajukan ke Sekmat'
                        : modalAction === 'approve'
                        ? 'Pengesahan & Persetujuan SPJ'
                        : 'Tolak Berkas SPJ'
                }
                message={
                    modalAction === 'konsolidasi'
                        ? `Sistem akan menerbitkan nomor SPJ resmi untuk pengajuan ini. Lanjutkan konsolidasi?`
                        : modalAction === 'ajukanVerifikasi'
                        ? `SPJ ${spj.nomor_spj} akan diteruskan ke antrean Sekmat. Pastikan berkas fisik telah lengkap.`
                        : modalAction === 'approve'
                        ? `Pengesahan SPJ ${spj.nomor_spj} bersifat final (BR-SPJ-06) dan akan mencatat realisasi anggaran secara permanen.`
                        : `SPJ akan dikembalikan ke Staf Keuangan untuk revisi berkas.`
                }
                confirmText={
                    modalAction === 'konsolidasi'
                        ? 'Konsolidasi Sekarang'
                        : modalAction === 'ajukanVerifikasi'
                        ? 'Kirim ke Sekmat'
                        : modalAction === 'approve'
                        ? 'Sahkan SPJ'
                        : 'Tolak SPJ'
                }
                variant={
                    modalAction === 'reject'
                        ? 'danger'
                        : modalAction === 'approve'
                        ? 'success'
                        : modalAction === 'ajukanVerifikasi'
                        ? 'warning'
                        : 'primary'
                }
            >
                {modalAction === 'reject' && (
                    <div className="mt-3 space-y-1.5 text-left">
                        <label className="block text-xs font-bold text-neutral-800">
                            Catatan Alasan Penolakan <span className="text-rose-500">* (Min. 10 karakter)</span>
                        </label>
                        <textarea
                            rows="3"
                            value={rejectCatatan}
                            onChange={(e) => setRejectCatatan(e.target.value)}
                            placeholder="Tuliskan alasan penolakan secara spesifik..."
                            className="w-full p-2.5 rounded-lg border border-neutral-300 text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                        />
                        <div className="flex justify-between items-center text-[10px] text-neutral-500">
                            <span>Sesuai BR-SPJ-05</span>
                            <span className={rejectCatatan.length < 10 ? 'text-rose-500 font-bold' : 'text-emerald-600 font-bold'}>
                                {rejectCatatan.length} / 10 karakter
                            </span>
                        </div>
                    </div>
                )}
            </ConfirmModal>

            {/* Modal Unggah Berkas Bukti Revisi (FLOW-01) */}
            {revisiModalOpen && (
                <div className="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4 backdrop-blur-sm">
                    <div className="bg-surface rounded-card max-w-md w-full p-6 shadow-2xl border border-neutral-200 space-y-4">
                        <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                            <div className="flex items-center gap-2">
                                <UploadCloud className="w-5 h-5 text-primary" />
                                <h3 className="text-sm font-bold text-neutral-900">
                                    Unggah Berkas Bukti Revisi
                                </h3>
                            </div>
                            <button
                                type="button"
                                onClick={() => setRevisiModalOpen(false)}
                                className="p-1 text-neutral-400 hover:text-neutral-700 rounded-lg"
                            >
                                <X className="w-4 h-4" />
                            </button>
                        </div>

                        <p className="text-xs text-neutral-600 leading-relaxed">
                            Silakan unggah dokumen bukti fisik pertanggungjawaban yang telah diperbaiki (format PDF, JPG, atau PNG maks 5MB).
                        </p>

                        <form onSubmit={handleRevisiSubmit} className="space-y-4">
                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-neutral-800 block">
                                    Pilih Berkas Dokumen Bukti Baru <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="file"
                                    accept=".pdf,image/png,image/jpeg,image/jpg"
                                    onChange={(e) => setRevisiFile(e.target.files[0] || null)}
                                    className="w-full text-xs text-neutral-600 file:mr-3 file:py-2 file:px-3 file:rounded-btn file:border-0 file:text-xs file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20 cursor-pointer"
                                />
                                {revisiFile && (
                                    <p className="text-[11px] text-emerald-600 font-medium">
                                        ✓ Berkas dipilih: {revisiFile.name} ({(revisiFile.size / 1024).toFixed(0)} KB)
                                    </p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-bold text-neutral-800 block">
                                    Catatan Revisi / Keterangan Tambahan
                                </label>
                                <textarea
                                    rows="2"
                                    value={revisiCatatan}
                                    onChange={(e) => setRevisiCatatan(e.target.value)}
                                    placeholder="Contoh: Bukti kuitansi cap basah dan faktur pajak telah dilengkapi..."
                                    className="w-full p-2.5 rounded-lg border border-neutral-300 text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary"
                                />
                            </div>

                            <div className="flex items-center justify-end gap-2 pt-2 border-t border-neutral-100">
                                <button
                                    type="button"
                                    onClick={() => setRevisiModalOpen(false)}
                                    className="px-3.5 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={revisiLoading || !revisiFile}
                                    className="px-4 py-2 bg-primary text-white rounded-btn text-xs font-semibold hover:bg-primary-dark shadow-sm transition-all disabled:opacity-50 inline-flex items-center gap-1.5"
                                >
                                    <UploadCloud className="w-3.5 h-3.5" />
                                    {revisiLoading ? 'Mengunggah...' : 'Kirim Berkas Revisi'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
