import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import {
    Wallet,
    CheckCircle,
    Package,
    ArrowUpRight,
    Clock,
    AlertTriangle,
    BarChart3,
    PieChart as PieChartIcon,
    AlertCircle,
    ChevronRight,
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

export default function StafSekmatDashboard({
    role,
    stats = {},
    kegiatanList = [],
    warningsPagu = [],
    realisasiKegiatanChart = [],
    spjStatusChart = [],
    asetKondisiChart = [],
    recentSpj = [],
}) {
    const isSekmat = role === 'sekmat';
    const pageTitle = isSekmat
        ? 'Dashboard Verifikasi & Pengawasan Sekmat'
        : 'Dashboard Operasional Keuangan & BMD';

    const hasSpjChart = spjStatusChart.some((item) => item.value > 0);
    const hasAsetChart = asetKondisiChart.some((item) => item.value > 0);
    const hasKegiatanChart = realisasiKegiatanChart.length > 0;

    return (
        <AuthenticatedLayout title={pageTitle}>
            <Head title={pageTitle} />

            <div className="space-y-6">
                {/* Banner Hero */}
                <div className="bg-gradient-to-r from-primary to-primary-dark rounded-card p-6 text-white shadow-md flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div>
                        <span className="px-2.5 py-1 bg-white/20 text-white rounded-full text-xs font-semibold uppercase tracking-wider">
                            {isSekmat ? 'Verifikasi & Pengawasan' : 'Operasional Keuangan & BMD'}
                        </span>
                        <h2 className="text-xl font-bold mt-2">
                            {isSekmat
                                ? 'Pusat Pengawasan Anggaran & Verifikasi SPJ'
                                : 'Pusat Pengelolaan Keuangan & Aset Terintegrasi'}
                        </h2>
                        <p className="text-white/80 text-sm mt-1">
                            Kecamatan Caringin — Tahun Anggaran 2026. Pantau penyerapan pagu, verifikasi berkas, dan kondisi inventaris aset secara real-time.
                        </p>
                    </div>

                    <div className="flex gap-2 shrink-0">
                        {isSekmat ? (
                            <Link
                                href="/spj"
                                className="px-4 py-2.5 bg-amber-400 text-neutral-900 rounded-btn font-bold text-sm hover:bg-amber-300 shadow transition-colors flex items-center gap-2"
                            >
                                <CheckCircle className="w-4 h-4" />
                                {stats.pending_verifikasi || 0} Antrean Verifikasi
                            </Link>
                        ) : (
                            <Link
                                href="/spj"
                                className="px-4 py-2.5 bg-white text-primary rounded-btn font-bold text-sm hover:bg-neutral-50 shadow transition-colors flex items-center gap-2"
                            >
                                <Clock className="w-4 h-4" />
                                {stats.pending_konsolidasi || 0} Perlu Konsolidasi
                            </Link>
                        )}
                    </div>
                </div>

                {/* Over 80% Pagu Warnings Banner (BR-KEU-03) */}
                {warningsPagu.length > 0 && (
                    <div className="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r-lg shadow-sm space-y-2">
                        <div className="flex items-center gap-2">
                            <AlertTriangle className="w-5 h-5 text-amber-600 shrink-0" />
                            <h4 className="text-sm font-bold text-amber-900">
                                Peringatan Ambang Pagu (BR-KEU-03): {warningsPagu.length} Kegiatan Mencapai &gt; 80%
                            </h4>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-2 pt-1">
                            {warningsPagu.map((item) => (
                                <div
                                    key={item.id}
                                    className="bg-white/80 rounded px-3 py-2 border border-amber-200 text-xs flex justify-between items-center"
                                >
                                    <span className="font-medium text-neutral-800 truncate mr-2">
                                        {item.nama}
                                    </span>
                                    <span className="font-bold text-amber-700 shrink-0">
                                        {item.persen}% serapan ({formatRupiah(item.realisasi)})
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Core Metrics Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <Wallet className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">Total Pagu 2026</p>
                            <h3 className="text-lg font-bold text-neutral-900 mt-0.5">
                                {formatRupiah(stats.total_pagu || 0)}
                            </h3>
                            <span className="text-[11px] text-neutral-500">
                                {stats.total_kegiatan || 0} kegiatan aktif
                            </span>
                        </div>
                    </div>

                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                            <ArrowUpRight className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">Total Realisasi</p>
                            <h3 className="text-lg font-bold text-neutral-900 mt-0.5 text-emerald-700">
                                {formatRupiah(stats.total_realisasi || 0)}
                            </h3>
                            <span className="text-[11px] font-semibold text-emerald-600">
                                {stats.persen_realisasi || 0}% terserap
                            </span>
                        </div>
                    </div>

                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
                            <CheckCircle className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">
                                {isSekmat ? 'Antrean Verifikasi' : 'Perlu Konsolidasi'}
                            </p>
                            <h3 className="text-xl font-bold text-neutral-900 mt-0.5 text-amber-600">
                                {isSekmat ? stats.pending_verifikasi || 0 : stats.pending_konsolidasi || 0}
                            </h3>
                            <span className="text-[11px] text-neutral-500">
                                {stats.diverifikasi || 0} SPJ disahkan
                            </span>
                        </div>
                    </div>

                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                            <Package className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">Total Aset BMD</p>
                            <h3 className="text-xl font-bold text-neutral-900 mt-0.5">
                                {stats.total_aset || 0} Unit
                            </h3>
                            <span className="text-[11px] text-neutral-500">
                                {formatRupiah(stats.total_nilai_aset || 0)}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Main Charts Section */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Bar Chart: Realisasi Anggaran per Kegiatan */}
                    <div className="lg:col-span-2 bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                        <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                            <div>
                                <h3 className="font-bold text-neutral-900 text-sm flex items-center gap-2">
                                    <BarChart3 className="w-4 h-4 text-primary" />
                                    Rekap Realisasi Anggaran per Kegiatan
                                </h3>
                                <p className="text-xs text-neutral-500 mt-0.5">
                                    Perbandingan Pagu Anggaran vs Realisasi SPJ Diverifikasi (Rp)
                                </p>
                            </div>
                        </div>

                        {hasKegiatanChart ? (
                            <div className="h-72 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={realisasiKegiatanChart}
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
                                        <Legend wrapperStyle={{ fontSize: 12, paddingTop: 10 }} />
                                        <Bar dataKey="pagu" name="Pagu Anggaran" fill="#93c5fd" radius={[4, 4, 0, 0]} />
                                        <Bar dataKey="realisasi" name="Realisasi SPJ" fill="#2563eb" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        ) : (
                            <div className="h-72 flex items-center justify-center text-xs text-neutral-400">
                                Belum ada data kegiatan terdaftar.
                            </div>
                        )}
                    </div>

                    {/* Side Distribution Charts: SPJ & Aset */}
                    <div className="space-y-6">
                        {/* Pie Chart: SPJ Status */}
                        <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm space-y-3">
                            <h3 className="font-bold text-neutral-900 text-xs uppercase tracking-wider flex items-center gap-1.5 text-neutral-600">
                                <PieChartIcon className="w-3.5 h-3.5 text-primary" />
                                Distribusi Status SPJ
                            </h3>
                            {hasSpjChart ? (
                                <div className="h-44 w-full">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={spjStatusChart}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={35}
                                                outerRadius={60}
                                                paddingAngle={3}
                                                dataKey="value"
                                            >
                                                {spjStatusChart.map((entry, index) => (
                                                    <Cell key={`cell-spj-${index}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip formatter={(value, name) => [`${value} Berkas`, name]} />
                                            <Legend wrapperStyle={{ fontSize: 10 }} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            ) : (
                                <div className="h-44 flex items-center justify-center text-xs text-neutral-400">
                                    Belum ada data SPJ.
                                </div>
                            )}
                        </div>

                        {/* Pie Chart: Kondisi Aset */}
                        <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm space-y-3">
                            <h3 className="font-bold text-neutral-900 text-xs uppercase tracking-wider flex items-center gap-1.5 text-neutral-600">
                                <Package className="w-3.5 h-3.5 text-primary" />
                                Kondisi Fisik BMD
                            </h3>
                            {hasAsetChart ? (
                                <div className="h-44 w-full">
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie
                                                data={asetKondisiChart}
                                                cx="50%"
                                                cy="50%"
                                                innerRadius={35}
                                                outerRadius={60}
                                                paddingAngle={3}
                                                dataKey="value"
                                            >
                                                {asetKondisiChart.map((entry, index) => (
                                                    <Cell key={`cell-aset-${index}`} fill={entry.color} />
                                                ))}
                                            </Pie>
                                            <Tooltip formatter={(value, name) => [`${value} Unit`, name]} />
                                            <Legend wrapperStyle={{ fontSize: 10 }} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </div>
                            ) : (
                                <div className="h-44 flex items-center justify-center text-xs text-neutral-400">
                                    Belum ada data aset.
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Recent SPJs Table */}
                <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                    <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                        <div>
                            <h3 className="font-bold text-neutral-900 text-base">
                                Alur & Riwayat SPJ Terkini
                            </h3>
                            <p className="text-xs text-neutral-500 mt-0.5">
                                Pantau dan tindaklanjuti proses konsolidasi & verifikasi dokumen
                            </p>
                        </div>
                        <Link
                            href="/spj"
                            className="text-xs font-semibold text-primary hover:text-primary-dark inline-flex items-center gap-1"
                        >
                            Buka Seluruh SPJ <ChevronRight className="w-3 h-3" />
                        </Link>
                    </div>

                    {recentSpj.length === 0 ? (
                        <div className="text-center py-6 text-neutral-500 text-sm">
                            Belum ada riwayat SPJ terdata.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-neutral-200 text-neutral-500">
                                        <th className="py-2.5 font-semibold">Nomor SPJ</th>
                                        <th className="py-2.5 font-semibold">Kegiatan</th>
                                        <th className="py-2.5 font-semibold">Pengaju (Kasi)</th>
                                        <th className="py-2.5 font-semibold">Nominal</th>
                                        <th className="py-2.5 font-semibold">Tanggal</th>
                                        <th className="py-2.5 font-semibold">Status</th>
                                        <th className="py-2.5 font-semibold text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-100">
                                    {recentSpj.map((item) => (
                                        <tr key={item.id} className="hover:bg-neutral-50/60">
                                            <td className="py-3 font-semibold text-neutral-900">
                                                {item.nomor_spj || 'Draft / Belum Bernomor'}
                                            </td>
                                            <td className="py-3 text-neutral-700 max-w-[200px] truncate">
                                                {item.kegiatan?.nama}
                                            </td>
                                            <td className="py-3 text-neutral-600">
                                                {item.diajukan_oleh?.name || item.pengaju?.name || '-'}
                                            </td>
                                            <td className="py-3 font-semibold text-neutral-800">
                                                {formatRupiah(item.nominal)}
                                            </td>
                                            <td className="py-3 text-neutral-500">
                                                {formatDate(item.tanggal_pengajuan)}
                                            </td>
                                            <td className="py-3">
                                                <StatusBadge status={item.status} />
                                            </td>
                                            <td className="py-3 text-right">
                                                <Link
                                                    href={`/spj/${item.id}`}
                                                    className="font-semibold text-primary hover:text-primary-dark"
                                                >
                                                    Periksa
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
