import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import {
    ReceiptText,
    ArrowLeft,
    CheckCircle2,
    XCircle,
    UploadCloud,
    FileText,
    Download,
    Trash2,
    Send,
    Edit3,
    Calendar,
    User,
    Clock,
    AlertCircle,
    Paperclip,
    FileCheck,
    Image as ImageIcon,
    ExternalLink,
    ShieldCheck,
    HelpCircle,
} from 'lucide-react';

export default function BelanjaDetail({
    belanja,
    checklist = {},
    canEdit = false,
    canDelete = false,
    canUpload = false,
    canAjukan = false,
    jenisDokumenOptions = [],
}) {
    const subKeg = belanja.sub_kegiatan;
    const keg = subKeg?.kegiatan_rap;
    const prog = keg?.program;
    const dokumenList = belanja.dokumen_bukti || [];
    const riwayatList = belanja.riwayat_proses || [];

    // State for delete modal
    const [deleteDokumenItem, setDeleteDokumenItem] = useState(null);
    const [confirmAjukanModal, setConfirmAjukanModal] = useState(false);
    const [deleteBelanjaModal, setDeleteBelanjaModal] = useState(false);
    const [previewImage, setPreviewImage] = useState(null);

    // Upload form
    const { data, setData, post, processing, progress, errors, reset } = useForm({
        jenis_dokumen: 'nota',
        file: null,
        keterangan: '',
    });

    const handleUploadSubmit = (e) => {
        e.preventDefault();
        post(`/belanja/${belanja.id}/dokumen`, {
            onSuccess: () => {
                reset('file', 'keterangan');
                // Clear file input DOM element if present
                const fileInput = document.getElementById('bukti_file_input');
                if (fileInput) fileInput.value = '';
            },
        });
    };

    const handleAjukan = () => {
        router.post(`/belanja/${belanja.id}/ajukan`, {}, {
            onSuccess: () => setConfirmAjukanModal(false),
        });
    };

    const handleDeleteDokumen = () => {
        if (!deleteDokumenItem) return;
        router.delete(`/dokumen-bukti/${deleteDokumenItem.id}`, {
            onSuccess: () => setDeleteDokumenItem(null),
        });
    };

    const handleDeleteBelanja = () => {
        router.delete(`/belanja/${belanja.id}`);
    };

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
            month: 'long',
            year: 'numeric',
        });
    };

    const formatDateTime = (dateStr) => {
        if (!dateStr) return '-';
        return new Date(dateStr).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AuthenticatedLayout title="Detail Belanja & Bukti Digital">
            <Head title={`Belanja: ${belanja.uraian}`} />

            {/* Breadcrumb Hierarchy */}
            <div className="flex flex-wrap items-center gap-1.5 text-xs text-neutral-500 mb-4 bg-white/70 backdrop-blur px-4 py-2.5 rounded-lg border border-neutral-200/80">
                <Link href="/belanja" className="text-indigo-600 hover:underline flex items-center gap-1">
                    <ReceiptText className="w-3.5 h-3.5" /> Belanja
                </Link>
                <span>/</span>
                <span className="text-neutral-600">{prog?.nama || 'Program'}</span>
                <span>&rarr;</span>
                <span className="text-neutral-600">{keg?.nama || 'Kegiatan'}</span>
                <span>&rarr;</span>
                <span className="font-semibold text-neutral-800">{subKeg?.nama || 'Sub Kegiatan'}</span>
            </div>

            {/* Hero Card */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-6 mb-6">
                <div className="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                    <div className="flex-1">
                        <div className="flex flex-wrap items-center gap-2 mb-2">
                            <span className="px-2.5 py-0.5 rounded text-xs font-semibold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                {belanja.jenis_belanja}
                            </span>
                            <StatusBadge status={belanja.status_dokumen} />
                            <StatusBadge status={belanja.status_verifikasi} />
                        </div>

                        <h1 className="text-xl font-bold text-neutral-900 leading-snug">
                            {belanja.uraian}
                        </h1>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-4 pt-4 border-t border-neutral-100 text-xs text-neutral-600">
                            <div className="flex items-center gap-2">
                                <Calendar className="w-4 h-4 text-neutral-400" />
                                <span>Tanggal: <strong>{formatDate(belanja.tanggal_belanja)}</strong></span>
                            </div>
                            <div className="flex items-center gap-2">
                                <User className="w-4 h-4 text-neutral-400" />
                                <span>Penerima: <strong>{belanja.penerima || '-'}</strong></span>
                            </div>
                            <div className="flex items-center gap-2 font-mono">
                                <FileText className="w-4 h-4 text-neutral-400" />
                                <span>No Bukti: <strong>{belanja.nomor_bukti_manual || '-'}</strong></span>
                            </div>
                        </div>

                        {belanja.catatan && (
                            <div className="mt-3 p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800">
                                <strong>Catatan Pengembalian / Revisi:</strong> {belanja.catatan}
                            </div>
                        )}
                    </div>

                    {/* Right side: Nominal & Actions */}
                    <div className="flex flex-col items-start lg:items-end justify-between shrink-0 bg-neutral-50/80 p-4 rounded-xl border border-neutral-200/80 min-w-[240px]">
                        <div>
                            <span className="text-[11px] font-semibold uppercase tracking-wider text-neutral-400">
                                Total Nilai Belanja
                            </span>
                            <div className="text-2xl font-extrabold font-mono text-neutral-900 mt-0.5">
                                {formatRupiah(belanja.nominal)}
                            </div>
                        </div>

                        {/* Workflow Buttons */}
                        <div className="w-full mt-4 pt-3 border-t border-neutral-200 flex flex-col gap-2">
                            {canAjukan && (
                                <button
                                    onClick={() => setConfirmAjukanModal(true)}
                                    className="w-full inline-flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2.5 rounded-lg transition-colors shadow-sm"
                                >
                                    <Send className="w-3.5 h-3.5" />
                                    Ajukan ke Sekmat
                                </button>
                            )}

                            {belanja.status_dokumen !== 'lengkap' && belanja.status_verifikasi === 'draft' && (
                                <div className="text-[11px] text-amber-700 bg-amber-50 p-2 rounded border border-amber-200 flex items-start gap-1.5">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0 mt-0.5" />
                                    <span>Unggah minimal 1 bukti belanja (Nota/Kwitansi/Faktur) untuk dapat mengajukan verifikasi.</span>
                                </div>
                            )}

                            <div className="flex items-center gap-2 w-full">
                                {canEdit && (
                                    <Link
                                        href={`/belanja/${belanja.id}/edit`}
                                        className="flex-1 inline-flex items-center justify-center gap-1 bg-white hover:bg-neutral-50 text-neutral-700 text-xs font-medium px-3 py-2 rounded-lg border border-neutral-300 transition-colors"
                                    >
                                        <Edit3 className="w-3.5 h-3.5" />
                                        Edit
                                    </Link>
                                )}
                                {canDelete && (
                                    <button
                                        onClick={() => setDeleteBelanjaModal(true)}
                                        className="inline-flex items-center justify-center p-2 rounded-lg bg-white border border-neutral-300 text-neutral-500 hover:text-rose-600 hover:bg-neutral-50 transition-colors"
                                        title="Hapus Belanja"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Checklist Kelengkapan Bukti (BR-DOK-01 & BR-DOK-02) */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-5 mb-6">
                <div className="flex items-center justify-between mb-3">
                    <h2 className="text-sm font-bold text-neutral-900 flex items-center gap-2">
                        <FileCheck className="w-4 h-4 text-indigo-600" />
                        Checklist Kelengkapan Dokumen Bukti Belanja
                    </h2>
                    <span className="text-xs text-neutral-500">
                        Standar minimal: ada minimal salah satu dari Nota, Kwitansi, atau Faktur.
                    </span>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    {Object.entries(checklist).map(([key, item]) => (
                        <div
                            key={key}
                            className={`p-3 rounded-lg border text-center transition-all ${
                                item.uploaded
                                    ? 'bg-emerald-50/70 border-emerald-200 text-emerald-900'
                                    : 'bg-neutral-50 border-neutral-200 text-neutral-500'
                            }`}
                        >
                            <div className="flex items-center justify-center mb-1">
                                {item.uploaded ? (
                                    <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                                ) : (
                                    <XCircle className="w-5 h-5 text-neutral-300" />
                                )}
                            </div>
                            <div className="font-semibold text-xs capitalize">{item.label}</div>
                            <div className="text-[10px] mt-0.5">
                                {item.uploaded ? `${item.count} berkas terunggah` : 'Belum diunggah'}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Main Grid: Upload & File Gallery */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                {/* Upload Form Box */}
                <div className="lg:col-span-1">
                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-5 sticky top-20">
                        <h2 className="text-sm font-bold text-neutral-900 flex items-center gap-2 mb-3">
                            <UploadCloud className="w-4 h-4 text-indigo-600" />
                            Unggah Dokumen Bukti
                        </h2>

                        {canUpload ? (
                            <form onSubmit={handleUploadSubmit} className="space-y-4">
                                <div>
                                    <label className="text-xs font-semibold text-neutral-700 block mb-1">
                                        Jenis Dokumen *
                                    </label>
                                    <select
                                        value={data.jenis_dokumen}
                                        onChange={(e) => setData('jenis_dokumen', e.target.value)}
                                        className="w-full text-xs rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase font-semibold"
                                        required
                                    >
                                        {jenisDokumenOptions.map((opt) => (
                                            <option key={opt} value={opt}>
                                                {opt.toUpperCase()}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.jenis_dokumen && (
                                        <p className="text-[11px] text-rose-600 mt-1">{errors.jenis_dokumen}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="text-xs font-semibold text-neutral-700 block mb-1">
                                        Pilih Berkas (PDF, JPG, PNG maks 10MB) *
                                    </label>
                                    <input
                                        id="bukti_file_input"
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        onChange={(e) => setData('file', e.target.files[0])}
                                        className="w-full text-xs text-neutral-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-neutral-300 rounded-lg p-1.5"
                                        required
                                    />
                                    {errors.file && (
                                        <p className="text-[11px] text-rose-600 mt-1">{errors.file}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="text-xs font-semibold text-neutral-700 block mb-1">
                                        Keterangan File (Opsional)
                                    </label>
                                    <input
                                        type="text"
                                        value={data.keterangan}
                                        onChange={(e) => setData('keterangan', e.target.value)}
                                        placeholder="Contoh: Nota Toko ATK 12 Mei"
                                        className="w-full text-xs rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>

                                {progress && (
                                    <div className="w-full bg-neutral-200 rounded-full h-1.5 overflow-hidden">
                                        <div
                                            className="bg-indigo-600 h-1.5 rounded-full transition-all duration-300"
                                            style={{ width: `${progress.percentage}%` }}
                                        />
                                    </div>
                                )}

                                <button
                                    type="submit"
                                    disabled={processing || !data.file}
                                    className="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-neutral-300 text-white text-xs font-semibold py-2.5 px-4 rounded-lg transition-colors shadow-sm inline-flex items-center justify-center gap-2"
                                >
                                    <UploadCloud className="w-4 h-4" />
                                    {processing ? 'Mengunggah...' : 'Unggah Dokumen'}
                                </button>
                            </form>
                        ) : (
                            <div className="p-4 bg-neutral-50 rounded-lg border border-neutral-200 text-center text-xs text-neutral-500">
                                <AlertCircle className="w-6 h-6 text-neutral-400 mx-auto mb-1.5" />
                                <p className="font-semibold text-neutral-700">Unggahan Dikunci</p>
                                <p className="text-[11px] mt-0.5">
                                    Dokumen belanja ini sedang dalam proses verifikasi atau telah disetujui, sehingga berkas bukti terkunci untuk menjaga keabsahan audit.
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Evidence Documents Gallery / List */}
                <div className="lg:col-span-2">
                    <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-5">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-sm font-bold text-neutral-900 flex items-center gap-2">
                                <Paperclip className="w-4 h-4 text-indigo-600" />
                                Berkas Dokumen Terunggah ({dokumenList.length})
                            </h2>
                            <span className="text-[11px] text-neutral-400">
                                Klik dokumen untuk mengunduh atau melihat pratinjau.
                            </span>
                        </div>

                        {dokumenList.length === 0 ? (
                            <div className="p-10 border border-dashed border-neutral-300 rounded-xl text-center text-neutral-400">
                                <FileText className="w-10 h-10 text-neutral-300 mx-auto mb-2" />
                                <p className="text-xs font-semibold text-neutral-700">Belum ada berkas dokumen diunggah</p>
                                <p className="text-[11px] text-neutral-400 mt-0.5">
                                    Gunakan form di sebelah kiri untuk mengunggah Nota, Kwitansi, atau Faktur.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {dokumenList.map((dok) => {
                                    const isPdf = dok.mime_type === 'application/pdf';
                                    const isImg = dok.mime_type?.startsWith('image/');
                                    const fileUrl = `/dokumen-bukti/${dok.id}/download`;

                                    return (
                                        <div
                                            key={dok.id}
                                            className="p-3.5 rounded-lg border border-neutral-200 hover:border-indigo-200 hover:bg-neutral-50/50 transition-all flex items-center justify-between gap-3"
                                        >
                                            <div className="flex items-center gap-3 min-w-0 flex-1">
                                                <div className={`p-2.5 rounded-lg shrink-0 ${isPdf ? 'bg-rose-50 text-rose-600' : 'bg-blue-50 text-blue-600'}`}>
                                                    {isPdf ? (
                                                        <FileText className="w-5 h-5" />
                                                    ) : (
                                                        <ImageIcon className="w-5 h-5" />
                                                    )}
                                                </div>

                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <span className="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                            {dok.jenis_dokumen}
                                                        </span>
                                                        <span className="text-xs font-semibold text-neutral-900 truncate">
                                                            {dok.nama_file_asli}
                                                        </span>
                                                    </div>

                                                    <div className="flex items-center gap-3 text-[11px] text-neutral-400 mt-1">
                                                        <span>{dok.formatted_size || `${Math.round(dok.ukuran_file / 1024)} KB`}</span>
                                                        <span>&bull;</span>
                                                        <span>Diunggah {formatDateTime(dok.created_at)}</span>
                                                        {dok.keterangan && (
                                                            <>
                                                                <span>&bull;</span>
                                                                <span className="text-neutral-600 italic">"{dok.keterangan}"</span>
                                                            </>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-2 shrink-0">
                                                <a
                                                    href={fileUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1 bg-white hover:bg-neutral-100 text-neutral-700 text-xs font-medium px-3 py-1.5 rounded-md border border-neutral-300 transition-colors shadow-sm"
                                                    title="Buka / Unduh Berkas"
                                                >
                                                    <Download className="w-3.5 h-3.5" />
                                                    Unduh
                                                </a>

                                                {canUpload && (
                                                    <button
                                                        onClick={() => setDeleteDokumenItem(dok)}
                                                        className="text-neutral-400 hover:text-rose-600 p-1.5 transition-colors"
                                                        title="Hapus Berkas Ini"
                                                    >
                                                        <Trash2 className="w-4 h-4" />
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Riwayat Proses & Audit Trail */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-5 mb-6">
                <h2 className="text-sm font-bold text-neutral-900 flex items-center gap-2 mb-4">
                    <Clock className="w-4 h-4 text-indigo-600" />
                    Riwayat Alur Proses & Verifikasi
                </h2>

                {riwayatList.length === 0 ? (
                    <p className="text-xs text-neutral-400 italic">Belum ada riwayat aktivitas tercatat.</p>
                ) : (
                    <div className="relative pl-6 space-y-4 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-neutral-200">
                        {riwayatList.map((r) => (
                            <div key={r.id} className="relative text-xs">
                                <span className="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-indigo-600 ring-4 ring-white" />
                                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                    <div className="font-semibold text-neutral-900">
                                        {r.label_aksi}
                                        <span className="text-neutral-500 font-normal ml-1">
                                            oleh <strong>{r.user?.name || 'Sistem'}</strong>
                                        </span>
                                    </div>
                                    <span className="text-[11px] text-neutral-400 font-mono">
                                        {formatDateTime(r.created_at)}
                                    </span>
                                </div>
                                {r.catatan && (
                                    <div className="mt-1 p-2 rounded bg-neutral-50 border border-neutral-200 text-neutral-700 italic">
                                        "{r.catatan}"
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>

            {/* Modal Konfirmasi Ajukan */}
            <Modal show={confirmAjukanModal} onClose={() => setConfirmAjukanModal(false)} maxWidth="sm">
                <div className="p-6">
                    <h2 className="text-sm font-bold text-neutral-900 mb-2 flex items-center gap-2 text-indigo-600">
                        <Send className="w-4 h-4" />
                        Ajukan Belanja ke Sekmat
                    </h2>
                    <p className="text-xs text-neutral-600">
                        Apakah Anda yakin seluruh berkas bukti (Nota, Kwitansi, Faktur) untuk belanja senilai{' '}
                        <strong>{formatRupiah(belanja.nominal)}</strong> sudah lengkap dan siap diverifikasi oleh Sekretaris Kecamatan?
                    </p>
                    <p className="text-[11px] text-neutral-500 mt-2">
                        Setelah diajukan, data belanja dan berkas bukti tidak dapat diubah hingga ada tindakan verifikasi.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setConfirmAjukanModal(false)}>Batal</SecondaryButton>
                        <PrimaryButton onClick={handleAjukan} className="bg-emerald-600 hover:bg-emerald-700">
                            Ya, Ajukan Sekarang
                        </PrimaryButton>
                    </div>
                </div>
            </Modal>

            {/* Modal Konfirmasi Hapus Dokumen */}
            <Modal show={!!deleteDokumenItem} onClose={() => setDeleteDokumenItem(null)} maxWidth="sm">
                <div className="p-6">
                    <h2 className="text-sm font-bold text-neutral-900 mb-2 flex items-center gap-2 text-rose-600">
                        <Trash2 className="w-4 h-4" />
                        Hapus Dokumen Bukti
                    </h2>
                    <p className="text-xs text-neutral-600">
                        Hapus berkas <strong className="text-neutral-900">"{deleteDokumenItem?.nama_file_asli}"</strong>? Berkas fisik di server akan dihapus secara permanen.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeleteDokumenItem(null)}>Batal</SecondaryButton>
                        <DangerButton onClick={handleDeleteDokumen}>Hapus Dokumen</DangerButton>
                    </div>
                </div>
            </Modal>

            {/* Modal Konfirmasi Hapus Belanja */}
            <Modal show={deleteBelanjaModal} onClose={() => setDeleteBelanjaModal(false)} maxWidth="sm">
                <div className="p-6">
                    <h2 className="text-sm font-bold text-neutral-900 mb-2 flex items-center gap-2 text-rose-600">
                        <Trash2 className="w-4 h-4" />
                        Hapus Data Belanja
                    </h2>
                    <p className="text-xs text-neutral-600">
                        Hapus uraian belanja ini beserta seluruh ({dokumenList.length}) dokumen bukti digital yang terlampir?
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeleteBelanjaModal(false)}>Batal</SecondaryButton>
                        <DangerButton onClick={handleDeleteBelanja}>Hapus Permanen</DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
