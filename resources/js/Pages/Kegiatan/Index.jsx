import React, { useState, useTransition } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatRupiah } from '@/Utils/formatRupiah';
import ConfirmModal from '@/Components/ConfirmModal';
import EmptyState from '@/Components/EmptyState';
import {
    ClipboardList,
    PlusCircle,
    Search,
    Filter,
    Edit2,
    Trash2,
    X,
    Calendar,
    DollarSign,
    CheckCircle2,
    UserCheck,
    Layers,
    FileText,
    Percent,
} from 'lucide-react';
import { toast } from 'sonner';

export default function Index({
    kegiatanList,
    kasiList = [],
    sumberDanaOptions = [],
    statusOptions = [],
    tahunOptions = [],
    filters = {},
    canManage = false,
}) {
    const items = kegiatanList?.data || [];

    // Filter states
    const [search, setSearch] = useState(filters.search || '');
    const [selectedTahun, setSelectedTahun] = useState(filters.tahun || '');
    const [selectedKasi, setSelectedKasi] = useState(filters.kasi_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [, startTransition] = useTransition();

    // Modal state for Create / Edit
    const [isFormModalOpen, setIsFormModalOpen] = useState(false);
    const [editingKegiatan, setEditingKegiatan] = useState(null);

    // Modal state for Delete
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [deletingKegiatan, setDeletingKegiatan] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Form handling with useForm
    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        nama: '',
        kode_rekening: '',
        pagu: '',
        tahun_anggaran: new Date().getFullYear(),
        sumber_dana: 'APBD',
        kasi_id: '',
        status: 'aktif',
        deskripsi: '',
        periode_mulai: '',
        periode_selesai: '',
    });

    // Handle filter application
    const applyFilters = (newParams = {}) => {
        const queryParams = {
            search: search || undefined,
            tahun: selectedTahun || undefined,
            kasi_id: selectedKasi || undefined,
            status: selectedStatus || undefined,
            ...newParams,
        };

        // Clean undefined
        Object.keys(queryParams).forEach((key) => {
            if (queryParams[key] === undefined || queryParams[key] === '') {
                delete queryParams[key];
            }
        });

        startTransition(() => {
            router.get(route('kegiatan.index'), queryParams, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        });
    };

    const handleSearchSubmit = (e) => {
        e.preventDefault();
        applyFilters();
    };

    const handleResetFilters = () => {
        setSearch('');
        setSelectedTahun('');
        setSelectedKasi('');
        setSelectedStatus('');
        router.get(route('kegiatan.index'), {}, { preserveState: false });
    };

    // Open Create Modal
    const openCreateModal = () => {
        setEditingKegiatan(null);
        reset();
        clearErrors();
        setData({
            nama: '',
            kode_rekening: '',
            pagu: '',
            tahun_anggaran: new Date().getFullYear(),
            sumber_dana: sumberDanaOptions[0]?.value || 'APBD',
            kasi_id: kasiList[0]?.id || '',
            status: 'aktif',
            deskripsi: '',
            periode_mulai: '',
            periode_selesai: '',
        });
        setIsFormModalOpen(true);
    };

    // Open Edit Modal
    const openEditModal = (kegiatan) => {
        setEditingKegiatan(kegiatan);
        clearErrors();
        setData({
            nama: kegiatan.nama || '',
            kode_rekening: kegiatan.kode_rekening || '',
            pagu: kegiatan.pagu ? String(kegiatan.pagu) : '',
            tahun_anggaran: kegiatan.tahun_anggaran || new Date().getFullYear(),
            sumber_dana: kegiatan.sumber_dana || 'APBD',
            kasi_id: kegiatan.kasi_id || '',
            status: kegiatan.status || 'aktif',
            deskripsi: kegiatan.deskripsi || '',
            periode_mulai: kegiatan.periode_mulai ? kegiatan.periode_mulai.split('T')[0] : '',
            periode_selesai: kegiatan.periode_selesai ? kegiatan.periode_selesai.split('T')[0] : '',
        });
        setIsFormModalOpen(true);
    };

    // Submit form (Create or Update)
    const handleSubmit = (e) => {
        e.preventDefault();

        if (editingKegiatan) {
            put(route('kegiatan.update', editingKegiatan.id), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsFormModalOpen(false);
                    setEditingKegiatan(null);
                    reset();
                },
                onError: (err) => {
                    toast.error('Gagal memperbarui kegiatan. Silakan periksa formulir.');
                },
            });
        } else {
            post(route('kegiatan.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    setIsFormModalOpen(false);
                    reset();
                },
                onError: (err) => {
                    toast.error('Gagal menambahkan kegiatan. Silakan periksa formulir.');
                },
            });
        }
    };

    // Open Delete Confirm Modal
    const openDeleteModal = (kegiatan) => {
        setDeletingKegiatan(kegiatan);
        setIsDeleteModalOpen(true);
    };

    const confirmDelete = () => {
        if (!deletingKegiatan) return;

        setIsDeleting(true);
        router.delete(route('kegiatan.destroy', deletingKegiatan.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsDeleteModalOpen(false);
                setDeletingKegiatan(null);
                setIsDeleting(false);
            },
            onError: (errors) => {
                setIsDeleting(false);
            },
        });
    };

    // Summary calculations
    const totalPagu = items.reduce((acc, curr) => acc + (Number(curr.pagu) || 0), 0);
    const totalRealisasi = items.reduce((acc, curr) => acc + (Number(curr.total_realisasi) || 0), 0);
    const totalSisa = items.reduce((acc, curr) => acc + (Number(curr.sisa_pagu) || 0), 0);

    return (
        <AuthenticatedLayout title="Daftar Kegiatan Anggaran">
            <Head title="Kegiatan Anggaran - SIMPEL KAN" />

            <div className="space-y-6">
                {/* Header Section */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-surface p-6 rounded-card border border-neutral-200/80 shadow-xs">
                    <div>
                        <div className="flex items-center gap-2.5">
                            <div className="p-2 rounded-lg bg-primary/10 text-primary">
                                <ClipboardList className="w-6 h-6" />
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-neutral-900 tracking-tight">
                                    Kegiatan Anggaran Kecamatan Caringin
                                </h1>
                                <p className="text-xs text-neutral-500 mt-0.5">
                                    Kelola daftar kegiatan APBD, pagu anggaran, sumber dana, dan penanggung jawab per seksi
                                </p>
                            </div>
                        </div>
                    </div>

                    {canManage && (
                        <button
                            type="button"
                            onClick={openCreateModal}
                            className="inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-btn text-xs font-semibold hover:bg-primary-dark shadow-sm transition-all duration-150 hover:shadow active:scale-98 shrink-0"
                        >
                            <PlusCircle className="w-4 h-4" />
                            Tambah Kegiatan Baru
                        </button>
                    )}
                </div>

                {/* Summary KPI Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-xs flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <Layers className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] font-medium text-neutral-500 block">Total Kegiatan</span>
                            <span className="text-lg font-bold text-neutral-900">
                                {kegiatanList?.total || items.length} <span className="text-xs font-normal text-neutral-500">Kegiatan</span>
                            </span>
                        </div>
                    </div>

                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-xs flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <DollarSign className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] font-medium text-neutral-500 block">Total Alokasi Pagu</span>
                            <span className="text-sm font-bold text-neutral-900">
                                {formatRupiah(totalPagu)}
                            </span>
                        </div>
                    </div>

                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-xs flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <CheckCircle2 className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] font-medium text-neutral-500 block">Realisasi SPJ Sah</span>
                            <span className="text-sm font-bold text-amber-600">
                                {formatRupiah(totalRealisasi)}
                            </span>
                        </div>
                    </div>

                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-xs flex items-center gap-3">
                        <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <Percent className="w-5 h-5" />
                        </div>
                        <div>
                            <span className="text-[11px] font-medium text-neutral-500 block">Sisa Anggaran Tersedia</span>
                            <span className="text-sm font-bold text-indigo-600">
                                {formatRupiah(totalSisa)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Filter and Search Bar */}
                <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-xs">
                    <form onSubmit={handleSearchSubmit} className="flex flex-col lg:flex-row gap-3">
                        <div className="relative flex-1">
                            <Search className="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400" />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama kegiatan, kode rekening, atau penanggung jawab..."
                                className="w-full pl-9 pr-4 py-2 text-xs rounded-btn border border-neutral-300 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors"
                            />
                        </div>

                        <div className="flex flex-wrap sm:flex-nowrap items-center gap-2">
                            {/* Tahun Anggaran */}
                            <select
                                value={selectedTahun}
                                onChange={(e) => {
                                    setSelectedTahun(e.target.value);
                                    applyFilters({ tahun: e.target.value });
                                }}
                                className="px-3 py-2 text-xs rounded-btn border border-neutral-300 focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-white text-neutral-700"
                            >
                                <option value="">Semua Tahun</option>
                                {tahunOptions.map((th) => (
                                    <option key={th} value={th}>
                                        Tahun {th}
                                    </option>
                                ))}
                            </select>

                            {/* Kasi / Seksi */}
                            <select
                                value={selectedKasi}
                                onChange={(e) => {
                                    setSelectedKasi(e.target.value);
                                    applyFilters({ kasi_id: e.target.value });
                                }}
                                className="px-3 py-2 text-xs rounded-btn border border-neutral-300 focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-white text-neutral-700"
                            >
                                <option value="">Semua Penanggung Jawab</option>
                                {kasiList.map((k) => (
                                    <option key={k.id} value={k.id}>
                                        {k.name} ({k.seksi_label})
                                    </option>
                                ))}
                            </select>

                            {/* Status */}
                            <select
                                value={selectedStatus}
                                onChange={(e) => {
                                    setSelectedStatus(e.target.value);
                                    applyFilters({ status: e.target.value });
                                }}
                                className="px-3 py-2 text-xs rounded-btn border border-neutral-300 focus:border-primary focus:ring-1 focus:ring-primary outline-none bg-white text-neutral-700"
                            >
                                <option value="">Semua Status</option>
                                {statusOptions.map((st) => (
                                    <option key={st.value} value={st.value}>
                                        {st.label}
                                    </option>
                                ))}
                            </select>

                            <button
                                type="submit"
                                className="px-3.5 py-2 bg-neutral-800 text-white rounded-btn text-xs font-semibold hover:bg-neutral-900 transition-colors"
                            >
                                Cari
                            </button>

                            {(search || selectedTahun || selectedKasi || selectedStatus) && (
                                <button
                                    type="button"
                                    onClick={handleResetFilters}
                                    className="px-3 py-2 text-xs font-medium text-neutral-600 bg-neutral-100 hover:bg-neutral-200 rounded-btn transition-colors"
                                >
                                    Reset
                                </button>
                            )}
                        </div>
                    </form>
                </div>

                {/* Table Data */}
                <div className="bg-surface rounded-card border border-neutral-200/80 shadow-xs overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead className="bg-neutral-50/80 border-b border-neutral-200 text-neutral-600 uppercase tracking-wider font-semibold">
                                <tr>
                                    <th className="py-3 px-4">Kode & Kegiatan</th>
                                    <th className="py-3 px-4">Penanggung Jawab</th>
                                    <th className="py-3 px-4">Sumber Dana</th>
                                    <th className="py-3 px-4 text-right">Pagu Anggaran</th>
                                    <th className="py-3 px-4 text-right">Realisasi & Sisa</th>
                                    <th className="py-3 px-4 text-center">Status</th>
                                    {canManage && <th className="py-3 px-4 text-center">Aksi</th>}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-100">
                                {items.length === 0 ? (
                                    <tr>
                                        <td colSpan={canManage ? 7 : 6} className="py-12">
                                            <EmptyState
                                                title="Tidak ada kegiatan ditemukan"
                                                description="Belum ada data kegiatan yang sesuai dengan filter atau kata kunci pencarian Anda."
                                            />
                                        </td>
                                    </tr>
                                ) : (
                                    items.map((k) => {
                                        const persentase = Number(k.persentase_realisasi) || 0;
                                        const isHigh = persentase >= 80;

                                        return (
                                            <tr key={k.id} className="hover:bg-neutral-50/70 transition-colors">
                                                <td className="py-3.5 px-4 max-w-xs">
                                                    <div className="flex items-center gap-2">
                                                        <span className="font-mono font-bold text-neutral-800 bg-neutral-100 px-2 py-0.5 rounded text-[11px]">
                                                            {k.kode_rekening}
                                                        </span>
                                                        <span className="px-1.5 py-0.5 text-[10px] rounded bg-primary/10 text-primary font-semibold">
                                                            {k.tahun_anggaran}
                                                        </span>
                                                    </div>
                                                    <div className="font-semibold text-neutral-900 mt-1">
                                                        {k.nama}
                                                    </div>
                                                    {k.deskripsi && (
                                                        <p className="text-[11px] text-neutral-500 line-clamp-1 mt-0.5">
                                                            {k.deskripsi}
                                                        </p>
                                                    )}
                                                </td>

                                                <td className="py-3.5 px-4">
                                                    <div className="font-medium text-neutral-800">
                                                        {k.kasi?.name || '-'}
                                                    </div>
                                                    <div className="text-[11px] text-neutral-500">
                                                        {k.kasi?.seksi ? `Seksi ${k.kasi.seksi}` : k.kasi?.jabatan || '-'}
                                                    </div>
                                                </td>

                                                <td className="py-3.5 px-4">
                                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/50">
                                                        {k.sumber_dana}
                                                    </span>
                                                </td>

                                                <td className="py-3.5 px-4 text-right">
                                                    <div className="font-bold text-neutral-900">
                                                        {formatRupiah(k.pagu)}
                                                    </div>
                                                </td>

                                                <td className="py-3.5 px-4 text-right">
                                                    <div className="flex flex-col items-end">
                                                        <span className="font-semibold text-emerald-600">
                                                            {formatRupiah(k.total_realisasi || 0)}
                                                        </span>
                                                        <span className="text-[11px] text-neutral-500">
                                                            Sisa: {formatRupiah(k.sisa_pagu !== undefined ? k.sisa_pagu : k.pagu)}
                                                        </span>

                                                        {/* Mini Progress Bar */}
                                                        <div className="w-28 bg-neutral-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                                            <div
                                                                className={`h-full rounded-full ${
                                                                    isHigh ? 'bg-amber-500' : 'bg-emerald-500'
                                                                }`}
                                                                style={{ width: `${Math.min(100, persentase)}%` }}
                                                            />
                                                        </div>
                                                        <span className="text-[10px] text-neutral-400 mt-0.5">
                                                            {persentase}% terealisasi
                                                        </span>
                                                    </div>
                                                </td>

                                                <td className="py-3.5 px-4 text-center">
                                                    <span
                                                        className={`inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-medium ${
                                                            k.status === 'aktif'
                                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                                                : k.status === 'selesai'
                                                                ? 'bg-blue-50 text-blue-700 border border-blue-200'
                                                                : 'bg-neutral-100 text-neutral-600 border border-neutral-200'
                                                        }`}
                                                    >
                                                        {k.status === 'aktif' ? 'Aktif' : k.status === 'selesai' ? 'Selesai' : 'Dibatalkan'}
                                                    </span>
                                                </td>

                                                {canManage && (
                                                    <td className="py-3.5 px-4 text-center">
                                                        <div className="inline-flex items-center gap-1.5">
                                                            <button
                                                                type="button"
                                                                onClick={() => openEditModal(k)}
                                                                className="p-1.5 text-neutral-500 hover:text-primary hover:bg-neutral-100 rounded-md transition-colors"
                                                                title="Edit Kegiatan"
                                                            >
                                                                <Edit2 className="w-4 h-4" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => openDeleteModal(k)}
                                                                className="p-1.5 text-neutral-500 hover:text-rose-600 hover:bg-rose-50 rounded-md transition-colors"
                                                                title="Hapus Kegiatan"
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {kegiatanList?.links && kegiatanList.links.length > 3 && (
                        <div className="p-4 border-t border-neutral-100 flex items-center justify-between">
                            <span className="text-xs text-neutral-500">
                                Menampilkan {kegiatanList.from || 0} - {kegiatanList.to || 0} dari {kegiatanList.total || 0} kegiatan
                            </span>
                            <div className="flex gap-1">
                                {kegiatanList.links.map((link, idx) => (
                                    <button
                                        key={idx}
                                        disabled={!link.url || link.active}
                                        onClick={() => {
                                            if (link.url) router.get(link.url, {}, { preserveState: true });
                                        }}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                        className={`px-3 py-1 text-xs rounded-md transition-colors ${
                                            link.active
                                                ? 'bg-primary text-white font-semibold'
                                                : link.url
                                                ? 'text-neutral-700 hover:bg-neutral-100'
                                                : 'text-neutral-300 cursor-not-allowed'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Tambah / Edit Kegiatan */}
            {isFormModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-xs animate-in fade-in duration-150">
                    <div className="bg-surface rounded-modal max-w-2xl w-full border border-neutral-200/80 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
                        {/* Modal Header */}
                        <div className="flex items-center justify-between px-6 py-4 border-b border-neutral-100 bg-neutral-50/50">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-primary/10 text-primary">
                                    <FileText className="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 className="text-base font-bold text-neutral-900">
                                        {editingKegiatan ? 'Perbarui Kegiatan Anggaran' : 'Tambah Kegiatan Anggaran Baru'}
                                    </h3>
                                    <p className="text-xs text-neutral-500">
                                        {editingKegiatan
                                            ? 'Sesuaikan rincian kegiatan, pagu, atau penanggung jawab'
                                            : 'Masukkan informasi lengkap kegiatan dan alokasi pagu anggaran'}
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                onClick={() => setIsFormModalOpen(false)}
                                className="text-neutral-400 hover:text-neutral-600 p-1 rounded-lg transition-colors"
                            >
                                <X className="w-5 h-5" />
                            </button>
                        </div>

                        {/* Modal Body Form */}
                        <form onSubmit={handleSubmit} className="flex-1 overflow-y-auto p-6 space-y-4">
                            {/* Nama Kegiatan */}
                            <div>
                                <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                    Nama Kegiatan <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="text"
                                    value={data.nama}
                                    onChange={(e) => setData('nama', e.target.value)}
                                    placeholder="Contoh: Pembinaan dan Pengawasan Pemerintahan Desa"
                                    className={`w-full px-3 py-2 text-xs rounded-btn border ${
                                        errors.nama ? 'border-rose-500 focus:ring-rose-500' : 'border-neutral-300 focus:ring-primary'
                                    } outline-none focus:ring-1 transition-colors`}
                                />
                                {errors.nama && <p className="text-[11px] text-rose-500 mt-1">{errors.nama}</p>}
                            </div>

                            {/* Kode Rekening & Pagu */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                        Kode Rekening Anggaran <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={data.kode_rekening}
                                        onChange={(e) => setData('kode_rekening', e.target.value)}
                                        placeholder="Contoh: 1.01.01.2.01.0001"
                                        className={`w-full px-3 py-2 text-xs font-mono rounded-btn border ${
                                            errors.kode_rekening ? 'border-rose-500 focus:ring-rose-500' : 'border-neutral-300 focus:ring-primary'
                                        } outline-none focus:ring-1 transition-colors`}
                                    />
                                    {errors.kode_rekening && (
                                        <p className="text-[11px] text-rose-500 mt-1">{errors.kode_rekening}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                        Pagu Anggaran (Rp) <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        value={data.pagu}
                                        onChange={(e) => setData('pagu', e.target.value)}
                                        placeholder="Contoh: 50000000"
                                        className={`w-full px-3 py-2 text-xs rounded-btn border ${
                                            errors.pagu ? 'border-rose-500 focus:ring-rose-500' : 'border-neutral-300 focus:ring-primary'
                                        } outline-none focus:ring-1 transition-colors`}
                                    />
                                    {data.pagu && Number(data.pagu) > 0 && (
                                        <span className="text-[11px] text-primary font-medium mt-1 block">
                                            Preview: {formatRupiah(Number(data.pagu))}
                                        </span>
                                    )}
                                    {errors.pagu && <p className="text-[11px] text-rose-500 mt-1">{errors.pagu}</p>}
                                </div>
                            </div>

                            {/* Tahun Anggaran & Sumber Dana */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                        Tahun Anggaran <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        value={data.tahun_anggaran}
                                        onChange={(e) => setData('tahun_anggaran', parseInt(e.target.value, 10) || '')}
                                        className={`w-full px-3 py-2 text-xs rounded-btn border ${
                                            errors.tahun_anggaran ? 'border-rose-500 focus:ring-rose-500' : 'border-neutral-300 focus:ring-primary'
                                        } outline-none focus:ring-1 transition-colors`}
                                    />
                                    {errors.tahun_anggaran && (
                                        <p className="text-[11px] text-rose-500 mt-1">{errors.tahun_anggaran}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                        Sumber Dana <span className="text-rose-500">*</span>
                                    </label>
                                    <select
                                        value={data.sumber_dana}
                                        onChange={(e) => setData('sumber_dana', e.target.value)}
                                        className={`w-full px-3 py-2 text-xs rounded-btn border bg-white ${
                                            errors.sumber_dana ? 'border-rose-500 focus:ring-rose-500' : 'border-neutral-300 focus:ring-primary'
                                        } outline-none focus:ring-1 transition-colors`}
                                    >
                                        {sumberDanaOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label} ({opt.value})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.sumber_dana && (
                                        <p className="text-[11px] text-rose-500 mt-1">{errors.sumber_dana}</p>
                                    )}
                                </div>
                            </div>

                            {/* Penanggung Jawab (Kasi) & Status */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                        Penanggung Jawab (Kasi) <span className="text-rose-500">*</span>
                                    </label>
                                    <select
                                        value={data.kasi_id}
                                        onChange={(e) => setData('kasi_id', e.target.value)}
                                        className={`w-full px-3 py-2 text-xs rounded-btn border bg-white ${
                                            errors.kasi_id ? 'border-rose-500 focus:ring-rose-500' : 'border-neutral-300 focus:ring-primary'
                                        } outline-none focus:ring-1 transition-colors`}
                                    >
                                        <option value="">-- Pilih Penanggung Jawab --</option>
                                        {kasiList.map((k) => (
                                            <option key={k.id} value={k.id}>
                                                {k.name} ({k.seksi_label})
                                            </option>
                                        ))}
                                    </select>
                                    {errors.kasi_id && (
                                        <p className="text-[11px] text-rose-500 mt-1">{errors.kasi_id}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                        Status Kegiatan <span className="text-rose-500">*</span>
                                    </label>
                                    <select
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                        className={`w-full px-3 py-2 text-xs rounded-btn border bg-white ${
                                            errors.status ? 'border-rose-500 focus:ring-rose-500' : 'border-neutral-300 focus:ring-primary'
                                        } outline-none focus:ring-1 transition-colors`}
                                    >
                                        {statusOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.status && (
                                        <p className="text-[11px] text-rose-500 mt-1">{errors.status}</p>
                                    )}
                                </div>
                            </div>

                            {/* Periode Pelaksanaan */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                        Periode Mulai (Opsional)
                                    </label>
                                    <input
                                        type="date"
                                        value={data.periode_mulai}
                                        onChange={(e) => setData('periode_mulai', e.target.value)}
                                        className="w-full px-3 py-2 text-xs rounded-btn border border-neutral-300 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors"
                                    />
                                    {errors.periode_mulai && (
                                        <p className="text-[11px] text-rose-500 mt-1">{errors.periode_mulai}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                        Periode Selesai (Opsional)
                                    </label>
                                    <input
                                        type="date"
                                        value={data.periode_selesai}
                                        onChange={(e) => setData('periode_selesai', e.target.value)}
                                        className="w-full px-3 py-2 text-xs rounded-btn border border-neutral-300 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors"
                                    />
                                    {errors.periode_selesai && (
                                        <p className="text-[11px] text-rose-500 mt-1">{errors.periode_selesai}</p>
                                    )}
                                </div>
                            </div>

                            {/* Deskripsi */}
                            <div>
                                <label className="block text-xs font-semibold text-neutral-700 mb-1">
                                    Deskripsi / Keterangan
                                </label>
                                <textarea
                                    rows="3"
                                    value={data.deskripsi}
                                    onChange={(e) => setData('deskripsi', e.target.value)}
                                    placeholder="Penjelasan ringkas tentang ruang lingkup atau rincian kegiatan..."
                                    className="w-full px-3 py-2 text-xs rounded-btn border border-neutral-300 focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-colors resize-none"
                                />
                                {errors.deskripsi && (
                                    <p className="text-[11px] text-rose-500 mt-1">{errors.deskripsi}</p>
                                )}
                            </div>

                            {/* Modal Footer Actions */}
                            <div className="pt-4 border-t border-neutral-100 flex items-center justify-end gap-3">
                                <button
                                    type="button"
                                    onClick={() => setIsFormModalOpen(false)}
                                    className="px-4 py-2 text-xs font-semibold text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-btn transition-colors"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="px-5 py-2 text-xs font-semibold text-white bg-primary hover:bg-primary-dark rounded-btn shadow-sm transition-all disabled:opacity-50"
                                >
                                    {processing ? 'Menyimpan...' : editingKegiatan ? 'Simpan Perubahan' : 'Tambah Kegiatan'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Confirm Delete Modal */}
            <ConfirmModal
                isOpen={isDeleteModalOpen}
                onClose={() => setIsDeleteModalOpen(false)}
                onConfirm={confirmDelete}
                title="Hapus Kegiatan Anggaran"
                message={`Apakah Anda yakin ingin menghapus kegiatan "${deletingKegiatan?.nama}" (${deletingKegiatan?.kode_rekening})? Tindakan ini tidak dapat dibatalkan.`}
                confirmText="Hapus Kegiatan"
                cancelText="Batal"
                variant="danger"
                isLoading={isDeleting}
            />
        </AuthenticatedLayout>
    );
}
