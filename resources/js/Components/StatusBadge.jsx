import React from 'react';

const statusConfig = {
    // V2 Status Verifikasi
    draft: {
        label: 'Draft',
        bg: 'bg-neutral-100 text-neutral-700 border-neutral-300',
        dot: 'bg-neutral-400',
    },
    diajukan: {
        label: 'Diajukan (Sekmat)',
        bg: 'bg-amber-50 text-amber-800 border-amber-300',
        dot: 'bg-amber-500',
    },
    diverifikasi_sekmat: {
        label: 'Diverifikasi Sekmat',
        bg: 'bg-sky-50 text-sky-800 border-sky-300',
        dot: 'bg-sky-500',
    },
    dikembalikan_sekmat: {
        label: 'Dikembalikan Sekmat',
        bg: 'bg-rose-50 text-rose-800 border-rose-300',
        dot: 'bg-rose-500',
    },
    disetujui_camat: {
        label: 'Disetujui Camat',
        bg: 'bg-emerald-50 text-emerald-800 border-emerald-300',
        dot: 'bg-emerald-500',
    },
    dikembalikan_camat: {
        label: 'Dikembalikan Camat',
        bg: 'bg-rose-50 text-rose-800 border-rose-300',
        dot: 'bg-rose-500',
    },

    // V2 Status Dokumen Bukti
    belum_lengkap: {
        label: 'Belum Lengkap',
        bg: 'bg-amber-50 text-amber-800 border-amber-300',
        dot: 'bg-amber-500',
    },
    lengkap: {
        label: 'Lengkap',
        bg: 'bg-emerald-50 text-emerald-800 border-emerald-300',
        dot: 'bg-emerald-500',
    },

    // Legacy V1 Status
    diajukan_kasi: {
        label: 'Diajukan Kasi',
        bg: 'bg-blue-50 text-blue-700 border-blue-200',
        dot: 'bg-blue-500',
    },
    dikonsolidasi: {
        label: 'Dikonsolidasi',
        bg: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        dot: 'bg-indigo-500',
    },
    diajukan_verifikasi: {
        label: 'Menunggu Verifikasi',
        bg: 'bg-amber-50 text-amber-700 border-amber-200',
        dot: 'bg-amber-500',
    },
    diverifikasi: {
        label: 'Diverifikasi',
        bg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        dot: 'bg-emerald-500',
    },
    ditolak: {
        label: 'Ditolak',
        bg: 'bg-rose-50 text-rose-700 border-rose-200',
        dot: 'bg-rose-500',
    },
};

export default function StatusBadge({ status, className = '' }) {
    const config = statusConfig[status] || {
        label: status || 'Unknown',
        bg: 'bg-neutral-100 text-neutral-600 border-neutral-200',
        dot: 'bg-neutral-400',
    };

    return (
        <span
            className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full border shadow-sm ${config.bg} ${className}`}
        >
            <span className={`w-1.5 h-1.5 rounded-full ${config.dot}`} />
            {config.label}
        </span>
    );
}
