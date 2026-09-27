import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import KondisiBadge from '@/Components/KondisiBadge';
import QrDownloadButton from '@/Components/QrDownloadButton';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import {
    ArrowLeft,
    Package,
    MapPin,
    Calendar,
    DollarSign,
    QrCode,
    FileText,
    Clock,
    User as UserIcon,
    Edit3,
    CheckCircle2,
    AlertTriangle,
    Download,
    RefreshCw,
    Layers,
    Shield,
    History,
    Check,
    Copy,
    ZoomIn,
    X
} from 'lucide-react';
import { toast } from 'sonner';

export default function Detail({ aset, history = [], users = [] }) {
    const { auth } = usePage().props;
    const userRole = auth?.user?.role;
    const permissions = auth?.user?.permissions || auth?.permissions || [];
    const canUpdate = permissions.includes('aset.update') || ['staf_keuangan', 'staf_umum'].includes(userRole);
    const canGenerateDoc = permissions.includes('aset.generate-kibkir') || userRole === 'staf_keuangan';

    const [copied, setCopied] = useState(false);
    const [lightboxOpen, setLightboxOpen] = useState(false);
    const [quickModalOpen, setQuickModalOpen] = useState(false);

    // Quick Update Form
    const { data, setData, put, processing, reset, errors } = useForm({
        kondisi: aset?.kondisi || 'baik',
        lokasi: aset?.lokasi || '',
        penanggung_jawab: aset?.penanggung_jawab?.id || aset?.penanggung_jawab || '',
    });

    const handleCopyCode = () => {
        if (!aset?.kode_barang) return;
        navigator.clipboard.writeText(aset.kode_barang);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleQuickUpdateSubmit = (e) => {
        e.preventDefault();
        put(`/aset/${aset.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Verifikasi fisik dan lokasi aset berhasil diperbarui.');
                setQuickModalOpen(false);
            },
            onError: (errs) => {
                toast.error('Gagal memperbarui verifikasi fisik: Mohon periksa isian kolom.');
            },
        });
    };

    const isOverdue = aset?.is_overdue_verifikasi;
    const kibKirType = aset?.kib_kir?.jenis || (aset?.kode_barang?.startsWith('06.') ? 'KIR' : 'KIB');

    return (
        <AuthenticatedLayout title={`Aset: ${aset?.nama || 'Detail'}`}>
            <Head title={`Detail Aset BMD - ${aset?.nama}`} />

            <div className="space-y-6">
                {/* Navigation and Actions */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-surface p-4 rounded-card border border-neutral-200/80 shadow-sm">
                    <Link
                        href="/aset"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        Kembali ke Daftar Aset
                    </Link>

                    <div className="flex items-center gap-2">
                        {canUpdate && (
                            <button
                                type="button"
                                onClick={() => setQuickModalOpen(true)}
                                className="inline-flex items-center gap-1.5 px-3 py-2 bg-emerald-600 text-white rounded-btn text-xs font-semibold hover:bg-emerald-700 shadow-sm transition-colors"
                            >
                                <CheckCircle2 className="w-4 h-4" />
                                Verifikasi Fisik / Mutasi Cepat
                            </button>
                        )}

                        {canUpdate && (
                            <Link
                                href={`/aset/${aset.id}/edit`}
                                className="inline-flex items-center gap-1.5 px-3 py-2 bg-white text-neutral-700 border border-neutral-200 rounded-btn text-xs font-semibold hover:bg-neutral-50 shadow-sm transition-colors"
                            >
                                <Edit3 className="w-4 h-4" />
                                Edit Data Aset
                            </Link>
                        )}
                    </div>
                </div>

                {/* Overdue Alert banner if physical check >90 days */}
                {isOverdue && (
                    <div className="flex items-center gap-3 p-4 rounded-card bg-rose-50 border border-rose-200 text-rose-900 shadow-sm">
                        <AlertTriangle className="w-5 h-5 text-rose-600 shrink-0" />
                        <div className="text-xs">
                            <strong className="font-semibold block text-rose-800">
                                Verifikasi Fisik Overdue (&gt; 90 Hari):
                            </strong>
                            Aset ini belum diverifikasi fisik dalam kurun waktu lebih dari 3 bulan terakhir. Lakukan pemeriksaan kondisi fisik barang dan perbarui status melalui tombol verifikasi fisik.
                        </div>
                    </div>
                )}

                {/* Main Content Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Left Column (8 cols): Info Detail & History */}
                    <div className="lg:col-span-8 space-y-6">
                        {/* Primary Detail Card */}
                        <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-6">
                            {/* Card Header */}
                            <div className="flex flex-col sm:flex-row justify-between items-start gap-4 border-b border-neutral-100 pb-5">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <span className="font-mono text-xs font-bold px-2.5 py-1 rounded bg-neutral-100 text-neutral-800 border border-neutral-200">
                                            {aset.kode_barang}
                                        </span>
                                        <button
                                            type="button"
                                            onClick={handleCopyCode}
                                            className="p-1 text-neutral-400 hover:text-neutral-700 rounded transition-colors"
                                            title="Salin Kode Barang"
                                        >
                                            {copied ? <Check className="w-3.5 h-3.5 text-emerald-600" /> : <Copy className="w-3.5 h-3.5" />}
                                        </button>
                                        <span className="text-[11px] text-neutral-400">
                                            Kategori: {kibKirType}
                                        </span>
                                    </div>
                                    <h1 className="text-xl font-bold text-neutral-900 mt-2">
                                        {aset.nama}
                                    </h1>
                                    <p className="text-xs text-neutral-500 mt-1 flex items-center gap-1.5">
                                        <MapPin className="w-3.5 h-3.5 text-neutral-400" />
                                        <span>Lokasi: <strong>{aset.lokasi}</strong></span>
                                    </p>
                                </div>

                                <div className="text-right">
                                    <KondisiBadge kondisi={aset.kondisi} />
                                    <span className="text-[11px] text-neutral-400 block mt-1">
                                        Status Kondisi Fisik
                                    </span>
                                </div>
                            </div>

                            {/* Detailed Info Grid */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                <div className="p-3.5 rounded-lg bg-neutral-50 border border-neutral-100 space-y-1">
                                    <span className="text-neutral-500 text-[11px] flex items-center gap-1">
                                        <DollarSign className="w-3.5 h-3.5 text-primary" /> Nilai Perolehan Aset
                                    </span>
                                    <span className="text-lg font-bold text-neutral-900 font-mono block">
                                        {formatRupiah(aset.nilai)}
                                    </span>
                                </div>

                                <div className="p-3.5 rounded-lg bg-neutral-50 border border-neutral-100 space-y-1">
                                    <span className="text-neutral-500 text-[11px] flex items-center gap-1">
                                        <Calendar className="w-3.5 h-3.5 text-neutral-500" /> Tahun & Cara Perolehan
                                    </span>
                                    <span className="font-semibold text-neutral-800 text-sm block">
                                        Tahun {aset.tahun_perolehan} • <span className="capitalize">{aset.cara_perolehan || 'Pembelian'}</span>
                                    </span>
                                </div>

                                <div className="p-3.5 rounded-lg bg-neutral-50 border border-neutral-100 space-y-1">
                                    <span className="text-neutral-500 text-[11px] flex items-center gap-1">
                                        <Layers className="w-3.5 h-3.5 text-neutral-500" /> Spesifikasi & Register
                                    </span>
                                    <span className="font-semibold text-neutral-800 block">
                                        {aset.merk_type || 'Tanpa Merk'} {aset.nomor_register ? `(Reg: ${aset.nomor_register})` : ''}
                                    </span>
                                    <span className="text-[11px] text-neutral-500 block">
                                        Bahan: {aset.bahan || '-'} • Ukuran: {aset.ukuran || '-'}
                                    </span>
                                </div>

                                <div className="p-3.5 rounded-lg bg-neutral-50 border border-neutral-100 space-y-1">
                                    <span className="text-neutral-500 text-[11px] flex items-center gap-1">
                                        <UserIcon className="w-3.5 h-3.5 text-neutral-500" /> Penanggung Jawab
                                    </span>
                                    <span className="font-semibold text-neutral-800 block">
                                        {aset.penanggung_jawab?.name || '-'}
                                    </span>
                                    <span className="text-[11px] text-neutral-500 block">
                                        NIP: {aset.penanggung_jawab?.nip || '-'}
                                    </span>
                                </div>

                                <div className="p-3.5 rounded-lg bg-neutral-50 border border-neutral-100 space-y-1 sm:col-span-2">
                                    <span className="text-neutral-500 text-[11px] flex items-center gap-1">
                                        <Clock className="w-3.5 h-3.5 text-neutral-500" /> Verifikasi Fisik Terakhir (BR-ASET-05)
                                    </span>
                                    <div className="flex items-center justify-between">
                                        <span className="font-semibold text-neutral-800 text-sm">
                                            {formatDate(aset.tanggal_verifikasi_fisik)}
                                        </span>
                                        {isOverdue ? (
                                            <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700">
                                                Overdue (&gt; 90 Hari)
                                            </span>
                                        ) : (
                                            <span className="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700">
                                                Terverifikasi Aktif
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Audit Trail & History Section */}
                        <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                            <div className="flex items-center gap-2 border-b border-neutral-100 pb-3">
                                <span className="p-1.5 rounded-lg bg-primary/10 text-primary">
                                    <History className="w-4 h-4" />
                                </span>
                                <div>
                                    <h3 className="text-sm font-bold text-neutral-900">
                                        Riwayat Verifikasi & Mutasi Aset (Audit Trail)
                                    </h3>
                                    <p className="text-[11px] text-neutral-500">
                                        Catatan otomatis setiap perubahan kondisi, mutasi lokasi, atau registrasi baru.
                                    </p>
                                </div>
                            </div>

                            {history.length === 0 ? (
                                <p className="text-xs text-neutral-500 py-4 text-center">
                                    Belum ada catatan aktivitas untuk aset ini.
                                </p>
                            ) : (
                                <div className="relative pl-6 space-y-4 before:content-[''] before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-neutral-200">
                                    {history.map((log) => (
                                        <div key={log.id} className="relative">
                                            <div className="absolute -left-[23px] top-1 w-3.5 h-3.5 rounded-full border-2 border-white bg-primary shadow-sm" />
                                            <div className="bg-neutral-50 p-3 rounded-lg border border-neutral-100 text-xs space-y-1">
                                                <div className="flex items-center justify-between text-neutral-500 text-[11px]">
                                                    <span className="font-semibold text-neutral-800">
                                                        {log.user?.name || 'Sistem Otomatis'}
                                                    </span>
                                                    <span>{formatDate(log.created_at)}</span>
                                                </div>
                                                <p className="text-neutral-700 font-medium">
                                                    {log.keterangan || log.aksi}
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Right Column (4 cols): Foto, QR Code, KIB/KIR */}
                    <div className="lg:col-span-4 space-y-6">
                        {/* Foto Aset Card */}
                        <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm space-y-3">
                            <h4 className="text-xs font-bold text-neutral-900 uppercase tracking-wider">
                                Foto Fisik Aset
                            </h4>

                            {aset.foto_url ? (
                                <div className="relative group cursor-pointer overflow-hidden rounded-xl border border-neutral-200" onClick={() => setLightboxOpen(true)}>
                                    <img
                                        src={aset.foto_url}
                                        alt={aset.nama}
                                        className="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-200"
                                    />
                                    <div className="absolute inset-0 bg-neutral-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-semibold gap-1.5">
                                        <ZoomIn className="w-4 h-4" /> Perbesar Foto
                                    </div>
                                </div>
                            ) : (
                                <div className="w-full h-44 rounded-xl border-2 border-dashed border-neutral-200 bg-neutral-50 flex flex-col items-center justify-center text-neutral-400 text-xs">
                                    <Package className="w-10 h-10 opacity-30 mb-1" />
                                    <span>Belum ada foto fisik</span>
                                    {canUpdate && (
                                        <Link
                                            href={`/aset/${aset.id}/edit`}
                                            className="mt-2 text-primary font-semibold underline text-[11px]"
                                        >
                                            Unggah Foto Sekarang
                                        </Link>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* QR Code Card */}
                        <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm space-y-4 text-center">
                            <div className="flex items-center justify-center gap-1.5 text-xs font-bold text-neutral-900 uppercase tracking-wider">
                                <QrCode className="w-4 h-4 text-primary" /> Label QR Code Fisik
                            </div>

                            <div className="p-3 bg-white rounded-xl border border-neutral-200 inline-block shadow-sm">
                                {aset.qr_code_url ? (
                                    <img
                                        src={aset.qr_code_url}
                                        alt={`QR Code ${aset.kode_barang}`}
                                        className="w-40 h-40 mx-auto object-contain"
                                    />
                                ) : (
                                    <div className="w-40 h-40 flex items-center justify-center text-neutral-400 text-xs">
                                        Memproses QR...
                                    </div>
                                )}
                                <span className="block font-mono text-[10px] text-neutral-500 mt-2 font-semibold">
                                    {aset.kode_barang}
                                </span>
                            </div>

                            <p className="text-[11px] text-neutral-500">
                                Scan QR Code ini menggunakan kamera ponsel untuk mengakses verifikasi langsung di lapangan.
                            </p>

                            <QrDownloadButton
                                aset={aset}
                                label="Unduh Label QR (PNG)"
                                className="w-full"
                            />
                        </div>

                        {/* Dokumen KIB / KIR Card */}
                        <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm space-y-4">
                            <div className="flex items-center gap-2 border-b border-neutral-100 pb-3">
                                <FileText className="w-4 h-4 text-primary" />
                                <div>
                                    <h4 className="text-xs font-bold text-neutral-900 uppercase tracking-wider">
                                        Dokumen {kibKirType} Resmi
                                    </h4>
                                    <span className="text-[11px] text-neutral-500">
                                        Standar KIB/KIR Pemda
                                    </span>
                                </div>
                            </div>

                            <p className="text-[11px] text-neutral-600">
                                Dokumen Kartu Inventaris siap cetak dengan kop resmi Kecamatan Caringin dan kolom verifikasi pejabat berwenang.
                            </p>

                            <div className="space-y-2">
                                <a
                                    href={`/aset/${aset.id}/kib-kir/download`}
                                    className="w-full inline-flex items-center justify-center gap-2 px-3.5 py-2.5 bg-primary text-white rounded-btn text-xs font-semibold hover:bg-primary-dark shadow-sm transition-colors"
                                >
                                    <Download className="w-4 h-4" />
                                    <span>Unduh Dokumen {kibKirType} (PDF)</span>
                                </a>

                                {canGenerateDoc && (
                                    <Link
                                        href={`/aset/${aset.id}/kib-kir`}
                                        className="w-full inline-flex items-center justify-center gap-2 px-3 py-2 bg-neutral-100 text-neutral-700 hover:bg-neutral-200 rounded-btn text-xs font-semibold transition-colors"
                                    >
                                        <RefreshCw className="w-3.5 h-3.5" />
                                        <span>Generate Ulang Dokumen</span>
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Modal Lightbox Foto Aset */}
                {lightboxOpen && aset.foto_url && (
                    <div
                        className="fixed inset-0 z-50 bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm"
                        onClick={() => setLightboxOpen(false)}
                    >
                        <div className="relative max-w-3xl max-h-[90vh] bg-surface rounded-xl overflow-hidden p-2 shadow-2xl">
                            <button
                                type="button"
                                onClick={() => setLightboxOpen(false)}
                                className="absolute top-4 right-4 p-2 bg-black/60 text-white rounded-full hover:bg-black/80 transition-colors"
                            >
                                <X className="w-5 h-5" />
                            </button>
                            <img
                                src={aset.foto_url}
                                alt={aset.nama}
                                className="max-w-full max-h-[80vh] object-contain rounded-lg"
                            />
                            <div className="p-3 text-center text-xs text-neutral-700 font-semibold">
                                {aset.nama} ({aset.kode_barang})
                            </div>
                        </div>
                    </div>
                )}

                {/* Modal Quick Update Verifikasi Fisik & Lokasi */}
                {quickModalOpen && (
                    <div className="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4 backdrop-blur-sm">
                        <div className="bg-surface rounded-card max-w-md w-full p-6 shadow-xl border border-neutral-200 space-y-4">
                            <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                                <div className="flex items-center gap-2">
                                    <CheckCircle2 className="w-5 h-5 text-emerald-600" />
                                    <h3 className="text-sm font-bold text-neutral-900">
                                        Verifikasi Fisik & Mutasi Cepat
                                    </h3>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => setQuickModalOpen(false)}
                                    className="p-1 text-neutral-400 hover:text-neutral-700"
                                >
                                    <X className="w-4 h-4" />
                                </button>
                            </div>

                            <p className="text-xs text-neutral-600">
                                Pembaruan status ini akan secara otomatis memperbarui <strong>Tanggal Verifikasi Fisik</strong> aset ke hari ini ({formatDate(new Date())}) dan mencatat jejak audit (BR-ASET-05).
                            </p>

                            <form onSubmit={handleQuickUpdateSubmit} className="space-y-4">
                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-neutral-700 block">
                                        Kondisi Fisik Saat Ini
                                    </label>
                                    <select
                                        value={data.kondisi}
                                        onChange={(e) => setData('kondisi', e.target.value)}
                                        className={`w-full py-2 px-3 text-xs rounded-input border-neutral-300 font-semibold ${
                                            errors.kondisi ? 'border-rose-500 bg-rose-50/20' : ''
                                        }`}
                                    >
                                        <option value="baik">Baik</option>
                                        <option value="rusak_ringan">Rusak Ringan</option>
                                        <option value="rusak_berat">Rusak Berat (Kandidat Hapus)</option>
                                    </select>
                                    {errors.kondisi && (
                                        <span className="text-xs text-rose-500 block font-medium">
                                            {errors.kondisi}
                                        </span>
                                    )}
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-neutral-700 block">
                                        Lokasi Ruangan Terkini
                                    </label>
                                    <input
                                        type="text"
                                        value={data.lokasi}
                                        onChange={(e) => setData('lokasi', e.target.value)}
                                        placeholder="Contoh: Ruang Pelayanan Umum"
                                        className={`w-full py-2 px-3 text-xs rounded-input border-neutral-300 ${
                                            errors.lokasi ? 'border-rose-500 bg-rose-50/20' : ''
                                        }`}
                                    />
                                    {errors.lokasi && (
                                        <span className="text-xs text-rose-500 block font-medium">
                                            {errors.lokasi}
                                        </span>
                                    )}
                                </div>

                                <div className="space-y-1">
                                    <label className="text-xs font-semibold text-neutral-700 block">
                                        Penanggung Jawab Aset
                                    </label>
                                    <select
                                        value={data.penanggung_jawab}
                                        onChange={(e) => setData('penanggung_jawab', e.target.value)}
                                        className={`w-full py-2 px-3 text-xs rounded-input border-neutral-300 ${
                                            errors.penanggung_jawab ? 'border-rose-500 bg-rose-50/20' : ''
                                        }`}
                                    >
                                        <option value="">-- Tetap / Pilih Pegawai --</option>
                                        {users.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.name} ({u.jabatan || 'Pegawai'})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.penanggung_jawab && (
                                        <span className="text-xs text-rose-500 block font-medium">
                                            {errors.penanggung_jawab}
                                        </span>
                                    )}
                                </div>

                                <div className="flex items-center justify-end gap-2 pt-2">
                                    <button
                                        type="button"
                                        onClick={() => setQuickModalOpen(false)}
                                        className="px-3.5 py-2 text-xs font-semibold text-neutral-600 hover:text-neutral-900"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 bg-emerald-600 text-white rounded-btn text-xs font-semibold hover:bg-emerald-700 shadow-sm disabled:opacity-60"
                                    >
                                        {processing ? 'Menyimpan...' : 'Simpan Verifikasi Fisik'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
