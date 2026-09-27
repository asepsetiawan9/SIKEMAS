import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatRupiah } from '@/Utils/formatRupiah';
import {
    ArrowLeft,
    AlertCircle,
    UploadCloud,
    FileText,
    CheckCircle2,
    Calendar,
    DollarSign,
    ShieldAlert,
    X,
} from 'lucide-react';
import { toast } from 'sonner';

export default function FormPengajuan({ kegiatans = [], hasRejectedSpj = false, tahunAktif = 2026 }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        kegiatan_id: kegiatans.length === 1 ? kegiatans[0].id : '',
        nominal: '',
        periode_bulan: new Date().getMonth() + 1,
        periode_tahun: tahunAktif,
        jenis_belanja: '',
        file_bukti: null,
    });

    const [filePreview, setFilePreview] = useState(null);

    const selectedKegiatan = kegiatans.find((k) => String(k.id) === String(data.kegiatan_id));
    const sisaPagu = selectedKegiatan ? Number(selectedKegiatan.sisa_pagu) : 0;
    const nominalNumber = Number(data.nominal) || 0;
    const isOverPagu = selectedKegiatan && nominalNumber > sisaPagu;

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (file.size > 5 * 1024 * 1024) {
            toast.error('Ukuran file maksimal 5 MB.');
            return;
        }

        setData('file_bukti', file);
        setFilePreview({
            name: file.name,
            size: (file.size / 1024 / 1024).toFixed(2) + ' MB',
            type: file.type,
        });
    };

    const removeFile = () => {
        setData('file_bukti', null);
        setFilePreview(null);
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (hasRejectedSpj) {
            toast.error('Pengajuan diblokir karena masih ada SPJ ditolak yang belum direvisi.');
            return;
        }

        if (isOverPagu) {
            toast.error('Nominal pengajuan melebihi sisa pagu kegiatan.');
            return;
        }

        post('/spj', {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Pengajuan SPJ berhasil dikirim ke Bagian Keuangan.');
            },
            onError: (errs) => {
                const firstError = Object.values(errs)[0];
                toast.error(firstError || 'Gagal mengirim pengajuan SPJ.');
            },
        });
    };

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
        <AuthenticatedLayout title="Pengajuan SPJ Digital">
            <Head title="Form Pengajuan SPJ" />

            <div className="max-w-3xl mx-auto space-y-6">
                {/* Breadcrumb & Navigation */}
                <div className="flex items-center justify-between">
                    <Link
                        href="/spj"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" /> Kembali ke Daftar SPJ
                    </Link>
                    <span className="text-[11px] font-mono text-neutral-400">
                        Tahun Anggaran: {tahunAktif}
                    </span>
                </div>

                {/* Blocker Banner: BR-SPJ-10 */}
                {hasRejectedSpj && (
                    <div className="p-4 rounded-card bg-rose-50 border border-rose-200 flex items-start gap-3 shadow-xs">
                        <ShieldAlert className="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
                        <div className="text-xs text-rose-800 space-y-1">
                            <span className="font-bold block text-rose-900">
                                Pengajuan SPJ Baru Ditolak Sementara (BR-SPJ-10)
                            </span>
                            <p>
                                Anda memiliki berkas SPJ berstatus <strong>Ditolak</strong> oleh Sekmat. Sesuai aturan sistem, Anda harus menyelesaikan revisi berkas tersebut bersama Staf Keuangan sebelum dapat mengajukan SPJ baru.
                            </p>
                            <Link
                                href="/spj"
                                className="inline-block mt-1 font-semibold text-rose-700 underline hover:text-rose-900"
                            >
                                Lihat SPJ yang Ditolak &rarr;
                            </Link>
                        </div>
                    </div>
                )}

                {/* Form Card */}
                <div className="bg-surface rounded-card p-6 sm:p-8 border border-neutral-200/80 shadow-sm space-y-6">
                    <div>
                        <h2 className="text-lg font-bold text-neutral-900 tracking-tight">
                            Formulir Pengajuan SPJ Baru
                        </h2>
                        <p className="text-xs text-neutral-500 mt-1">
                            Isi detail pengajuan dan lampirkan dokumen bukti fisik (nota/kuitansi/laporan kegiatan).
                        </p>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Kegiatan Selector */}
                        <div>
                            <label className="block text-xs font-bold text-neutral-700 mb-1.5">
                                Kegiatan Anggaran <span className="text-rose-500">*</span>
                            </label>
                            <select
                                value={data.kegiatan_id}
                                disabled={hasRejectedSpj || kegiatans.length === 0}
                                onChange={(e) => setData('kegiatan_id', e.target.value)}
                                className={`w-full px-3.5 py-2.5 rounded-btn border text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all ${
                                    errors.kegiatan_id ? 'border-rose-400 bg-rose-50/20' : 'border-neutral-300'
                                }`}
                            >
                                <option value="">-- Pilih Kegiatan Milik Anda --</option>
                                {kegiatans.map((k) => (
                                    <option key={k.id} value={k.id}>
                                        {k.nama} (Sisa Pagu: {formatRupiah(k.sisa_pagu)})
                                    </option>
                                ))}
                            </select>
                            {errors.kegiatan_id && (
                                <p className="text-[11px] text-rose-600 mt-1">{errors.kegiatan_id}</p>
                            )}

                            {kegiatans.length === 0 && (
                                <p className="text-[11px] text-amber-600 mt-1">
                                    Tidak ada kegiatan aktif yang ditugaskan kepada seksi Anda.
                                </p>
                            )}
                        </div>

                        {/* Pagu Real-Time Card */}
                        {selectedKegiatan && (
                            <div className="p-4 rounded-xl bg-neutral-50/80 border border-neutral-200/90 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                                <div>
                                    <span className="text-[11px] text-neutral-500 block">Total Pagu Kegiatan:</span>
                                    <span className="font-bold text-neutral-900 mt-0.5 block text-sm">
                                        {formatRupiah(selectedKegiatan.pagu)}
                                    </span>
                                </div>
                                <div>
                                    <span className="text-[11px] text-neutral-500 block">Telah Direalisasi:</span>
                                    <span className="font-semibold text-neutral-700 mt-0.5 block text-sm">
                                        {formatRupiah(selectedKegiatan.total_realisasi)} ({selectedKegiatan.persentase_realisasi}%)
                                    </span>
                                </div>
                                <div className="border-l border-neutral-200 pl-3 sm:border-l-1">
                                    <span className="text-[11px] text-neutral-500 block">Sisa Pagu Tersedia:</span>
                                    <span className={`font-bold mt-0.5 block text-sm ${
                                        selectedKegiatan.sisa_pagu > 0 ? 'text-emerald-700' : 'text-rose-600'
                                    }`}>
                                        {formatRupiah(selectedKegiatan.sisa_pagu)}
                                    </span>
                                </div>
                            </div>
                        )}

                        {/* Nominal Pengajuan */}
                        <div>
                            <label className="block text-xs font-bold text-neutral-700 mb-1.5">
                                Nominal Pengajuan (Rp) <span className="text-rose-500">*</span>
                            </label>
                            <div className="relative">
                                <span className="absolute inset-y-0 left-0 pl-3.5 flex items-center text-xs font-bold text-neutral-400">
                                    Rp
                                </span>
                                <input
                                    type="number"
                                    min="1"
                                    step="1"
                                    disabled={hasRejectedSpj}
                                    value={data.nominal}
                                    placeholder="Contoh: 5000000"
                                    onChange={(e) => setData('nominal', e.target.value)}
                                    className={`w-full pl-10 pr-4 py-2.5 rounded-btn border text-xs font-medium text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all ${
                                        errors.nominal || isOverPagu ? 'border-rose-400 bg-rose-50/20' : 'border-neutral-300'
                                    }`}
                                />
                            </div>

                            {data.nominal && !isNaN(Number(data.nominal)) && (
                                <p className="text-[11px] text-neutral-500 mt-1">
                                    Terbaca: <span className="font-semibold text-neutral-700">{formatRupiah(data.nominal)}</span>
                                </p>
                            )}

                            {isOverPagu && (
                                <div className="flex items-center gap-1.5 text-[11px] text-rose-600 mt-1.5 font-medium">
                                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                                    <span>
                                        Nominal pengajuan melebihi sisa pagu kegiatan ({formatRupiah(sisaPagu)}) — BR-SPJ-02.
                                    </span>
                                </div>
                            )}

                            {errors.nominal && (
                                <p className="text-[11px] text-rose-600 mt-1">{errors.nominal}</p>
                            )}
                        </div>

                        {/* Periode Bulan & Tahun */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-bold text-neutral-700 mb-1.5">
                                    Periode Bulan Anggaran <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    disabled={hasRejectedSpj}
                                    value={data.periode_bulan}
                                    onChange={(e) => setData('periode_bulan', Number(e.target.value))}
                                    className="w-full px-3.5 py-2.5 rounded-btn border border-neutral-300 text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                                >
                                    {bulanList.map((b) => (
                                        <option key={b.value} value={b.value}>
                                            Bulan {b.value} - {b.label}
                                        </option>
                                    ))}
                                </select>
                                {errors.periode_bulan && (
                                    <p className="text-[11px] text-rose-600 mt-1">{errors.periode_bulan}</p>
                                )}
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-neutral-700 mb-1.5">
                                    Tahun Anggaran
                                </label>
                                <input
                                    type="number"
                                    disabled
                                    value={data.periode_tahun}
                                    className="w-full px-3.5 py-2.5 rounded-btn border border-neutral-200 bg-neutral-100 text-xs text-neutral-500 cursor-not-allowed"
                                />
                            </div>
                        </div>

                        {/* Jenis Belanja */}
                        <div>
                            <label className="block text-xs font-bold text-neutral-700 mb-1.5">
                                Jenis Belanja / Keterangan Keperluan
                            </label>
                            <input
                                type="text"
                                disabled={hasRejectedSpj}
                                value={data.jenis_belanja}
                                placeholder="Contoh: Belanja Konsumsi Rapat Koordinasi, Belanja ATK"
                                onChange={(e) => setData('jenis_belanja', e.target.value)}
                                className="w-full px-3.5 py-2.5 rounded-btn border border-neutral-300 text-xs text-neutral-900 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                            />
                            {errors.jenis_belanja && (
                                <p className="text-[11px] text-rose-600 mt-1">{errors.jenis_belanja}</p>
                            )}
                        </div>

                        {/* File Upload Zone */}
                        <div>
                            <label className="block text-xs font-bold text-neutral-700 mb-1.5">
                                Dokumen Bukti Pertanggungjawaban (PDF / JPG / PNG, Max 5MB) <span className="text-rose-500">*</span>
                            </label>

                            {!filePreview ? (
                                <label className={`border-2 border-dashed rounded-xl p-6 flex flex-col items-center justify-center cursor-pointer hover:bg-neutral-50/70 transition-colors ${
                                    errors.file_bukti ? 'border-rose-400 bg-rose-50/20' : 'border-neutral-300'
                                } ${hasRejectedSpj ? 'opacity-50 cursor-not-allowed' : ''}`}>
                                    <UploadCloud className="w-8 h-8 text-neutral-400 mb-2" />
                                    <span className="text-xs font-semibold text-neutral-700">
                                        Klik untuk unggah atau seret berkas ke sini
                                    </span>
                                    <span className="text-[11px] text-neutral-400 mt-0.5">
                                        Dokumen scan bukti nota, kuitansi, atau rekap PDF (maksimal 5MB)
                                    </span>
                                    <input
                                        type="file"
                                        disabled={hasRejectedSpj}
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        onChange={handleFileChange}
                                        className="hidden"
                                    />
                                </label>
                            ) : (
                                <div className="flex items-center justify-between p-3.5 rounded-xl bg-neutral-50 border border-neutral-200">
                                    <div className="flex items-center gap-3 min-w-0">
                                        <div className="p-2 rounded-lg bg-primary/10 text-primary shrink-0">
                                            <FileText className="w-5 h-5" />
                                        </div>
                                        <div className="min-w-0">
                                            <span className="text-xs font-semibold text-neutral-900 block truncate">
                                                {filePreview.name}
                                            </span>
                                            <span className="text-[10px] text-neutral-500">
                                                {filePreview.size} • {filePreview.type}
                                            </span>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={removeFile}
                                        className="p-1 rounded-md text-neutral-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                        title="Ganti Berkas"
                                    >
                                        <X className="w-4 h-4" />
                                    </button>
                                </div>
                            )}

                            {errors.file_bukti && (
                                <p className="text-[11px] text-rose-600 mt-1">{errors.file_bukti}</p>
                            )}
                        </div>

                        {/* Submit Button */}
                        <div className="pt-4 border-t border-neutral-100 flex items-center justify-end gap-3">
                            <Link
                                href="/spj"
                                className="px-4 py-2.5 rounded-btn text-xs font-semibold text-neutral-600 hover:bg-neutral-100 transition-colors"
                            >
                                Batal
                            </Link>

                            <button
                                type="submit"
                                disabled={processing || hasRejectedSpj || isOverPagu || !data.kegiatan_id || !data.nominal}
                                className="px-6 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-btn text-xs font-bold shadow-md hover:shadow-lg transition-all focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center gap-2"
                            >
                                {processing ? (
                                    <>
                                        <span className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                        <span>Mengirim Pengajuan...</span>
                                    </>
                                ) : (
                                    <>
                                        <CheckCircle2 className="w-4 h-4" />
                                        <span>Kirim Pengajuan SPJ</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
