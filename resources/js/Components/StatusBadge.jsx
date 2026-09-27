import React from 'react';

const statusConfig = {
    draft: {
        label: 'Draft',
        bg: 'bg-neutral-100 text-neutral-700 border-neutral-300',
        dot: 'bg-neutral-400',
    },
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
