import React from 'react';
import { FolderOpen } from 'lucide-react';

export default function EmptyState({
    icon: Icon = FolderOpen,
    title = 'Tidak Ada Data',
    description = 'Belum ada data yang tersedia pada kriteria atau halaman ini.',
    action = null,
}) {
    return (
        <div className="flex flex-col items-center justify-center p-8 sm:p-12 text-center rounded-xl bg-neutral-50/50 border border-dashed border-neutral-200">
            <div className="w-14 h-14 rounded-2xl bg-primary/10 text-primary flex items-center justify-center mb-3.5 shadow-xs">
                <Icon className="w-7 h-7" />
            </div>
            <h3 className="text-sm font-bold text-neutral-800 tracking-tight">
                {title}
            </h3>
            <p className="text-xs text-neutral-500 max-w-sm mt-1.5 leading-relaxed">
                {description}
            </p>
            {action && (
                <div className="mt-5">
                    {action}
                </div>
            )}
        </div>
    );
}
