import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatRupiah } from '@/Utils/formatRupiah';
import {
    Building2,
    Wallet,
    ArrowUpRight,
    Package,
    ShieldCheck,
    AlertTriangle,
    Flame,
    BarChart3,
    PieChart as PieChartIcon,
    Layers,
} from 'lucide-react';
import {
    ResponsiveContainer,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
    PieChart,
    Pie,
    Cell,
} from 'recharts';

export default function CamatDashboard({
    stats = {},
    kegiatanSummary = [],
    kegiatanChart = [],
    seksiSummary = [],
    asetKondisiChart = [],
}) {
    const aset = stats.aset || {};
    const hasAsetChart = asetKondisiChart.some((i) => i.value > 0);
    const hasKegiatanChart = kegiatanChart.length > 0;

    return (
        <AuthenticatedLayout title="Dashboard Eksekutif Camat">
            <Head title="Dashboard Eksekutif Camat" />

            <div className="space-y-6">
                {/* Executive Welcome Hero Banner */}
                <div className="bg-gradient-to-r from-[#173e63] via-[#1a5b94] to-[#12426b] rounded-card p-6 text-white shadow-lg flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <div className="inline-flex items-center gap-2 px-3 py-1 bg-amber-400/20 text-amber-300 rounded-full text-xs font-bold uppercase tracking-wider border border-amber-300/30">
                            <ShieldCheck className="w-3.5 h-3.5" />
                            Ringkasan Eksekutif Wilayah
                        </div>
                        <h2 className="text-2xl font-bold mt-2">
                            Pimpinan Wilayah Kecamatan Caringin
                        </h2>
                        <p className="text-white/80 text-sm mt-1 max-w-xl">
                            Monitoring terpadu capaian realisasi keuangan kegiatan seksi dan kondisi inventaris aset Barang Milik Daerah (BMD) Tahun Anggaran 2026.
                        </p>
                    </div>

                    <div className="bg-white/10 backdrop-blur px-6 py-3.5 rounded-xl border border-white/20 text-right shrink-0">
                        <span className="text-xs text-white/70 block uppercase font-medium">Serapan Anggaran</span>
                        <span className="text-3xl font-black text-amber-300">
                            {stats.persen_realisasi || 0}%
                        </span>
                    </div>
                </div>

                {/* Over 80% Pagu Alert if applicable */}
                {stats.persen_realisasi > 80 && (
                    <div className="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg flex items-center gap-3 shadow-sm">
                        <Flame className="w-5 h-5 text-amber-600 shrink-0" />
                        <div>
                            <p className="text-sm font-bold text-amber-800">
                                Peringatan Eksekutif: Realisasi Anggaran Telah Melampaui Batas Ambang 80%
                            </p>
                            <p className="text-xs text-amber-700 mt-0.5">
                                Total realisasi saat ini telah menyentuh {stats.persen_realisasi}% dari total pagu kecamatan.
                            </p>
                        </div>
                    </div>
                )}

                {/* Executive Progress Bar */}
                <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-3">
                    <div className="flex justify-between items-center">
                        <div>
                            <h3 className="font-bold text-neutral-900 text-sm">
                                Akumulasi Penyerapan Anggaran Kecamatan Caringin 2026
                            </h3>
                            <p className="text-xs text-neutral-500 mt-0.5">
                                Realisasi SPJ Disahkan vs Total Pagu Anggaran
                            </p>
                        </div>
                        <span className="text-sm font-black px-3 py-1 rounded bg-primary-light text-primary">
                            {stats.persen_realisasi || 0}%
                        </span>
                    </div>

                    <div className="w-full bg-neutral-200 h-4 rounded-full overflow-hidden p-0.5">
                        <div
                            className={`h-full rounded-full transition-all duration-700 ${
                                stats.persen_realisasi > 80 ? 'bg-amber-500' : 'bg-primary'
                            }`}
                            style={{ width: `${Math.min(stats.persen_realisasi || 0, 100)}%` }}
                        />
                    </div>

                    <div className="flex justify-between text-xs text-neutral-600 font-medium">
                        <span>Realisasi SPJ: <strong className="text-emerald-700">{formatRupiah(stats.total_realisasi || 0)}</strong></span>
                        <span>Sisa Pagu: <strong className="text-blue-700">{formatRupiah(stats.sisa_pagu || 0)}</strong></span>
                        <span>Total Pagu: <strong className="text-neutral-900">{formatRupiah(stats.total_pagu || 0)}</strong></span>
                    </div>
                </div>

                {/* Core Financial Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <Wallet className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">Total Pagu Kecamatan</p>
                            <h3 className="text-xl font-bold text-neutral-900 mt-0.5">
                                {formatRupiah(stats.total_pagu || 0)}
                            </h3>
                        </div>
                    </div>

                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <ArrowUpRight className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">Total Realisasi SPJ</p>
                            <h3 className="text-xl font-bold text-neutral-900 mt-0.5 text-emerald-700">
                                {formatRupiah(stats.total_realisasi || 0)}
                            </h3>
                        </div>
                    </div>

                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <Building2 className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">Sisa Pagu Tersedia</p>
                            <h3 className="text-xl font-bold text-neutral-900 mt-0.5 text-indigo-700">
                                {formatRupiah(stats.sisa_pagu || 0)}
                            </h3>
                        </div>
                    </div>
                </div>

                {/* Charts Grid: Activities & Asset Conditions */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Bar Chart: Kegiatan Pagu vs Realisasi */}
                    <div className="lg:col-span-2 bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                        <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                            <div>
                                <h3 className="font-bold text-neutral-900 text-sm flex items-center gap-2">
                                    <BarChart3 className="w-4 h-4 text-primary" />
                                    Penyerapan Anggaran per Kegiatan
                                </h3>
                                <p className="text-xs text-neutral-500 mt-0.5">
                                    Perbandingan Pagu vs Realisasi seluruh kegiatan
                                </p>
                            </div>
                        </div>

                        {hasKegiatanChart ? (
                            <div className="h-64 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={kegiatanChart}
                                        margin={{ top: 10, right: 10, left: 10, bottom: 25 }}
                                    >
                                        <CartesianGrid strokeDasharray="3 3" stroke="#f1f5f9" />
                                        <XAxis
                                            dataKey="name"
                                            tick={{ fontSize: 11, fill: '#64748b' }}
                                            angle={-15}
                                            textAnchor="end"
                                        />
                                        <YAxis
                                            tick={{ fontSize: 10, fill: '#64748b' }}
                                            tickFormatter={(val) => `Rp ${(val / 1000000).toFixed(0)}Jt`}
                                        />
                                        <Tooltip
                                            formatter={(value) => [formatRupiah(value), '']}
                                            labelFormatter={(label, payload) => payload?.[0]?.payload?.fullName || label}
                                        />
                                        <Legend wrapperStyle={{ fontSize: 11, paddingTop: 10 }} />
                                        <Bar dataKey="pagu" name="Pagu" fill="#93c5fd" radius={[4, 4, 0, 0]} />
                                        <Bar dataKey="realisasi" name="Realisasi" fill="#2563eb" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        ) : (
                            <div className="h-64 flex items-center justify-center text-xs text-neutral-400">
                                Belum ada data kegiatan terdaftar.
                            </div>
                        )}
                    </div>

                    {/* Pie Chart: BMD Asset Condition */}
                    <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4 flex flex-col justify-between">
                        <div className="border-b border-neutral-100 pb-3">
                            <h3 className="font-bold text-neutral-900 text-sm flex items-center gap-2">
                                <PieChartIcon className="w-4 h-4 text-primary" />
                                Kondisi Inventaris BMD
                            </h3>
                            <p className="text-xs text-neutral-500 mt-0.5">
                                Total Nilai: {formatRupiah(aset.total_nilai || 0)}
                            </p>
                        </div>

                        {hasAsetChart ? (
                            <div className="h-48 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={asetKondisiChart}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={45}
                                            outerRadius={70}
                                            paddingAngle={4}
                                            dataKey="value"
                                        >
                                            {asetKondisiChart.map((entry, index) => (
                                                <Cell key={`cell-camat-aset-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={(value, name) => [`${value} Unit`, name]} />
                                        <Legend wrapperStyle={{ fontSize: 10 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        ) : (
                            <div className="h-48 flex items-center justify-center text-xs text-neutral-400">
                                Belum ada data aset.
                            </div>
                        )}

                        <div className="pt-2 border-t border-neutral-100 grid grid-cols-3 gap-2 text-center">
                            <div>
                                <span className="text-[10px] text-neutral-500">Baik</span>
                                <p className="text-xs font-bold text-emerald-600">{aset.baik || 0}</p>
                            </div>
                            <div>
                                <span className="text-[10px] text-neutral-500">Rusak Ringan</span>
                                <p className="text-xs font-bold text-amber-600">{aset.rusak_ringan || 0}</p>
                            </div>
                            <div>
                                <span className="text-[10px] text-neutral-500">Rusak Berat</span>
                                <p className="text-xs font-bold text-rose-600">{aset.rusak_berat || 0}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Seksi Performance Breakdown */}
                {seksiSummary.length > 0 && (
                    <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                        <div className="border-b border-neutral-100 pb-3 flex items-center gap-2">
                            <Layers className="w-4 h-4 text-primary" />
                            <div>
                                <h3 className="font-bold text-neutral-900 text-sm">
                                    Rekapitulasi Penyerapan Anggaran per Seksi
                                </h3>
                                <p className="text-xs text-neutral-500 mt-0.5">
                                    Perbandingan serapan kegiatan antar 5 seksi di Kecamatan Caringin
                                </p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                            {seksiSummary.map((s) => (
                                <div key={s.seksi} className="p-3.5 rounded-xl border border-neutral-200 bg-neutral-50/60 flex flex-col justify-between">
                                    <div>
                                        <span className="text-xs font-bold text-neutral-800">{s.label}</span>
                                        <p className="text-[11px] text-neutral-500 mt-1">Pagu: {formatRupiah(s.pagu)}</p>
                                        <p className="text-[11px] text-emerald-700 font-semibold">Real: {formatRupiah(s.realisasi)}</p>
                                    </div>
                                    <div className="mt-3">
                                        <div className="w-full bg-neutral-200 h-1.5 rounded-full overflow-hidden">
                                            <div
                                                className={`h-full rounded-full ${s.persen > 80 ? 'bg-amber-500' : 'bg-primary'}`}
                                                style={{ width: `${Math.min(s.persen, 100)}%` }}
                                            />
                                        </div>
                                        <span className="text-[10px] font-bold text-neutral-600 block text-right mt-1">
                                            {s.persen}%
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Per-Activity Budget Table */}
                <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                    <div className="border-b border-neutral-100 pb-3">
                        <h3 className="font-bold text-neutral-900 text-base">
                            Tingkat Penyerapan Anggaran per Kegiatan
                        </h3>
                        <p className="text-xs text-neutral-500 mt-0.5">
                            Rincian realisasi pagu kegiatan dari seluruh seksi di Kecamatan Caringin
                        </p>
                    </div>

                    <div className="overflow-x-auto">
                        <table className="w-full text-left text-xs">
                            <thead>
                                <tr className="border-b border-neutral-200 text-neutral-500">
                                    <th className="py-2.5 font-semibold">Nama Kegiatan</th>
                                    <th className="py-2.5 font-semibold">Pengampu (Seksi)</th>
                                    <th className="py-2.5 font-semibold">Pagu</th>
                                    <th className="py-2.5 font-semibold">Realisasi</th>
                                    <th className="py-2.5 font-semibold w-48">Progres</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-neutral-100">
                                {kegiatanSummary.map((k) => (
                                    <tr key={k.id} className="hover:bg-neutral-50/60">
                                        <td className="py-3 font-semibold text-neutral-900">
                                            {k.nama}
                                        </td>
                                        <td className="py-3 text-neutral-600">
                                            {k.kasi_nama} ({ucfirst(k.seksi || '-')})
                                        </td>
                                        <td className="py-3 text-neutral-800 font-medium">
                                            {formatRupiah(k.pagu)}
                                        </td>
                                        <td className="py-3 text-emerald-700 font-semibold">
                                            {formatRupiah(k.realisasi)}
                                        </td>
                                        <td className="py-3">
                                            <div className="flex items-center gap-2">
                                                <div className="flex-1 bg-neutral-200 h-2 rounded-full overflow-hidden">
                                                    <div
                                                        className={`h-full rounded-full ${
                                                            k.persen > 80 ? 'bg-amber-500' : 'bg-primary'
                                                        }`}
                                                        style={{ width: `${Math.min(k.persen, 100)}%` }}
                                                    />
                                                </div>
                                                <span className="text-[11px] font-bold text-neutral-700 shrink-0 w-8 text-right">
                                                    {k.persen}%
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function ucfirst(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
