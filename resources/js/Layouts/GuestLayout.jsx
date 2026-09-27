import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-neutral-100 via-slate-50 to-primary-light/30 px-4 py-8">
            <div className="mb-6 flex flex-col items-center text-center">
                <Link href="/" className="flex items-center gap-3">
                    <ApplicationLogo className="h-14 w-14" iconClassName="w-8 h-8" />
                </Link>
                <h1 className="mt-3 text-2xl font-black tracking-wider text-neutral-900">
                    SIKEMAS
                </h1>
                <p className="text-xs font-medium text-neutral-500 max-w-sm mt-0.5">
                    Sistem Informasi Keuangan & Aset Terintegrasi<br />
                    Kecamatan Caringin
                </p>
            </div>

            <div className="w-full max-w-md overflow-hidden bg-surface p-6 sm:p-8 rounded-card border border-neutral-200/80 shadow-lg">
                {children}
            </div>

            <div className="mt-6 text-center text-xs text-neutral-400">
                &copy; 2026 Pemerintah Kecamatan Caringin. Hak Cipta Dilindungi.
            </div>
        </div>
    );
}
