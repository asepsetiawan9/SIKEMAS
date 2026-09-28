import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import {
    ShieldCheck,
    CheckCircle,
    XCircle,
    Paperclip,
    ExternalLink,
    AlertCircle,
    Award,
} from 'lucide-react';

export default function CamatIndex({ antrean = { data: [], links: [] } }) {
    const [activeBelanja, setActiveBelanja] = useState(null);
    const [actionType, setActionType] = useState(null); // 'setujui' or 'kembalikan'
    const { data, setData, post, processing, reset, errors } = useForm({
        catatan: '',
    });

    const openActionModal = (item, type) => {
        setActiveBelanja(item);
        setActionType(type);
        reset();
    };

    const closeModal = () => {
        setActiveBelanja(null);
        setActionType(null);
        reset();
    };

    const handleActionSubmit = (e) => {
        e.preventDefault();
        if (!activeBelanja || !actionType) return;

        const url = actionType === 'setujui'
            ? `/verifikasi/camat/${activeBelanja.id}/setujui`
            : `/verifikasi/camat/${activeBelanja.id}/kembalikan`;

        post(url, {
            onSuccess: () => closeModal(),
        });
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

    return (
        <AuthenticatedLayout title="Persetujuan Belanja (Camat)">
            <Head title="Persetujuan Akhir Belanja" />

            {/* Header banner */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 p-5 mb-6">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2.5 text-neutral-900 font-bold text-lg">
                            <ShieldCheck className="w-6 h-6 text-emerald-600" />
                            <span>Persetujuan Akhir Belanja (Camat)</span>
                        </div>
                        <p className="text-xs text-neutral-500 mt-1">
                            Daftar transaksi belanja yang telah diverifikasi oleh Sekretaris Kecamatan dan menunggu persetujuan resmi Camat.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-800 text-xs font-semibold border border-emerald-200">
                            <Award className="w-4 h-4 text-emerald-600" />
                            {antrean.total || 0} Belanja Menunggu Persetujuan
                        </span>
                    </div>
                </div>
            </div>

            {/* Antrean Table */}
            <div className="bg-white rounded-xl shadow-sm border border-neutral-200/80 overflow-hidden">
                <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                        <thead className="bg-neutral-50 text-neutral-600 uppercase tracking-wider text-[10px] font-semibold border-b border-neutral-200">
                            <tr>
                                <th className="px-4 py-3">Tanggal</th>
                                <th className="px-4 py-3">Sub Kegiatan & Uraian Belanja</th>
                                <th className="px-4 py-3 text-right">Nominal</th>
                                <th className="px-4 py-3 text-center">Bukti Belanja</th>
                                <th className="px-4 py-3">Verifikator Sekmat</th>
                                <th className="px-4 py-3 text-right">Persetujuan Camat</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-neutral-100">
                            {antrean.data.length === 0 ? (
                                <tr>
                                    <td colSpan="6" className="p-12 text-center text-neutral-400">
                                        <CheckCircle className="w-12 h-12 text-emerald-400 mx-auto mb-2" />
                                        <p className="font-semibold text-neutral-700 text-sm">Tidak ada antrean persetujuan</p>
                                        <p className="text-xs text-neutral-400 mt-0.5">
                                            Seluruh belanja yang diverifikasi Sekmat telah disetujui.
                                        </p>
                                    </td>
                                </tr>
                            ) : (
                                antrean.data.map((item) => {
                                    const dokCount = item.dokumen_bukti ? item.dokumen_bukti.length : 0;
                                    return (
                                        <tr key={item.id} className="hover:bg-neutral-50/70 transition-colors">
                                            <td className="px-4 py-3 whitespace-nowrap text-neutral-600 font-medium">
                                                {formatDate(item.tanggal_belanja)}
                                            </td>

                                            <td className="px-4 py-3 max-w-sm">
                                                <div className="text-[10px] text-neutral-400 truncate">
                                                    {item.sub_kegiatan?.nama || '-'}
                                                </div>
                                                <div className="font-semibold text-neutral-900 mt-0.5">
                                                    {item.uraian}
                                                </div>
                                                {item.penerima && (
                                                    <div className="text-[11px] text-neutral-500 mt-0.5">
                                                        Penerima: {item.penerima}
                                                    </div>
                                                )}
                                            </td>

                                            <td className="px-4 py-3 whitespace-nowrap text-right font-mono font-bold text-neutral-900 text-sm">
                                                {formatRupiah(item.nominal)}
                                            </td>

                                            <td className="px-4 py-3 whitespace-nowrap text-center">
                                                <Link
                                                    href={`/belanja/${item.id}`}
                                                    target="_blank"
                                                    className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 hover:bg-indigo-100 text-xs font-medium border border-indigo-200 transition-colors"
                                                    title="Lihat Berkas Bukti Digital"
                                                >
                                                    <Paperclip className="w-3.5 h-3.5" />
                                                    {dokCount} Berkas Bukti
                                                    <ExternalLink className="w-3 h-3 text-indigo-400" />
                                                </Link>
                                            </td>

                                            <td className="px-4 py-3 whitespace-nowrap text-neutral-600">
                                                <div className="font-medium text-neutral-800">{item.verifier?.name || 'Sekretaris Kecamatan'}</div>
                                                <div className="text-[10px] text-emerald-700 font-medium">Telah Diverifikasi</div>
                                            </td>

                                            <td className="px-4 py-3 whitespace-nowrap text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    <button
                                                        onClick={() => openActionModal(item, 'setujui')}
                                                        className="inline-flex items-center gap-1 bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors shadow-sm"
                                                    >
                                                        <CheckCircle className="w-3.5 h-3.5" />
                                                        Setujui Resmi
                                                    </button>
                                                    <button
                                                        onClick={() => openActionModal(item, 'kembalikan')}
                                                        className="inline-flex items-center gap-1 bg-white hover:bg-rose-50 text-rose-600 px-3 py-1.5 rounded-lg text-xs font-medium border border-rose-200 transition-colors"
                                                    >
                                                        <XCircle className="w-3.5 h-3.5" />
                                                        Kembalikan
                                                    </button>
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
                {antrean.links && antrean.links.length > 3 && (
                    <div className="p-4 border-t border-neutral-100 flex items-center justify-between">
                        <span className="text-xs text-neutral-500">
                            Menampilkan {antrean.from || 0} - {antrean.to || 0} dari {antrean.total || 0} antrean
                        </span>
                        <div className="flex items-center gap-1">
                            {antrean.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    className={`px-3 py-1 text-xs rounded border transition-colors ${
                                        link.active
                                            ? 'bg-emerald-600 text-white border-emerald-600 font-semibold'
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

            {/* Modal Persetujuan Camat */}
            <Modal show={!!activeBelanja} onClose={closeModal} maxWidth="md">
                <form onSubmit={handleActionSubmit} className="p-6">
                    <h2 className="text-base font-bold text-neutral-900 mb-2 flex items-center gap-2">
                        {actionType === 'setujui' ? (
                            <>
                                <ShieldCheck className="w-5 h-5 text-emerald-600" />
                                <span>Persetujuan Resmi Belanja oleh Camat</span>
                            </>
                        ) : (
                            <>
                                <XCircle className="w-5 h-5 text-rose-600" />
                                <span>Kembalikan Belanja ke Operator</span>
                            </>
                        )}
                    </h2>

                    <div className="p-3 my-3 bg-neutral-50 rounded-lg border border-neutral-200 text-xs">
                        <div className="font-semibold text-neutral-900">{activeBelanja?.uraian}</div>
                        <div className="flex items-center justify-between mt-1 text-neutral-500">
                            <span>Nominal: <strong className="text-neutral-800 font-mono">{formatRupiah(activeBelanja?.nominal)}</strong></span>
                            <span>{activeBelanja?.dokumen_bukti?.length || 0} Berkas Bukti Terlampir</span>
                        </div>
                    </div>

                    <div className="mt-4">
                        <label className="text-xs font-semibold text-neutral-700 block mb-1">
                            {actionType === 'kembalikan' ? 'Catatan Pengembalian *' : 'Catatan Persetujuan (Opsional)'}
                        </label>
                        <textarea
                            rows="3"
                            value={data.catatan}
                            onChange={(e) => setData('catatan', e.target.value)}
                            className="w-full text-xs rounded-lg border-neutral-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder={
                                actionType === 'kembalikan'
                                    ? 'Jelaskan alasan pengembalian...'
                                    : 'Catatan tambahan (bila ada)...'
                            }
                            required={actionType === 'kembalikan'}
                        />
                        {errors.catatan && (
                            <p className="text-[11px] text-rose-600 mt-1">{errors.catatan}</p>
                        )}
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={closeModal}>Batal</SecondaryButton>
                        {actionType === 'setujui' ? (
                            <PrimaryButton disabled={processing} className="bg-emerald-600 hover:bg-emerald-700">
                                Setujui Resmi
                            </PrimaryButton>
                        ) : (
                            <DangerButton disabled={processing}>
                                Kembalikan
                            </DangerButton>
                        )}
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}
