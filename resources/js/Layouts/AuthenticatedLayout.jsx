import React, { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { Toaster, toast } from 'sonner';
import Sidebar from '@/Components/Sidebar';
import Topbar from '@/Components/Topbar';

export default function AuthenticatedLayout({ title = 'Dashboard', children }) {
    const [collapsed, setCollapsed] = useState(false);
    const [mobileOpen, setMobileOpen] = useState(false);
    const { flash } = usePage().props;

    // Trigger Sonner toast when flash session changes
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.warning) {
            toast.warning(flash.warning);
        }
        if (flash?.info) {
            toast.info(flash.info);
        }
    }, [flash]);

    return (
        <div className="min-h-screen bg-neutral-100 flex flex-col font-sans">
            <Toaster position="top-right" richColors closeButton />

            {/* Role-tailored Sidebar */}
            <Sidebar
                collapsed={collapsed}
                setCollapsed={setCollapsed}
                mobileOpen={mobileOpen}
                setMobileOpen={setMobileOpen}
            />

            {/* Main content wrapper */}
            <div
                className={`flex-1 flex flex-col transition-all duration-300 ease-in-out ${
                    collapsed ? 'lg:pl-[72px]' : 'lg:pl-[260px]'
                }`}
            >
                {/* Topbar */}
                <Topbar
                    setMobileOpen={setMobileOpen}
                    pageTitle={title}
                />

                {/* Content Area max-width 1280px with 24px padding */}
                <main className="flex-1 w-full max-w-[1280px] mx-auto p-4 sm:p-6">
                    {children}
                </main>
            </div>
        </div>
    );
}
