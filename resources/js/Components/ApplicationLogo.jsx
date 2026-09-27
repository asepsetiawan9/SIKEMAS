import React from 'react';
import { Building2 } from 'lucide-react';

export default function ApplicationLogo({ className = 'w-10 h-10', iconClassName = 'w-6 h-6' }) {
    return (
        <div className={`rounded-2xl bg-gradient-to-tr from-primary to-primary-light flex items-center justify-center text-white shadow-md border border-primary/20 ${className}`}>
            <Building2 className={`text-white ${iconClassName}`} />
        </div>
    );
}
