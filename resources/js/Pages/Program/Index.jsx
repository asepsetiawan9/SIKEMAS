import React, { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import {
    FolderTree,
    Plus,
    ChevronDown,
    ChevronRight,
    Edit2,
    Trash2,
    Calendar,
    Layers,
    ListTree,
    Tag,
    Receipt,
} from 'lucide-react';

export default function ProgramIndex({ tree = [], filters = {}, availableYears = [] }) {
    const [selectedTahun, setSelectedTahun] = useState(filters.tahun || new Date().getFullYear());
    const [expandedPrograms, setExpandedPrograms] = useState({});
    const [expandedKegiatans, setExpandedKegiatans] = useState({});

    // Modal States
    const [modalType, setModalType] = useState(null); // 'create_program', 'edit_program', 'create_kegiatan', etc.
    const [activeItem, setActiveItem] = useState(null);
    const [deleteConfirm, setDeleteConfirm] = useState(null);

    // Form handlers
    const programForm = useForm({
        nama: '',
        kode: '',
        tahun_anggaran: selectedTahun,
        keterangan: '',
    });

    const kegiatanForm = useForm({
        program_id: '',
        nama: '',
        kode: '',
        keterangan: '',
    });

    const subKegiatanForm = useForm({
        kegiatan_rap_id: '',
        nama: '',
        kode: '',
        keterangan: '',
    });

    const toggleProgram = (id) => {
        setExpandedPrograms((prev) => ({ ...prev, [id]: !prev[id] }));
    };

    const toggleKegiatan = (id) => {
        setExpandedKegiatans((prev) => ({ ...prev, [id]: !prev[id] }));
    };

    const handleTahunChange = (year) => {
        setSelectedTahun(year);
        router.get('/program', { tahun: year }, { preserveState: true });
    };

    // Open Modals
    const openCreateProgram = () => {
        programForm.reset();
        programForm.setData('tahun_anggaran', selectedTahun);
        setModalType('create_program');
    };

    const openEditProgram = (prog) => {
        setActiveItem(prog);
        programForm.setData({
            nama: prog.nama,
            kode: prog.kode || '',
            tahun_anggaran: prog.tahun_anggaran,
            keterangan: prog.keterangan || '',
        });
        setModalType('edit_program');
    };

    const openCreateKegiatan = (prog) => {
        kegiatanForm.reset();
        kegiatanForm.setData('program_id', prog.id);
        setActiveItem(prog);
        setModalType('create_kegiatan');
    };

    const openEditKegiatan = (keg) => {
        setActiveItem(keg);
        kegiatanForm.setData({
            program_id: keg.program_id,
            nama: keg.nama,
            kode: keg.kode || '',
            keterangan: keg.keterangan || '',
        });
        setModalType('edit_kegiatan');
    };

    const openCreateSubKegiatan = (keg) => {
        subKegiatanForm.reset();
        subKegiatanForm.setData('kegiatan_rap_id', keg.id);
        setActiveItem(keg);
        setModalType('create_sub_kegiatan');
    };

    const openEditSubKegiatan = (sub) => {
        setActiveItem(sub);
        subKegiatanForm.setData({
            kegiatan_rap_id: sub.kegiatan_rap_id,
            nama: sub.nama,
            kode: sub.kode || '',
            keterangan: sub.keterangan || '',
        });
        setModalType('edit_sub_kegiatan');
    };

    const closeModal = () => {
        setModalType(null);
        setActiveItem(null);
        programForm.reset();
        kegiatanForm.reset();
        subKegiatanForm.reset();
    };

    // Submit actions
    const handleProgramSubmit = (e) => {
        e.preventDefault();
        if (modalType === 'create_program') {
            programForm.post('/program', { onSuccess: () => closeModal() });
        } else {
            programForm.put(`/program/${activeItem.id}`, { onSuccess: () => closeModal() });
        }
    };

    const handleKegiatanSubmit = (e) => {
        e.preventDefault();
        if (modalType === 'create_kegiatan') {
            kegiatanForm.post('/program/kegiatan', { onSuccess: () => closeModal() });
        } else {
            kegiatanForm.put(`/program/kegiatan/${activeItem.id}`, { onSuccess: () => closeModal() });
        }
    };

    const handleSubKegiatanSubmit = (e) => {
        e.preventDefault();
        if (modalType === 'create_sub_kegiatan') {
            subKegiatanForm.post('/program/sub-kegiatan', { onSuccess: () => closeModal() });
        } else {
            subKegiatanForm.put(`/program/sub-kegiatan/${activeItem.id}`, { onSuccess: () => closeModal() });
        }
    };

    const executeDelete = () => {
        if (!deleteConfirm) return;
        const { type, id } = deleteConfirm;
        let url = '';
        if (type === 'program') url = `/program/${id}`;
        if (type === 'kegiatan') url = `/program/kegiatan/${id}`;
        if (type === 'sub_kegiatan') url = `/program/sub-kegiatan/${id}`;

        router.delete(url, {
            onSuccess: () => setDeleteConfirm(null),
        });
    };

    const formatRupiah = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    return (
        <AuthenticatedLayout title="Hierarki Program & RAP">
            <Head title="Hierarki Program & RAP" />

            {/* Header & Filter bar */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-5 mb-6">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2 text-indigo-700 font-semibold text-lg">
                            <FolderTree className="w-6 h-6" />
                            <span>Struktur Hierarki RAP & SPJ</span>
                        </div>
                        <p className="text-xs text-neutral-500 mt-1">
                            Pola 3 tingkat: Program &rarr; Kegiatan &rarr; Sub Kegiatan. Setiap bukti belanja terhubung ke Sub Kegiatan.
                        </p>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="flex items-center gap-2 bg-neutral-50 px-3 py-1.5 rounded-lg border border-neutral-200 text-sm">
                            <Calendar className="w-4 h-4 text-neutral-500" />
                            <span className="text-neutral-600 font-medium text-xs">Tahun:</span>
                            <select
                                value={selectedTahun}
                                onChange={(e) => handleTahunChange(e.target.value)}
                                className="bg-transparent border-0 text-sm font-semibold text-neutral-800 focus:ring-0 cursor-pointer p-0"
                            >
                                {availableYears.map((yr) => (
                                    <option key={yr} value={yr}>
                                        {yr}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <button
                            onClick={openCreateProgram}
                            className="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors shadow-sm"
                        >
                            <Plus className="w-4 h-4" />
                            Tambah Program
                        </button>
                    </div>
                </div>
            </div>

            {/* Tree Container */}
            <div className="space-y-4">
                {tree.length === 0 ? (
                    <div className="bg-white rounded-xl border border-dashed border-neutral-300 p-12 text-center">
                        <FolderTree className="w-12 h-12 text-neutral-300 mx-auto mb-3" />
                        <h3 className="text-sm font-semibold text-neutral-700">Belum ada Program pada tahun {selectedTahun}</h3>
                        <p className="text-xs text-neutral-500 mt-1 mb-4">
                            Mulai dengan menambahkan Program induk untuk tahun anggaran ini.
                        </p>
                        <button
                            onClick={openCreateProgram}
                            className="inline-flex items-center gap-1.5 bg-indigo-600 text-white text-xs font-medium px-3.5 py-2 rounded-lg hover:bg-indigo-700"
                        >
                            <Plus className="w-4 h-4" />
                            Tambah Program Pertama
                        </button>
                    </div>
                ) : (
                    tree.map((program) => {
                        const isProgExpanded = expandedPrograms[program.id] !== false; // default open
                        const kegiatanList = program.kegiatan_rap || [];

                        return (
                            <div
                                key={program.id}
                                className="bg-white rounded-xl border border-neutral-200/90 shadow-sm overflow-hidden transition-all"
                            >
                                {/* Level 1: Program Header */}
                                <div className="p-4 bg-slate-50/70 border-b border-neutral-200 flex items-center justify-between gap-3">
                                    <div
                                        onClick={() => toggleProgram(program.id)}
                                        className="flex items-center gap-3 flex-1 cursor-pointer select-none"
                                    >
                                        <button className="text-neutral-500 hover:text-neutral-800 p-0.5">
                                            {isProgExpanded ? (
                                                <ChevronDown className="w-5 h-5 text-indigo-600" />
                                            ) : (
                                                <ChevronRight className="w-5 h-5 text-neutral-400" />
                                            )}
                                        </button>
                                        <div className="flex items-center gap-2">
                                            <span className="px-2 py-0.5 bg-indigo-100 text-indigo-800 text-[11px] font-mono font-bold rounded">
                                                {program.kode || 'PROG'}
                                            </span>
                                            <h3 className="font-semibold text-sm text-neutral-900">
                                                {program.nama}
                                            </h3>
                                        </div>
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <span className="hidden sm:inline-flex text-xs text-neutral-500 bg-white px-2.5 py-1 rounded border border-neutral-200">
                                            {kegiatanList.length} Kegiatan
                                        </span>
                                        <button
                                            onClick={() => openCreateKegiatan(program)}
                                            className="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-md border border-indigo-200 transition-colors"
                                            title="Tambah Kegiatan ke Program ini"
                                        >
                                            <Plus className="w-3.5 h-3.5" />
                                            Kegiatan
                                        </button>
                                        <button
                                            onClick={() => openEditProgram(program)}
                                            className="text-neutral-400 hover:text-neutral-700 p-1"
                                            title="Edit Program"
                                        >
                                            <Edit2 className="w-4 h-4" />
                                        </button>
                                        <button
                                            onClick={() => setDeleteConfirm({ type: 'program', id: program.id, name: program.nama })}
                                            className="text-neutral-400 hover:text-rose-600 p-1"
                                            title="Hapus Program"
                                        >
                                            <Trash2 className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>

                                {/* Level 2: Kegiatan List */}
                                {isProgExpanded && (
                                    <div className="divide-y divide-neutral-100 pl-4 sm:pl-8 pr-3 py-1 bg-white">
                                        {kegiatanList.length === 0 ? (
                                            <div className="py-4 text-xs text-neutral-400 italic flex items-center justify-between">
                                                <span>Belum ada kegiatan pada program ini.</span>
                                                <button
                                                    onClick={() => openCreateKegiatan(program)}
                                                    className="text-indigo-600 hover:underline inline-flex items-center gap-1"
                                                >
                                                    <Plus className="w-3.5 h-3.5" /> Tambah Kegiatan
                                                </button>
                                            </div>
                                        ) : (
                                            kegiatanList.map((kegiatan) => {
                                                const isKegExpanded = expandedKegiatans[kegiatan.id] !== false; // default open
                                                const subList = kegiatan.sub_kegiatan || [];

                                                return (
                                                    <div key={kegiatan.id} className="py-3">
                                                        <div className="flex items-center justify-between gap-3">
                                                            <div
                                                                onClick={() => toggleKegiatan(kegiatan.id)}
                                                                className="flex items-center gap-2.5 flex-1 cursor-pointer select-none"
                                                            >
                                                                <button className="text-neutral-400 hover:text-neutral-700 p-0.5">
                                                                    {isKegExpanded ? (
                                                                        <ChevronDown className="w-4 h-4 text-emerald-600" />
                                                                    ) : (
                                                                        <ChevronRight className="w-4 h-4 text-neutral-400" />
                                                                    )}
                                                                </button>
                                                                <span className="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-mono font-medium rounded border border-emerald-200">
                                                                    {kegiatan.kode || 'KEG'}
                                                                </span>
                                                                <span className="text-xs font-semibold text-neutral-800">
                                                                    {kegiatan.nama}
                                                                </span>
                                                            </div>

                                                            <div className="flex items-center gap-2">
                                                                <span className="hidden sm:inline-flex text-[11px] text-neutral-400">
                                                                    {subList.length} Sub Kegiatan
                                                                </span>
                                                                <button
                                                                    onClick={() => openCreateSubKegiatan(kegiatan)}
                                                                    className="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200 transition-colors"
                                                                    title="Tambah Sub Kegiatan"
                                                                >
                                                                    <Plus className="w-3 h-3" />
                                                                    Sub
                                                                </button>
                                                                <button
                                                                    onClick={() => openEditKegiatan(kegiatan)}
                                                                    className="text-neutral-400 hover:text-neutral-700 p-1"
                                                                >
                                                                    <Edit2 className="w-3.5 h-3.5" />
                                                                </button>
                                                                <button
                                                                    onClick={() => setDeleteConfirm({ type: 'kegiatan', id: kegiatan.id, name: kegiatan.nama })}
                                                                    className="text-neutral-400 hover:text-rose-600 p-1"
                                                                >
                                                                    <Trash2 className="w-3.5 h-3.5" />
                                                                </button>
                                                            </div>
                                                        </div>

                                                        {/* Level 3: Sub Kegiatan List */}
                                                        {isKegExpanded && (
                                                            <div className="mt-2 pl-6 sm:pl-9 space-y-1.5 border-l-2 border-emerald-200/50 ml-2">
                                                                {subList.length === 0 ? (
                                                                    <div className="py-2 text-[11px] text-neutral-400 italic">
                                                                        Belum ada sub kegiatan. Klik tombol "+ Sub" di atas untuk menambahkan.
                                                                    </div>
                                                                ) : (
                                                                    subList.map((sub) => {
                                                                        const belanjaCount = sub.belanja_count ?? (sub.belanja ? sub.belanja.length : 0);
                                                                        const totalNominal = sub.total_nominal || 0;

                                                                        return (
                                                                            <div
                                                                                key={sub.id}
                                                                                className="flex items-center justify-between p-2 rounded-lg bg-neutral-50/80 hover:bg-neutral-100/80 transition-colors text-xs"
                                                                            >
                                                                                <div className="flex items-center gap-2 flex-1 min-w-0 pr-2">
                                                                                    <span className="px-1.5 py-0.5 bg-neutral-200/80 text-neutral-700 text-[9px] font-mono rounded">
                                                                                        {sub.kode || 'SUB'}
                                                                                    </span>
                                                                                    <span className="font-medium text-neutral-800 truncate">
                                                                                        {sub.nama}
                                                                                    </span>
                                                                                </div>

                                                                                <div className="flex items-center gap-3 shrink-0">
                                                                                    {belanjaCount > 0 ? (
                                                                                        <div className="flex items-center gap-1.5 text-[11px] font-semibold text-neutral-700">
                                                                                            <Receipt className="w-3.5 h-3.5 text-neutral-500" />
                                                                                            <span>{belanjaCount} belanja</span>
                                                                                            {totalNominal > 0 && (
                                                                                                <span className="text-emerald-700 font-mono">
                                                                                                    ({formatRupiah(totalNominal)})
                                                                                                </span>
                                                                                            )}
                                                                                        </div>
                                                                                    ) : (
                                                                                        <span className="text-[11px] text-neutral-400">
                                                                                            0 belanja
                                                                                        </span>
                                                                                    )}

                                                                                    <div className="flex items-center gap-1">
                                                                                        <button
                                                                                            onClick={() => openEditSubKegiatan(sub)}
                                                                                            className="text-neutral-400 hover:text-neutral-700 p-0.5"
                                                                                        >
                                                                                            <Edit2 className="w-3 h-3" />
                                                                                        </button>
                                                                                        <button
                                                                                            onClick={() => setDeleteConfirm({ type: 'sub_kegiatan', id: sub.id, name: sub.nama })}
                                                                                            className="text-neutral-400 hover:text-rose-600 p-0.5"
                                                                                        >
                                                                                            <Trash2 className="w-3 h-3" />
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        );
                                                                    })
                                                                )}
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })
                                        )}
                                    </div>
                                )}
                            </div>
                        );
                    })
                )}
            </div>

            {/* Modal Form: Program */}
            <Modal show={modalType === 'create_program' || modalType === 'edit_program'} onClose={closeModal} maxWidth="md">
                <form onSubmit={handleProgramSubmit} className="p-6">
                    <h2 className="text-base font-bold text-neutral-900 mb-4 flex items-center gap-2">
                        <FolderTree className="w-5 h-5 text-indigo-600" />
                        {modalType === 'create_program' ? 'Tambah Program Baru' : 'Edit Program'}
                    </h2>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="prog_nama" value="Nama Program *" />
                            <TextInput
                                id="prog_nama"
                                type="text"
                                className="mt-1 block w-full text-sm"
                                value={programForm.data.nama}
                                onChange={(e) => programForm.setData('nama', e.target.value)}
                                placeholder="Contoh: Program Penunjang Urusan Pemerintahan Daerah"
                                required
                            />
                            <InputError message={programForm.errors.nama} className="mt-1" />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel htmlFor="prog_kode" value="Kode Program (Opsional)" />
                                <TextInput
                                    id="prog_kode"
                                    type="text"
                                    className="mt-1 block w-full text-sm font-mono"
                                    value={programForm.data.kode}
                                    onChange={(e) => programForm.setData('kode', e.target.value)}
                                    placeholder="Contoh: 7.01.01"
                                />
                                <InputError message={programForm.errors.kode} className="mt-1" />
                            </div>

                            <div>
                                <InputLabel htmlFor="prog_tahun" value="Tahun Anggaran *" />
                                <TextInput
                                    id="prog_tahun"
                                    type="number"
                                    className="mt-1 block w-full text-sm"
                                    value={programForm.data.tahun_anggaran}
                                    onChange={(e) => programForm.setData('tahun_anggaran', parseInt(e.target.value) || 2026)}
                                    required
                                />
                                <InputError message={programForm.errors.tahun_anggaran} className="mt-1" />
                            </div>
                        </div>

                        <div>
                            <InputLabel htmlFor="prog_ket" value="Keterangan (Opsional)" />
                            <textarea
                                id="prog_ket"
                                rows="2"
                                className="mt-1 block w-full text-sm rounded-lg border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={programForm.data.keterangan}
                                onChange={(e) => programForm.setData('keterangan', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeModal}>Batal</SecondaryButton>
                        <PrimaryButton disabled={programForm.processing}>
                            {modalType === 'create_program' ? 'Simpan Program' : 'Perbarui'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Form: Kegiatan */}
            <Modal show={modalType === 'create_kegiatan' || modalType === 'edit_kegiatan'} onClose={closeModal} maxWidth="md">
                <form onSubmit={handleKegiatanSubmit} className="p-6">
                    <h2 className="text-base font-bold text-neutral-900 mb-4 flex items-center gap-2">
                        <Layers className="w-5 h-5 text-emerald-600" />
                        {modalType === 'create_kegiatan' ? 'Tambah Kegiatan Baru' : 'Edit Kegiatan'}
                    </h2>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="keg_nama" value="Nama Kegiatan *" />
                            <TextInput
                                id="keg_nama"
                                type="text"
                                className="mt-1 block w-full text-sm"
                                value={kegiatanForm.data.nama}
                                onChange={(e) => kegiatanForm.setData('nama', e.target.value)}
                                placeholder="Contoh: Administrasi Keuangan Perangkat Daerah"
                                required
                            />
                            <InputError message={kegiatanForm.errors.nama} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="keg_kode" value="Kode Kegiatan (Opsional)" />
                            <TextInput
                                id="keg_kode"
                                type="text"
                                className="mt-1 block w-full text-sm font-mono"
                                value={kegiatanForm.data.kode}
                                onChange={(e) => kegiatanForm.setData('kode', e.target.value)}
                                placeholder="Contoh: 7.01.01.2.02"
                            />
                            <InputError message={kegiatanForm.errors.kode} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="keg_ket" value="Keterangan (Opsional)" />
                            <textarea
                                id="keg_ket"
                                rows="2"
                                className="mt-1 block w-full text-sm rounded-lg border-neutral-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                value={kegiatanForm.data.keterangan}
                                onChange={(e) => kegiatanForm.setData('keterangan', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeModal}>Batal</SecondaryButton>
                        <PrimaryButton disabled={kegiatanForm.processing}>
                            {modalType === 'create_kegiatan' ? 'Simpan Kegiatan' : 'Perbarui'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Form: Sub Kegiatan */}
            <Modal show={modalType === 'create_sub_kegiatan' || modalType === 'edit_sub_kegiatan'} onClose={closeModal} maxWidth="md">
                <form onSubmit={handleSubKegiatanSubmit} className="p-6">
                    <h2 className="text-base font-bold text-neutral-900 mb-4 flex items-center gap-2">
                        <ListTree className="w-5 h-5 text-indigo-600" />
                        {modalType === 'create_sub_kegiatan' ? 'Tambah Sub Kegiatan Baru' : 'Edit Sub Kegiatan'}
                    </h2>

                    <div className="space-y-4">
                        <div>
                            <InputLabel htmlFor="sub_nama" value="Nama Sub Kegiatan *" />
                            <TextInput
                                id="sub_nama"
                                type="text"
                                className="mt-1 block w-full text-sm"
                                value={subKegiatanForm.data.nama}
                                onChange={(e) => subKegiatanForm.setData('nama', e.target.value)}
                                placeholder="Contoh: Penyediaan Gaji dan Tunjangan ASN"
                                required
                            />
                            <InputError message={subKegiatanForm.errors.nama} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="sub_kode" value="Kode Sub Kegiatan (Opsional)" />
                            <TextInput
                                id="sub_kode"
                                type="text"
                                className="mt-1 block w-full text-sm font-mono"
                                value={subKegiatanForm.data.kode}
                                onChange={(e) => subKegiatanForm.setData('kode', e.target.value)}
                                placeholder="Contoh: 7.01.01.2.02.01"
                            />
                            <InputError message={subKegiatanForm.errors.kode} className="mt-1" />
                        </div>

                        <div>
                            <InputLabel htmlFor="sub_ket" value="Keterangan (Opsional)" />
                            <textarea
                                id="sub_ket"
                                rows="2"
                                className="mt-1 block w-full text-sm rounded-lg border-neutral-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                value={subKegiatanForm.data.keterangan}
                                onChange={(e) => subKegiatanForm.setData('keterangan', e.target.value)}
                            />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeModal}>Batal</SecondaryButton>
                        <PrimaryButton disabled={subKegiatanForm.processing}>
                            {modalType === 'create_sub_kegiatan' ? 'Simpan Sub Kegiatan' : 'Perbarui'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Konfirmasi Hapus */}
            <Modal show={!!deleteConfirm} onClose={() => setDeleteConfirm(null)} maxWidth="sm">
                <div className="p-6">
                    <h2 className="text-sm font-bold text-neutral-900 mb-2 flex items-center gap-2 text-rose-600">
                        <Trash2 className="w-5 h-5" />
                        Konfirmasi Hapus
                    </h2>
                    <p className="text-xs text-neutral-600">
                        Apakah Anda yakin ingin menghapus {deleteConfirm?.type?.replace('_', ' ')}{' '}
                        <strong className="text-neutral-900">"{deleteConfirm?.name}"</strong>?
                    </p>
                    <p className="text-[11px] text-rose-600 mt-2">
                        Peringatan: Item hanya dapat dihapus bila tidak memiliki sub-hierarki atau data belanja yang terhubung.
                    </p>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={() => setDeleteConfirm(null)}>Batal</SecondaryButton>
                        <DangerButton onClick={executeDelete}>Ya, Hapus</DangerButton>
                    </div>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}
