import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import DangerButton from '@/Components/DangerButton';
import SecondaryButton from '@/Components/SecondaryButton';
import Modal from '@/Components/Modal';
import {
    ReceiptText,
    Plus,
    Search,
    Filter,
    FileCheck2,
    Calendar,
    ArrowUpRight,
    Edit3,
    Trash2,
    RotateCcw,
    Eye,
    Paperclip,
    AlertCircle,
} from 'lucide-react';

export default function BelanjaIndex({
    belanjaList = { data: [], links: [] },
    filters = {},
    programTree = [],
    jenisBelanjaOptions = [],
    statusDokumenOptions = [],
    statusVerifikasiOptions = [],
}) {
    const [search, setSearch] = useState(filters.search || '');
    const [selectedProgram, setSelectedProgram] = useState(filters.program_id || '');
    const [selectedKegiatan, setSelectedKegiatan] = useState(filters.kegiatan_rap_id || '');
    const [selectedSubKegiatan, setSelectedSubKegiatan] = useState(filters.sub_kegiatan_id || '');
    const [selectedJenis, setSelectedJenis] = useState(filters.jenis_belanja || '');
    const [selectedStatusDok, setSelectedStatusDok] = useState(filters.status_dokumen || '');
    const [selectedStatusVerif, setSelectedStatusVerif] = useState(filters.status_verifikasi || '');
    const [showFilters, setShowFilters] = useState(false);
    const [deleteModalItem, setDeleteModalItem] = useState(null);

    // Filter available kegiatans based on selectedProgram
    const availableKegiatans = selectedProgram
        ? programTree.find((p) => String(p.id) === String(selectedProgram))?.kegiatan || []
        : [];

    // Filter available sub kegiatans based on selectedKegiatan
    const availableSubKegiatans = selectedKegiatan
        ? availableKegiatans.find((k) => String(k.id) === String(selectedKegiatan))?.sub_kegiatan || []
        : [];

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        applyFilters();
    };

    const applyFilters = () => {
        router.get(
            '/belanja',
            {
                search: search || undefined,
                program_id: selectedProgram || undefined,
                kegiatan_rap_id: selectedKegiatan || undefined,
                sub_kegiatan_id: selectedSubKegiatan || undefined,
                jenis_belanja: selectedJenis || undefined,
                status_dokumen: selectedStatusDok || undefined,
                status_verifikasi: selectedStatusVerif || undefined,
            },
            { preserveState: true, replace: true }
        );
    };

    const resetFilters = () => {
        setSearch('');
        setSelectedProgram('');
        setSelectedKegiatan('');
        setSelectedSubKegiatan('');
        setSelectedJenis('');
        setSelectedStatusDok('');
        setSelectedStatusVerif('');
        router.get('/belanja', {}, { preserveState: true, replace: true });
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
            month: 'short',
            year: 'numeric',
        });
    };

    const handleDelete = () => {
        if (!deleteModalItem) return;
        router.delete(`/belanja/${deleteModalItem.id}`, {
            onSuccess: () => setDeleteModalItem(null),
        });
    };

    return (
        <AuthenticatedLayout title="Daftar Belanja & Bukti SPJ">
            <Head title="Daftar Belanja & Bukti SPJ" />

            {/* Header & Primary CTA */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-5 mb-6">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2.5 text-neutral-900 font-bold text-lg">
                            <ReceiptText className="w-6 h-6 text-indigo-600" />
                            <span>Arsip Belanja & Dokumen Bukti SPJ</span>
                        </div>
                        <p className="text-xs text-neutral-500 mt-1">
                            Semua uraian belanja kegiatan dengan kelengkapan bukti (Nota, Kwitansi, Faktur, dll) serta alur verifikasi.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setShowFilters(!showFilters)}
                            className={`inline-flex items-center gap-1.5 text-xs font-medium px-3.5 py-2 rounded-lg border transition-colors ${
                                showFilters
                                    ? 'bg-neutral-800 text-white border-neutral-800'
                                    : 'bg-white text-neutral-700 border-neutral-300 hover:bg-neutral-50'
                            }`}
                        >
                            <Filter className="w-3.5 h-3.5" />
                            Filter & Cari
                        </button>

                        <Link
                            href="/belanja/create"
                            className="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm"
                        >
                            <Plus className="w-4 h-4" />
                            Catat Belanja Baru
                        </Link>
                    </div>
                </div>

                {/* Filter Drawer / Bar */}
                {showFilters && (
                    <div className="mt-5 pt-5 border-t border-neutral-100">
                        <form onSubmit={handleSearchSubmit} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            <div>
                                <label className="text-[11px] font-semibold text-neutral-600 block mb-1">
                                    Cari Uraian / Penerima / No. Bukti
                                </label>
                                <div className="relative">
                                    <Search className="w-3.5 h-3.5 text-neutral-400 absolute left-2.5 top-2.5" />
                                    <input
                                        type="text"
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Ketik kata kunci..."
                                        className="w-full text-xs pl-8 pr-3 py-1.5 rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="text-[11px] font-semibold text-neutral-600 block mb-1">
                                    Program Induk
                                </label>
                                <select
                                    value={selectedProgram}
                                    onChange={(e) => {
                                        setSelectedProgram(e.target.value);
                                        setSelectedKegiatan('');
                                        setSelectedSubKegiatan('');
                                    }}
                                    className="w-full text-xs py-1.5 rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Program</option>
                                    {programTree.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="text-[11px] font-semibold text-neutral-600 block mb-1">
                                    Kegiatan
                                </label>
                                <select
                                    value={selectedKegiatan}
                                    disabled={!selectedProgram}
                                    onChange={(e) => {
                                        setSelectedKegiatan(e.target.value);
                                        setSelectedSubKegiatan('');
                                    }}
                                    className="w-full text-xs py-1.5 rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-neutral-100"
                                >
                                    <option value="">Semua Kegiatan</option>
                                    {availableKegiatans.map((k) => (
                                        <option key={k.id} value={k.id}>
                                            {k.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="text-[11px] font-semibold text-neutral-600 block mb-1">
                                    Sub Kegiatan
                                </label>
                                <select
                                    value={selectedSubKegiatan}
                                    disabled={!selectedKegiatan}
                                    onChange={(e) => setSelectedSubKegiatan(e.target.value)}
                                    className="w-full text-xs py-1.5 rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-neutral-100"
                                >
                                    <option value="">Semua Sub Kegiatan</option>
                                    {availableSubKegiatans.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.nama}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="text-[11px] font-semibold text-neutral-600 block mb-1">
                                    Jenis Belanja
                                </label>
                                <select
                                    value={selectedJenis}
                                    onChange={(e) => setSelectedJenis(e.target.value)}
                                    className="w-full text-xs py-1.5 rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Jenis (Cetak, Mamin, Perdin...)</option>
                                    {jenisBelanjaOptions.map((j) => (
                                        <option key={j} value={j}>
                                            {j.toUpperCase()}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="text-[11px] font-semibold text-neutral-600 block mb-1">
                                    Kelengkapan Bukti
                                </label>
                                <select
                                    value={selectedStatusDok}
                                    onChange={(e) => setSelectedStatusDok(e.target.value)}
                                    className="w-full text-xs py-1.5 rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Status Dokumen</option>
                                    {statusDokumenOptions.map((sd) => (
                                        <option key={sd} value={sd}>
                                            {sd === 'lengkap' ? 'Lengkap (Ada Nota/Kwitansi/Faktur)' : 'Belum Lengkap'}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="text-[11px] font-semibold text-neutral-600 block mb-1">
                                    Status Verifikasi
                                </label>
                                <select
                                    value={selectedStatusVerif}
                                    onChange={(e) => setSelectedStatusVerif(e.target.value)}
                                    className="w-full text-xs py-1.5 rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Semua Status Verifikasi</option>
                                    {statusVerifikasiOptions.map((sv) => (
                                        <option key={sv} value={sv}>
                                            {sv.replace('_', ' ').toUpperCase()}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div className="flex items-end gap-2">
                                <button
                                    type="submit"
                                    className="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold py-2 px-3 rounded-lg transition-colors"
                                >
                                    Terapkan Filter
                                </button>
                                <button
                                    type="button"
                                    onClick={resetFilters}
                                    className="p-2 border border-neutral-300 hover:bg-neutral-100 rounded-lg text-neutral-600"
                                    title="Reset Filter"
                                >
                                    <RotateCcw className="w-4 h-4" />
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>

            {/* Belanja Table Container */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-neutral-50/80 text-neutral-600 uppercase tracking-wider text-[10px] font-semibold border-b border-neutral-200">
                            <tr>
                                <th className="px-4 py-3">Tanggal & Nomor</th>
                                <th className="px-4 py-3">Sub Kegiatan & Uraian Belanja</th>
                                <th className="px-4 py-3">Jenis Belanja</th>
                                <th className="px-4 py-3 text-right">Nominal (Rp)</th>
                                <th className="px-4 py-3 text-center">Kelengkapan Bukti</th>
                                <th className="px-4 py-3 text-center">Status Verifikasi</th>
                                <th className="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-100">
                            {belanjaList.data.length === 0 ? (
                                <tr>
                                    <td colSpan="7" className="p-8 text-center text-neutral-400">
                                        <ReceiptText className="w-10 h-10 mx-auto text-neutral-300 mb-2" />
                                        <p className="font-semibold text-neutral-700 text-sm">Tidak ada data belanja ditemukan</p>
                                        <p className="text-xs text-neutral-400 mt-0.5">
                                            Coba sesuaikan filter atau tambahkan belanja baru.
                                        </p>
                                    </td>
                                </tr>
                            ) : (
                                belanjaList.data.map((item) => {
                                    const sub = item.sub_kegiatan;
                                    const keg = sub?.kegiatan_rap;
                                    const prog = keg?.program;
                                    const dokCount = item.dokumen_bukti_count ?? (item.dokumen_bukti ? item.dokumen_bukti.length : 0);

                                    return (
                                        <tr key={item.id} className="hover:bg-neutral-50/70 transition-colors">
                                            {/* Tanggal & No */}
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <div className="font-medium text-neutral-900">
                                                    {formatDate(item.tanggal_belanja)}
                                                </div>
                                                {item.nomor_bukti_manual ? (
                                                    <span className="text-[10px] font-mono text-neutral-500">
                                                        No: {item.nomor_bukti_manual}
                                                    </span>
                                                ) : (
                                                    <span className="text-[10px] text-neutral-400 italic">
                                                        Tanpa nomor manual
                                                    </span>
                                                )}
                                            </td>

                                            {/* Hierarki & Uraian */}
                                            <td className="px-4 py-3 max-w-xs">
                                                <div className="text-[10px] text-neutral-400 truncate mb-0.5" title={`${prog?.nama} > ${keg?.nama} > ${sub?.nama}`}>
                                                    {sub?.nama || '-'}
                                                </div>
                                                <div className="font-semibold text-neutral-900 line-clamp-2">
                                                    {item.uraian}
                                                </div>
                                                {item.penerima && (
                                                    <div className="text-[11px] text-neutral-500 mt-0.5">
                                                        Penerima: <span className="font-medium text-neutral-700">{item.penerima}</span>
                                                    </div>
                                                )}
                                            </td>

                                            {/* Jenis Belanja */}
                                            <td className="px-4 py-3 whitespace-nowrap">
                                                <span className="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider bg-neutral-100 text-neutral-700 border border-neutral-200">
                                                    {item.jenis_belanja}
                                                </span>
                                            </td>

                                            {/* Nominal */}
                                            <td className="px-4 py-3 whitespace-nowrap text-right font-mono font-bold text-neutral-900 text-sm">
                                                {formatRupiah(item.nominal)}
                                            </td>

                                            {/* Kelengkapan Bukti Dokumen */}
                                            <td className="px-4 py-3 whitespace-nowrap text-center">
                                                <div className="flex flex-col items-center gap-1">
                                                    <StatusBadge status={item.status_dokumen} />
                                                    <span className="text-[10px] text-neutral-500 inline-flex items-center gap-1">
                                                        <Paperclip className="w-3 h-3" />
                                                        {dokCount} berkas
                                                    </span>
                                                </div>
                                            </td>

                                            {/* Status Verifikasi */}
                                            <td className="px-4 py-3 whitespace-nowrap text-center">
                                                <StatusBadge status={item.status_verifikasi} />
                                            </td>

                                            {/* Actions */}
                                            <td className="px-4 py-3 whitespace-nowrap text-right">
                                                <div className="flex items-center justify-end gap-1.5">
                                                    <Link
                                                        href={`/belanja/${item.id}`}
                                                        className="inline-flex items-center gap-1 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-2.5 py-1 rounded text-xs font-medium border border-indigo-200 transition-colors"
                                                        title="Buka Dokumen & Detail"
                                                    >
                                                        <Eye className="w-3.5 h-3.5" />
                                                        Bukti
                                                    </Link>

                                                    {item.status_verifikasi === 'draft' ||
                                                    item.status_verifikasi === 'dikembalikan_sekmat' ||
                                                    item.status_verifikasi === 'dikembalikan_camat' ? (
                                                        <>
                                                            <Link
                                                                href={`/belanja/${item.id}/edit`}
                                                                className="text-neutral-400 hover:text-neutral-700 p-1"
                                                                title="Edit Belanja"
                                                            >
                                                                <Edit3 className="w-4 h-4" />
                                                            </Link>
                                                            <button
                                                                onClick={() => setDeleteModalItem(item)}
                                                                className="text-neutral-400 hover:text-rose-600 p-1"
                                                                title="Hapus Belanja"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        </>
                                                    ) : null}
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                {belanjaList.links && belanjaList.links.length > 3 && (
                    <div className="p-4 border-t border-neutral-100 flex items-center justify-between">
                        <span className="text-xs text-neutral-500">
                            Menampilkan data {belanjaList.from || 0} - {belanjaList.to || 0} dari {belanjaList.total || 0}
                        </span>
                        <div className="flex items-center gap-1">
                            {belanjaList.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    preserveState
                                    className={`px-3 py-1 text-xs rounded border transition-colors ${
                                        link.active
                                            ? 'bg-indigo-600 text-white border-indigo-600 font-semibold'
                                            : !link.url
                                            ? 'text-neutral-300 border-neutral-200 cursor-not-allowed'
                                            : 'text-neutral-600 border-neutral-300 hover:bg-neutral-50'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>

            {/* Modal Konfirmasi Hapus */}
            <Modal show={!!deleteModalItem} onClose={() => setDeleteModalItem(null)} maxWidth="sm">
                <div className="p-6">
                    <h2 className="text-sm font-bold text-neutral-900 mb-2 flex items-center gap-2 text-rose-600">
                        <Trash2 className="w-5 h-5" />
                        Hapus Belanja & Berkas Bukti
                    </h2>
                    <p className="text-xs text-neutral-600">
                        Apakah Anda yakin ingin menghapus data belanja:
                    </p>
                    <div className="p-3 my-3 bg-neutral-50 rounded border border-neutral-200 text-xs">
                        <p className="font-semibold text-neutral-900">{deleteModalItem?.uraian}</p>
                        <p className="text-neutral-500 font-mono mt-1">{formatRupiah(deleteModalItem?.nominal)}</p>
                    </div>
                    <p className="text-[11px] text-rose-600">
                        Peringatan: Seluruh file bukti digital yang diunggah pada belanja ini juga akan dihapus dari server secara permanen.
                    </p>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeleteModalItem(null)}>Batal</SecondaryButton>
                        <DangerButton onClick={handleDelete}>Hapus Sekarang</DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
