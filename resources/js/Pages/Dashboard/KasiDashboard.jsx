import React from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import { formatRupiah } from '@/Utils/formatRupiah';
import { formatDate } from '@/Utils/formatDate';
import {
    ClipboardList,
    Wallet,
    FileText,
    AlertCircle,
    PlusCircle,
    ArrowUpRight,
    PieChart as PieChartIcon,
    BarChart3,
} from 'lucide-react';
import {
    ResponsiveContainer,
    PieChart,
    Pie,
    Cell,
    Tooltip,
    Legend,
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
} from 'recharts';

export default function KasiDashboard({
    kegiatanList = [],
    kegiatanChart = [],
    spjStatusChart = [],
    recentSpj = [],
    stats = {},
}) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const seksiTitle = user?.seksi
        ? user.seksi.charAt(0).toUpperCase() + user.seksi.slice(1)
        : 'Seksi';

    const hasSpjData = spjStatusChart.some((item) => item.value > 0);
    const hasKegiatanChart = kegiatanChart.length > 0;

    return (
        <AuthenticatedLayout title={`Dashboard Seksi ${seksiTitle}`}>
            <Head title={`Dashboard Seksi ${seksiTitle}`} />

            <div className="space-y-6">
                {/* Welcome Hero Card */}
                <div className="bg-gradient-to-r from-primary to-primary-dark rounded-card p-6 text-white shadow-md flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <span className="inline-block px-2.5 py-0.5 rounded-full bg-white/20 text-white text-xs font-semibold uppercase tracking-wider mb-2">
                            Ruang Kerja Kasi
                        </span>
                        <h2 className="text-xl font-bold">
                            Selamat Datang, {user?.name}
                        </h2>
                        <p className="text-white/80 text-sm mt-1">
                            Kepala Seksi {seksiTitle} — Kecamatan Caringin. Pantau realisasi anggaran dan ajukan SPJ kegiatan Anda.
                        </p>
                    </div>
                    <Link
                        href="/spj/create"
                        className="inline-flex items-center gap-2 px-4 py-2.5 bg-white text-primary rounded-btn font-semibold text-sm hover:bg-neutral-50 shadow transition-colors shrink-0"
                    >
                        <PlusCircle className="w-4 h-4" />
                        Ajukan SPJ Baru
                    </Link>
                </div>

                {/* Rejected SPJ Alert banner if any */}
                {stats.spj_ditolak > 0 && (
                    <div className="bg-rose-50 border-l-4 border-rose-500 p-4 rounded-r-lg flex items-center justify-between shadow-sm">
                        <div className="flex items-center gap-3">
                            <AlertCircle className="w-5 h-5 text-rose-500 shrink-0" />
                            <div>
                                <p className="text-sm font-bold text-rose-800">
                                    Perhatian: Ada {stats.spj_ditolak} Pengajuan SPJ Ditolak
                                </p>
                                <p className="text-xs text-rose-600 mt-0.5">
                                    SPJ yang ditolak sedang dalam proses revisi bersama Staf Keuangan sebelum diajukan kembali ke Sekmat.
                                </p>
                            </div>
                        </div>
                        <Link
                            href="/spj"
                            className="text-xs font-semibold text-rose-700 hover:text-rose-900 underline"
                        >
                            Lihat Rincian
                        </Link>
                    </div>
                )}

                {/* Metric Cards Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                            <ClipboardList className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">Kegiatan Seksi</p>
                            <h3 className="text-xl font-bold text-neutral-900 mt-0.5">
                                {stats.total_kegiatan || 0}
                            </h3>
                        </div>
                    </div>

                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                            <Wallet className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">Total Pagu</p>
                            <h3 className="text-lg font-bold text-neutral-900 mt-0.5">
                                {formatRupiah(stats.total_pagu || 0)}
                            </h3>
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
                                {stats.persen_realisasi || 0}% serapan
                            </span>
                        </div>
                    </div>

                    <div className="bg-surface rounded-card p-5 border border-neutral-200/80 shadow-sm flex items-center gap-4">
                        <div className="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center shrink-0">
                            <FileText className="w-6 h-6" />
                        </div>
                        <div>
                            <p className="text-xs font-medium text-neutral-500">SPJ Diajukan</p>
                            <h3 className="text-xl font-bold text-neutral-900 mt-0.5">
                                {stats.total_spj_diajukan || 0}
                            </h3>
                            <span className="text-[11px] text-neutral-400">
                                {stats.spj_diverifikasi || 0} diverifikasi
                            </span>
                        </div>
                    </div>
                </div>

                {/* Charts Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Bar Chart: Pagu vs Realisasi Kegiatan */}
                    <div className="lg:col-span-2 bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                        <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                            <div>
                                <h3 className="font-bold text-neutral-900 text-sm flex items-center gap-2">
                                    <BarChart3 className="w-4 h-4 text-primary" />
                                    Perbandingan Pagu vs Realisasi per Kegiatan
                                </h3>
                                <p className="text-xs text-neutral-500 mt-0.5">
                                    Angka real-time kegiatan anggaran Seksi {seksiTitle}
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
                                        <Legend wrapperStyle={{ fontSize: 12, paddingTop: 10 }} />
                                        <Bar dataKey="pagu" name="Pagu Anggaran" fill="#93c5fd" radius={[4, 4, 0, 0]} />
                                        <Bar dataKey="realisasi" name="Realisasi" fill="#2563eb" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        ) : (
                            <div className="h-64 flex items-center justify-center text-xs text-neutral-400">
                                Belum ada data kegiatan untuk grafik ini.
                            </div>
                        )}
                    </div>

                    {/* Pie Chart: Status SPJ */}
                    <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4 flex flex-col justify-between">
                        <div className="border-b border-neutral-100 pb-3">
                            <h3 className="font-bold text-neutral-900 text-sm flex items-center gap-2">
                                <PieChartIcon className="w-4 h-4 text-primary" />
                                Status Berkas SPJ
                            </h3>
                            <p className="text-xs text-neutral-500 mt-0.5">
                                Distribusi status verifikasi berkas pengajuan
                            </p>
                        </div>

                        {hasSpjData ? (
                            <div className="h-56 w-full">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={spjStatusChart}
                                            cx="50%"
                                            cy="50%"
                                            innerRadius={50}
                                            outerRadius={80}
                                            paddingAngle={4}
                                            dataKey="value"
                                        >
                                            {spjStatusChart.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={(value, name) => [`${value} Dokumen`, name]} />
                                        <Legend wrapperStyle={{ fontSize: 11 }} />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        ) : (
                            <div className="h-56 flex items-center justify-center text-xs text-neutral-400">
                                Belum ada riwayat pengajuan SPJ.
                            </div>
                        )}

                        <div className="pt-2 border-t border-neutral-100 grid grid-cols-3 gap-2 text-center">
                            <div>
                                <span className="text-[10px] text-neutral-500">Disetujui</span>
                                <p className="text-xs font-bold text-emerald-600">{stats.spj_diverifikasi || 0}</p>
                            </div>
                            <div>
                                <span className="text-[10px] text-neutral-500">Diproses</span>
                                <p className="text-xs font-bold text-blue-600">{stats.spj_menunggu || 0}</p>
                            </div>
                            <div>
                                <span className="text-[10px] text-neutral-500">Ditolak</span>
                                <p className="text-xs font-bold text-rose-600">{stats.spj_ditolak || 0}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Kegiatan & Progress List */}
                <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                    <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                        <div>
                            <h3 className="font-bold text-neutral-900 text-base">
                                Daftar Kegiatan Anggaran 2026
                            </h3>
                            <p className="text-xs text-neutral-500 mt-0.5">
                                Realisasi vs Pagu per kegiatan di bawah Seksi {seksiTitle}
                            </p>
                        </div>
                    </div>

                    {kegiatanList.length === 0 ? (
                        <div className="text-center py-8 text-neutral-500 text-sm">
                            Belum ada kegiatan yang terdaftar untuk seksi ini.
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {kegiatanList.map((keg) => (
                                <div
                                    key={keg.id}
                                    className="p-4 rounded-lg border border-neutral-200 hover:border-primary/40 transition-colors bg-neutral-50/50"
                                >
                                    <div className="flex justify-between items-start gap-2">
                                        <div>
                                            <span className="text-[10px] font-mono px-2 py-0.5 rounded bg-neutral-200 text-neutral-700">
                                                {keg.kode_rekening}
                                            </span>
                                            <h4 className="font-semibold text-sm text-neutral-900 mt-1.5">
                                                {keg.nama}
                                            </h4>
                                        </div>
                                        <span className="text-xs font-bold text-primary">
                                            {keg.persen}%
                                        </span>
                                    </div>

                                    {/* Progress Bar */}
                                    <div className="w-full bg-neutral-200 h-2 rounded-full mt-3 overflow-hidden">
                                        <div
                                            className={`h-full rounded-full transition-all duration-500 ${
                                                keg.persen > 80 ? 'bg-amber-500' : 'bg-primary'
                                            }`}
                                            style={{ width: `${Math.min(keg.persen, 100)}%` }}
                                        />
                                    </div>

                                    <div className="flex justify-between text-xs text-neutral-600 mt-2 font-medium">
                                        <span>Realisasi: {formatRupiah(keg.realisasi)}</span>
                                        <span>Pagu: {formatRupiah(keg.pagu)}</span>
                                    </div>
                                    <div className="text-[11px] text-neutral-500 mt-1">
                                        Sisa Pagu: <span className="font-semibold text-neutral-700">{formatRupiah(keg.sisa_pagu)}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Recent Submissions */}
                <div className="bg-surface rounded-card p-6 border border-neutral-200/80 shadow-sm space-y-4">
                    <div className="flex items-center justify-between border-b border-neutral-100 pb-3">
                        <h3 className="font-bold text-neutral-900 text-base">
                            Pengajuan SPJ Terakhir
                        </h3>
                        <Link
                            href="/spj"
                            className="text-xs font-semibold text-primary hover:text-primary-dark"
                        >
                            Lihat Semua SPJ
                        </Link>
                    </div>

                    {recentSpj.length === 0 ? (
                        <div className="text-center py-6 text-neutral-500 text-sm">
                            Belum ada riwayat pengajuan SPJ.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead>
                                    <tr className="border-b border-neutral-200 text-neutral-500">
                                        <th className="py-2.5 font-semibold">Nomor / Kegiatan</th>
                                        <th className="py-2.5 font-semibold">Nominal</th>
                                        <th className="py-2.5 font-semibold">Tanggal</th>
                                        <th className="py-2.5 font-semibold">Status</th>
                                        <th className="py-2.5 font-semibold text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-neutral-100">
                                    {recentSpj.map((spj) => (
                                        <tr key={spj.id} className="hover:bg-neutral-50/60">
                                            <td className="py-3">
                                                <p className="font-semibold text-neutral-900">
                                                    {spj.nomor_spj || 'Menunggu Penomoran'}
                                                </p>
                                                <p className="text-[11px] text-neutral-500">
                                                    {spj.kegiatan?.nama}
                                                </p>
                                            </td>
                                            <td className="py-3 font-semibold text-neutral-800">
                                                {formatRupiah(spj.nominal)}
                                            </td>
                                            <td className="py-3 text-neutral-600">
                                                {formatDate(spj.tanggal_pengajuan)}
                                            </td>
                                            <td className="py-3">
                                                <StatusBadge status={spj.status} />
                                            </td>
                                            <td className="py-3 text-right">
                                                <Link
                                                    href={`/spj/${spj.id}`}
                                                    className="font-semibold text-primary hover:text-primary-dark"
                                                >
                                                    Detail
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
