import React from 'react';

export default function ApplicationLogo({ className = 'w-10 h-10', imgClassName = 'w-full h-full' }) {
    return (
        <div className={`flex items-center justify-center shrink-0 overflow-hidden ${className}`}>
            <img
                src="/logo.png"
                alt="Logo SIMPEL KAN Kecamatan Caringin"
                className={`object-contain max-h-full max-w-full drop-shadow-sm ${imgClassName}`}
            />
        </div>
    );
}
