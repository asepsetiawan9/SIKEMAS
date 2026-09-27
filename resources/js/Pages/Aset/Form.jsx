import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import {
    ArrowLeft,
    Save,
    UploadCloud,
    Package,
    MapPin,
    DollarSign,
    Layers,
    FileText,
    Image as ImageIcon,
    AlertCircle,
    Info
} from 'lucide-react';

export default function Form({ isEdit = false, aset = null, users = [] }) {
    const { data, setData, post, processing, errors, transform } = useForm({
        kode_barang: aset?.kode_barang || '',
        nama: aset?.nama || '',
        merk_type: aset?.merk_type || '',
        nomor_register: aset?.nomor_register || '',
        lokasi: aset?.lokasi || '',
        penanggung_jawab: aset?.penanggung_jawab?.id || aset?.penanggung_jawab || '',
        tahun_perolehan: aset?.tahun_perolehan || new Date().getFullYear(),
        nilai: aset?.nilai || '',
        cara_perolehan: aset?.cara_perolehan || 'pembelian',
        kondisi: aset?.kondisi || 'baik',
        ukuran: aset?.ukuran || '',
        bahan: aset?.bahan || '',
        foto: null,
    });

    const [previewUrl, setPreviewUrl] = useState(aset?.foto_url || null);

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('foto', file);
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (isEdit) {
            // Gunakan method spoofing agar file upload via multipart/form-data berjalan lancar
            transform((currentData) => ({
                ...currentData,
                _method: 'put',
            }));
            post(`/aset/${aset.id}`, {
                forceFormData: true,
                preserveScroll: true,
            });
        } else {
            post('/aset', {
                forceFormData: true,
                preserveScroll: true,
            });
        }
    };

    return (
        <AuthenticatedLayout title={isEdit ? `Edit Aset: ${aset?.kode_barang}` : 'Pendaftaran Aset BMD Baru'}>
            <Head title={isEdit ? `Edit Aset - ${aset?.nama}` : 'Tambah Aset BMD Baru'} />

            <div className="max-w-4xl mx-auto space-y-6">
                {/* Back button */}
                <div className="flex items-center justify-between">
                    <Link
                        href={isEdit ? `/aset/${aset.id}` : '/aset'}
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-600 hover:text-neutral-900 transition-colors"
                    >
                        <ArrowLeft className="w-4 h-4" />
                        {isEdit ? 'Kembali ke Detail Aset' : 'Kembali ke Daftar Aset'}
                    </Link>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Section 1: Identitas Barang */}
                    <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                        <div className="flex items-center gap-2 border-b border-neutral-100 pb-3">
                            <span className="p-1.5 rounded-lg bg-primary/10 text-primary">
                                <Package className="w-4 h-4" />
                            </span>
                            <div>
                                <h3 className="text-sm font-bold text-neutral-900">
                                    Identitas & Kode Barang (BMD)
                                </h3>
                                <p className="text-[11px] text-neutral-500">
                                    Format kode barang harus mengikuti standar regulasi penomoran BMD.
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Kode Barang */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Kode Barang <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Contoh: 02.06/0012/2024"
                                    value={data.kode_barang}
                                    onChange={(e) => setData('kode_barang', e.target.value)}
                                    className={`w-full py-2 px-3 text-xs font-mono rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 ${
                                        errors.kode_barang ? 'border-rose-500 bg-rose-50/20' : ''
                                    }`}
                                />
                                <span className="text-[10px] text-neutral-400 block">
                                    Format: &#123;GOLONGAN&#125;.&#123;SUB&#125;/&#123;URUT_4DIGIT&#125;/&#123;TAHUN&#125; (BR-ASET-01)
                                </span>
                                {errors.kode_barang && (
                                    <span className="text-xs text-rose-500 block font-medium">
                                        {errors.kode_barang}
                                    </span>
                                )}
                            </div>

                            {/* Nama Barang */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Nama Barang <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Contoh: Laptop Dell Inspiron 14"
                                    value={data.nama}
                                    onChange={(e) => setData('nama', e.target.value)}
                                    className={`w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 ${
                                        errors.nama ? 'border-rose-500 bg-rose-50/20' : ''
                                    }`}
                                />
                                {errors.nama && (
                                    <span className="text-xs text-rose-500 block font-medium">
                                        {errors.nama}
                                    </span>
                                )}
                            </div>

                            {/* Merk / Tipe */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Merk / Tipe / Spesifikasi
                                </label>
                                <input
                                    type="text"
                                    placeholder="Contoh: Dell Core i7 RAM 16GB"
                                    value={data.merk_type}
                                    onChange={(e) => setData('merk_type', e.target.value)}
                                    className="w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20"
                                />
                                {errors.merk_type && (
                                    <span className="text-xs text-rose-500 block font-medium">
                                        {errors.merk_type}
                                    </span>
                                )}
                            </div>

                            {/* Nomor Register */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Nomor Register / Seri Pabrik
                                </label>
                                <input
                                    type="text"
                                    placeholder="Contoh: REG-2024-001 / SN: 8971203"
                                    value={data.nomor_register}
                                    onChange={(e) => setData('nomor_register', e.target.value)}
                                    className="w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20"
                                />
                                {errors.nomor_register && (
                                    <span className="text-xs text-rose-500 block font-medium">
                                        {errors.nomor_register}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Section 2: Penempatan & Penanggung Jawab */}
                    <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                        <div className="flex items-center gap-2 border-b border-neutral-100 pb-3">
                            <span className="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                                <MapPin className="w-4 h-4" />
                            </span>
                            <div>
                                <h3 className="text-sm font-bold text-neutral-900">
                                    Lokasi & Penanggung Jawab
                                </h3>
                                <p className="text-[11px] text-neutral-500">
                                    Ruangan penempatan barang inventaris dan pegawai yang bertanggung jawab.
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Lokasi */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Lokasi Ruangan <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    placeholder="Contoh: Ruang Pelayanan Umum / Ruang Sekretariat"
                                    value={data.lokasi}
                                    onChange={(e) => setData('lokasi', e.target.value)}
                                    className={`w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 ${
                                        errors.lokasi ? 'border-rose-500 bg-rose-50/20' : ''
                                    }`}
                                />
                                {errors.lokasi && (
                                    <span className="text-xs text-rose-500 block font-medium">
                                        {errors.lokasi}
                                    </span>
                                )}
                            </div>

                            {/* Penanggung Jawab */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Penanggung Jawab Aset <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={data.penanggung_jawab}
                                    onChange={(e) => setData('penanggung_jawab', e.target.value)}
                                    className={`w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 ${
                                        errors.penanggung_jawab ? 'border-rose-500 bg-rose-50/20' : ''
                                    }`}
                                >
                                    <option value="">-- Pilih Pegawai Penanggung Jawab --</option>
                                    {users.map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name} {u.nip ? `(NIP: ${u.nip})` : ''} - {u.jabatan || 'Pegawai'}
                                        </option>
                                    ))}
                                </select>
                                {errors.penanggung_jawab && (
                                    <span className="text-xs text-rose-500 block font-medium">
                                        {errors.penanggung_jawab}
                                    </span>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Section 3: Nilai & Perolehan */}
                    <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                        <div className="flex items-center gap-2 border-b border-neutral-100 pb-3">
                            <span className="p-1.5 rounded-lg bg-amber-50 text-amber-600">
                                <DollarSign className="w-4 h-4" />
                            </span>
                            <div>
                                <h3 className="text-sm font-bold text-neutral-900">
                                    Nilai Perolehan & Kondisi Fisik
                                </h3>
                                <p className="text-[11px] text-neutral-500">
                                    Rincian anggaran perolehan dan status fisik barang saat inventarisasi.
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                            {/* Tahun Perolehan */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Tahun Perolehan <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="1980"
                                    max={new Date().getFullYear() + 1}
                                    value={data.tahun_perolehan}
                                    onChange={(e) => setData('tahun_perolehan', e.target.value)}
                                    className={`w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 ${
                                        errors.tahun_perolehan ? 'border-rose-500 bg-rose-50/20' : ''
                                    }`}
                                />
                                {errors.tahun_perolehan && (
                                    <span className="text-xs text-rose-500 block font-medium">
                                        {errors.tahun_perolehan}
                                    </span>
                                )}
                            </div>

                            {/* Nilai Perolehan */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Nilai Aset (Rp) <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="0"
                                    step="1000"
                                    placeholder="Contoh: 15000000"
                                    value={data.nilai}
                                    onChange={(e) => setData('nilai', e.target.value)}
                                    className={`w-full py-2 px-3 text-xs font-mono rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 ${
                                        errors.nilai ? 'border-rose-500 bg-rose-50/20' : ''
                                    }`}
                                />
                                {errors.nilai && (
                                    <span className="text-xs text-rose-500 block font-medium">
                                        {errors.nilai}
                                    </span>
                                )}
                            </div>

                            {/* Cara Perolehan */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Cara Perolehan <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={data.cara_perolehan}
                                    onChange={(e) => setData('cara_perolehan', e.target.value)}
                                    className="w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 capitalize"
                                >
                                    <option value="pembelian">Pembelian / Pengadaan</option>
                                    <option value="hibah">Hibah</option>
                                    <option value="sumbangan">Sumbangan</option>
                                    <option value="produksi_sendiri">Produksi Sendiri</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>

                            {/* Kondisi */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Kondisi Fisik <span className="text-rose-500">*</span>
                                </label>
                                <select
                                    value={data.kondisi}
                                    onChange={(e) => setData('kondisi', e.target.value)}
                                    className={`w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20 font-semibold ${
                                        data.kondisi === 'baik'
                                            ? 'text-emerald-700'
                                            : data.kondisi === 'rusak_ringan'
                                            ? 'text-amber-700'
                                            : 'text-rose-700'
                                    }`}
                                >
                                    <option value="baik">Baik</option>
                                    <option value="rusak_ringan">Rusak Ringan</option>
                                    <option value="rusak_berat">Rusak Berat (Kandidat Hapus)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {/* Section 4: Spesifikasi & Foto */}
                    <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                        <div className="flex items-center gap-2 border-b border-neutral-100 pb-3">
                            <span className="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                                <Layers className="w-4 h-4" />
                            </span>
                            <div>
                                <h3 className="text-sm font-bold text-neutral-900">
                                    Spesifikasi Fisik & Foto Barang
                                </h3>
                                <p className="text-[11px] text-neutral-500">
                                    Ukuran, bahan material, dan dokumentasi visual aset fisik.
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {/* Ukuran */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Ukuran / Dimensi / Kapasitas
                                </label>
                                <input
                                    type="text"
                                    placeholder="Contoh: 14 Inci / 120 x 60 x 75 cm / 150 cc"
                                    value={data.ukuran}
                                    onChange={(e) => setData('ukuran', e.target.value)}
                                    className="w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20"
                                />
                            </div>

                            {/* Bahan */}
                            <div className="space-y-1">
                                <label className="text-xs font-semibold text-neutral-700 block">
                                    Bahan Material
                                </label>
                                <input
                                    type="text"
                                    placeholder="Contoh: Kayu Jati / Logam / Plastik / Aluminium"
                                    value={data.bahan}
                                    onChange={(e) => setData('bahan', e.target.value)}
                                    className="w-full py-2 px-3 text-xs rounded-input border-neutral-300 focus:border-primary focus:ring-primary/20"
                                />
                            </div>
                        </div>

                        {/* Foto Aset Upload */}
                        <div className="pt-2">
                            <label className="text-xs font-semibold text-neutral-700 block mb-2">
                                Foto Fisik Aset (Maks. 2MB, JPG/PNG)
                            </label>

                            <div className="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-xl border-2 border-dashed border-neutral-200 bg-neutral-50/50 hover:bg-neutral-50 transition-colors">
                                {previewUrl ? (
                                    <div className="relative shrink-0">
                                        <img
                                            src={previewUrl}
                                            alt="Preview Foto Aset"
                                            className="w-28 h-28 object-cover rounded-lg border border-neutral-200 shadow-sm"
                                        />
                                        <span className="absolute -top-2 -right-2 bg-emerald-500 text-white rounded-full p-1 text-[10px]">
                                            ✓
                                        </span>
                                    </div>
                                ) : (
                                    <div className="w-28 h-28 rounded-lg border border-neutral-200 bg-white flex flex-col items-center justify-center text-neutral-400 shrink-0">
                                        <ImageIcon className="w-8 h-8 opacity-40" />
                                        <span className="text-[10px] mt-1">Belum ada foto</span>
                                    </div>
                                )}

                                <div className="space-y-2 text-center sm:text-left flex-1">
                                    <label className="inline-flex items-center gap-2 px-4 py-2 bg-white text-neutral-700 border border-neutral-200 rounded-btn text-xs font-semibold cursor-pointer hover:bg-neutral-50 shadow-sm transition-colors">
                                        <UploadCloud className="w-4 h-4 text-primary" />
                                        <span>{previewUrl ? 'Ganti Foto Aset' : 'Pilih Berkas Foto'}</span>
                                        <input
                                            type="file"
                                            accept="image/png, image/jpeg, image/jpg"
                                            onChange={handleFileChange}
                                            className="hidden"
                                        />
                                    </label>
                                    <p className="text-[11px] text-neutral-500">
                                        Unggah foto fisik aset untuk mempermudah identifikasi dan verifikasi lapangan saat scan QR Code.
                                    </p>
                                    {errors.foto && (
                                        <span className="text-xs text-rose-500 block font-medium">
                                            {errors.foto}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Actions bar */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link
                            href={isEdit ? `/aset/${aset.id}` : '/aset'}
                            className="px-4 py-2.5 rounded-btn text-xs font-semibold text-neutral-600 hover:text-neutral-900 border border-neutral-200 bg-white shadow-sm"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-btn text-xs font-semibold hover:bg-primary-dark shadow transition-all duration-150 active:scale-[0.98] disabled:opacity-60"
                        >
                            <Save className="w-4 h-4" />
                            <span>{processing ? 'Menyimpan Data...' : isEdit ? 'Simpan Perubahan Aset' : 'Daftarkan & Generate QR/KIB'}</span>
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
