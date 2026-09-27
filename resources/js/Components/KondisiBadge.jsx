import React from 'react';

const kondisiConfig = {
    baik: {
        label: 'Baik',
        bg: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        dot: 'bg-emerald-500',
    },
    rusak_ringan: {
        label: 'Rusak Ringan',
        bg: 'bg-amber-50 text-amber-700 border-amber-200',
        dot: 'bg-amber-500',
    },
    rusak_berat: {
        label: 'Rusak Berat',
        bg: 'bg-rose-50 text-rose-700 border-rose-200',
        dot: 'bg-rose-500',
    },
};

export default function KondisiBadge({ kondisi, className = '' }) {
    const config = kondisiConfig[kondisi] || {
        label: kondisi || 'Unknown',
        bg: 'bg-neutral-100 text-neutral-600 border-neutral-200',
        dot: 'bg-neutral-400',
    };

    return (
        <span
            className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border shadow-sm ${config.bg} ${className}`}
        >
            <span className={`w-1.5 h-1.5 rounded-full ${config.dot}`} />
            {config.label}
        </span>
    );
}
