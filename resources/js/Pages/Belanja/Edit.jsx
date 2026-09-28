import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import {
    ReceiptText,
    ArrowLeft,
    Layers,
    FileText,
} from 'lucide-react';

export default function BelanjaEdit({ belanja, programTree = [], jenisBelanjaOptions = [] }) {
    const subKeg = belanja.sub_kegiatan;
    const keg = subKeg?.kegiatan_rap;
    const prog = keg?.program;

    const [selectedProgram, setSelectedProgram] = useState(prog?.id || '');
    const [selectedKegiatan, setSelectedKegiatan] = useState(keg?.id || '');

    const { data, setData, put, processing, errors } = useForm({
        sub_kegiatan_id: belanja.sub_kegiatan_id,
        uraian: belanja.uraian || '',
        jenis_belanja: belanja.jenis_belanja || 'cetak',
        nominal: belanja.nominal || '',
        tanggal_belanja: belanja.tanggal_belanja ? belanja.tanggal_belanja.split('T')[0] : '',
        penerima: belanja.penerima || '',
        nomor_bukti_manual: belanja.nomor_bukti_manual || '',
        keterangan: belanja.keterangan || '',
    });

    const availableKegiatans = selectedProgram
        ? programTree.find((p) => String(p.id) === String(selectedProgram))?.kegiatan || []
        : [];

    const availableSubKegiatans = selectedKegiatan
        ? availableKegiatans.find((k) => String(k.id) === String(selectedKegiatan))?.sub_kegiatan || []
        : [];

    const handleProgramChange = (e) => {
        const val = e.target.value;
        setSelectedProgram(val);
        setSelectedKegiatan('');
        setData('sub_kegiatan_id', '');
    };

    const handleKegiatanChange = (e) => {
        const val = e.target.value;
        setSelectedKegiatan(val);
        setData('sub_kegiatan_id', '');
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        put(`/belanja/${belanja.id}`);
    };

    const formatRupiah = (val) => {
        if (!val) return 'Rp 0';
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(Number(val) || 0);
    };

    return (
        <AuthenticatedLayout title="Edit Uraian Belanja">
            <Head title={`Edit Belanja #${belanja.id}`} />

            <div className="max-w-3xl mx-auto">
                {/* Header */}
                <div className="flex items-center gap-3 mb-6">
                    <Link
                        href={`/belanja/${belanja.id}`}
                        className="p-2 rounded-lg bg-white border border-neutral-200 text-neutral-600 hover:bg-neutral-50 transition-colors shadow-sm"
                    >
                        <ArrowLeft className="w-4 h-4" />
                    </Link>
                    <div>
                        <h1 className="text-lg font-bold text-neutral-900 flex items-center gap-2">
                            <ReceiptText className="w-5 h-5 text-indigo-600" />
                            Edit Uraian Belanja #{belanja.id}
                        </h1>
                        <p className="text-xs text-neutral-500">
                            Perubahan data belanja hanya dapat dilakukan selama belum disetujui Camat.
                        </p>
                    </div>
                </div>

                {/* Form Card */}
                <form
                    onSubmit={handleSubmit}
                    className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-6 space-y-6"
                >
                    {/* Section 1: Hierarki RAP */}
                    <div>
                        <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400 mb-3 flex items-center gap-1.5">
                            <Layers className="w-4 h-4 text-indigo-500" />
                            1. Penempatan Hierarki RAP
                        </h3>

                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <InputLabel htmlFor="program" value="Program Induk *" />
                                <select
                                    id="program"
                                    value={selectedProgram}
                                    onChange={handleProgramChange}
                                    className="mt-1 w-full text-xs rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">-- Pilih Program --</option>
                                    {programTree.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.kode ? `[${p.kode}] ` : ''}{p.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <InputLabel htmlFor="kegiatan" value="Kegiatan *" />
                                <select
                                    id="kegiatan"
                                    disabled={!selectedProgram}
                                    value={selectedKegiatan}
                                    onChange={handleKegiatanChange}
                                    className="mt-1 w-full text-xs rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-neutral-100"
                                    required
                                >
                                    <option value="">-- Pilih Kegiatan --</option>
                                    {availableKegiatans.map((k) => (
                                        <option key={k.id} value={k.id}>
                                            {k.kode ? `[${k.kode}] ` : ''}{k.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <InputLabel htmlFor="sub_kegiatan_id" value="Sub Kegiatan *" />
                                <select
                                    id="sub_kegiatan_id"
                                    disabled={!selectedKegiatan}
                                    value={data.sub_kegiatan_id}
                                    onChange={(e) => setData('sub_kegiatan_id', e.target.value)}
                                    className="mt-1 w-full text-xs rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-neutral-100"
                                    required
                                >
                                    <option value="">-- Pilih Sub Kegiatan --</option>
                                    {availableSubKegiatans.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.kode ? `[${s.kode}] ` : ''}{s.nama}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.sub_kegiatan_id} className="mt-1" />
                            </div>
                        </div>
                    </div>

                    <hr className="border-neutral-100" />

                    {/* Section 2: Detail Belanja */}
                    <div>
                        <h3 className="text-xs font-bold uppercase tracking-wider text-neutral-400 mb-3 flex items-center gap-1.5">
                            <FileText className="w-4 h-4 text-emerald-500" />
                            2. Detail & Uraian Belanja
                        </h3>

                        <div className="space-y-4">
                            <div>
                                <InputLabel htmlFor="uraian" value="Uraian Belanja *" />
                                <textarea
                                    id="uraian"
                                    rows="2"
                                    className="mt-1 w-full text-xs rounded-lg border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    value={data.uraian}
                                    onChange={(e) => setData('uraian', e.target.value)}
                                    required
                                />
                                <InputError message={errors.uraian} className="mt-1" />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="jenis_belanja" value="Jenis Belanja *" />
                                    <select
                                        id="jenis_belanja"
                                        value={data.jenis_belanja}
                                        onChange={(e) => setData('jenis_belanja', e.target.value)}
                                        className="mt-1 w-full text-xs rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500 uppercase font-semibold"
                                        required
                                    >
                                        {jenisBelanjaOptions.map((opt) => (
                                            <option key={opt} value={opt}>
                                                {opt.toUpperCase()}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={errors.jenis_belanja} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="nominal" value="Nominal Belanja (Rp) *" />
                                    <TextInput
                                        id="nominal"
                                        type="number"
                                        min="1"
                                        step="1"
                                        className="mt-1 w-full text-xs font-mono font-bold"
                                        value={data.nominal}
                                        onChange={(e) => setData('nominal', e.target.value)}
                                        required
                                    />
                                    <div className="text-[11px] text-emerald-700 font-semibold mt-1">
                                        Terbilang: {formatRupiah(data.nominal)}
                                    </div>
                                    <InputError message={errors.nominal} className="mt-1" />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="tanggal_belanja" value="Tanggal Transaksi / Kwitansi *" />
                                    <TextInput
                                        id="tanggal_belanja"
                                        type="date"
                                        className="mt-1 w-full text-xs"
                                        value={data.tanggal_belanja}
                                        onChange={(e) => setData('tanggal_belanja', e.target.value)}
                                        required
                                    />
                                    <InputError message={errors.tanggal_belanja} className="mt-1" />
                                </div>

                                <div>
                                    <InputLabel htmlFor="penerima" value="Penerima / Penyedia (Opsional)" />
                                    <TextInput
                                        id="penerima"
                                        type="text"
                                        className="mt-1 w-full text-xs"
                                        value={data.penerima}
                                        onChange={(e) => setData('penerima', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel htmlFor="nomor_bukti_manual" value="Nomor Bukti Manual (Opsional)" />
                                    <TextInput
                                        id="nomor_bukti_manual"
                                        type="text"
                                        className="mt-1 w-full text-xs font-mono"
                                        value={data.nomor_bukti_manual}
                                        onChange={(e) => setData('nomor_bukti_manual', e.target.value)}
                                    />
                                </div>

                                <div>
                                    <InputLabel htmlFor="keterangan" value="Catatan Tambahan (Opsional)" />
                                    <TextInput
                                        id="keterangan"
                                        type="text"
                                        className="mt-1 w-full text-xs"
                                        value={data.keterangan}
                                        onChange={(e) => setData('keterangan', e.target.value)}
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Actions */}
                    <div className="flex items-center justify-end gap-3 pt-2">
                        <Link href={`/belanja/${belanja.id}`}>
                            <SecondaryButton>Batal</SecondaryButton>
                        </Link>
                        <PrimaryButton disabled={processing} className="bg-indigo-600 hover:bg-indigo-700">
                            Simpan Perubahan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
