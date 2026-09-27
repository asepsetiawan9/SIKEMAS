import React from 'react';
import { Download, QrCode } from 'lucide-react';

export default function QrDownloadButton({ aset, className = '', label = 'Unduh QR Code (PNG)', variant = 'primary' }) {
    const handleDownload = () => {
        if (!aset?.id) return;
        window.location.href = `/aset/${aset.id}/qr`;
    };

    const variantStyles = variant === 'primary'
        ? 'bg-primary text-white hover:bg-primary-dark shadow-sm'
        : 'bg-white text-neutral-700 border border-neutral-200 hover:bg-neutral-50 shadow-sm';

    return (
        <button
            type="button"
            onClick={handleDownload}
            className={`inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-btn text-xs font-semibold transition-all duration-150 active:scale-[0.98] ${variantStyles} ${className}`}
            title={`Unduh QR Code untuk ${aset?.kode_barang || 'Aset'}`}
        >
            <QrCode className="w-4 h-4" />
            <Download className="w-3.5 h-3.5 opacity-70" />
            <span>{label}</span>
        </button>
    );
}
