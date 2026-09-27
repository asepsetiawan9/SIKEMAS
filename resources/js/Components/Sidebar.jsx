import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    ClipboardList,
    FileText,
    Package,
    FileSpreadsheet,
    TrendingUp,
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    Building2,
} from 'lucide-react';

export default function Sidebar({ collapsed, setCollapsed, mobileOpen, setMobileOpen }) {
    const { url } = usePage();
    const { auth, sidebar_badges } = usePage().props;
    const userRole = auth?.user?.role;

    // Helper to determine if link is active
    const isActive = (path) => {
        if (path === '/dashboard' && (url === '/dashboard' || url === '/')) return true;
        if (path !== '/dashboard' && url.startsWith(path)) return true;
        return false;
    };

    // Construct role-specific menu items per Bagian 9
    const getMenuItems = () => {
        switch (userRole) {
            case 'super_admin':
                return [
                    {
                        name: 'Dashboard',
                        href: '/dashboard',
                        icon: BarChart3,
                        badge: null,
                    },
                    {
                        name: 'Kegiatan Anggaran',
                        href: '/kegiatan',
                        icon: ClipboardList,
                        badge: null,
                    },
                    {
                        name: 'SPJ Digital',
                        href: '/spj',
                        icon: FileText,
                        badge: sidebar_badges?.spj_pending_konsolidasi > 0
                            ? sidebar_badges.spj_pending_konsolidasi
                            : null,
                        badgeColor: 'bg-amber-400 text-neutral-900',
                        subItems: [
                            {
                                name: 'Arsip & Rekap SPJ',
                                href: '/spj?tab=arsip',
                                icon: FileText,
                            },
                            {
                                name: 'Antrean Konsolidasi',
                                href: '/spj?tab=konsolidasi',
                                icon: FileText,
                            },
                            {
                                name: 'Antrean Verifikasi',
                                href: '/spj?tab=verifikasi',
                                icon: CheckCircle,
                            },
                        ],
                    },
                    {
                        name: 'Aset BMD',
                        href: '/aset',
                        icon: Package,
                        badge: null,
                        subItems: [
                            {
                                name: 'Daftar Aset',
                                href: '/aset',
                                icon: Package,
                            },
                            {
                                name: 'KIB / KIR',
                                href: '/aset?tab=kib_kir',
                                icon: FileSpreadsheet,
                            },
                        ],
                    },
                    {
                        name: 'Laporan & Rekap',
                        href: '/laporan',
                        icon: TrendingUp,
                        badge: null,
                    },
                ];

            case 'staf_umum':
                return [
                    {
                        name: 'Aset BMD',
                        href: '/aset',
                        icon: Package,
                        badge: null,
                    },
                ];

            case 'staf_keuangan':
                return [
                    {
                        name: 'Dashboard',
                        href: '/dashboard',
                        icon: BarChart3,
                        badge: null,
                    },
                    {
                        name: 'Kegiatan Anggaran',
                        href: '/kegiatan',
                        icon: ClipboardList,
                        badge: null,
                    },
                    {
                        name: 'SPJ Digital',
                        href: '/spj',
                        icon: FileText,
                        badge: sidebar_badges?.spj_pending_konsolidasi > 0
                            ? sidebar_badges.spj_pending_konsolidasi
                            : null,
                        badgeColor: 'bg-amber-400 text-neutral-900',
                    },
                    {
                        name: 'Aset BMD',
                        href: '/aset',
                        icon: Package,
                        badge: null,
                        subItems: [
                            {
                                name: 'KIB / KIR',
                                href: '/aset?tab=kib_kir',
                                icon: FileSpreadsheet,
                            },
                        ],
                    },
                    {
                        name: 'Laporan & Rekap',
                        href: '/laporan',
                        icon: TrendingUp,
                        badge: null,
                    },
                ];

            case 'kasi':
                return [
                    {
                        name: 'Dashboard',
                        href: '/dashboard',
                        icon: BarChart3,
                        badge: null,
                    },
                    {
                        name: 'Kegiatan Anggaran',
                        href: '/kegiatan',
                        icon: ClipboardList,
                        badge: null,
                    },
                    {
                        name: 'Pengajuan SPJ',
                        href: '/spj',
                        icon: FileText,
                        badge: sidebar_badges?.spj_ditolak_kasi > 0
                            ? sidebar_badges.spj_ditolak_kasi
                            : null,
                        badgeColor: 'bg-rose-500 text-white',
                    },
                ];

            case 'sekmat':
                return [
                    {
                        name: 'Dashboard',
                        href: '/dashboard',
                        icon: BarChart3,
                        badge: null,
                    },
                    {
                        name: 'Verifikasi SPJ',
                        href: '/spj',
                        icon: CheckCircle,
                        badge: sidebar_badges?.spj_pending_verifikasi > 0
                            ? sidebar_badges.spj_pending_verifikasi
                            : null,
                        badgeColor: 'bg-amber-400 text-neutral-900',
                    },
                    {
                        name: 'Kegiatan Anggaran',
                        href: '/kegiatan',
                        icon: ClipboardList,
                        badge: null,
                    },
                    {
                        name: 'Aset BMD',
                        href: '/aset',
                        icon: Package,
                        badge: null,
                    },
                    {
                        name: 'Laporan Eksekutif',
                        href: '/laporan',
                        icon: TrendingUp,
                        badge: null,
                    },
                ];

            case 'camat':
                return [
                    {
                        name: 'Dashboard Eksekutif',
                        href: '/dashboard',
                        icon: BarChart3,
                        badge: null,
                    },
                    {
                        name: 'Kegiatan Anggaran',
                        href: '/kegiatan',
                        icon: ClipboardList,
                        badge: null,
                    },
                    {
                        name: 'SPJ Digital',
                        href: '/spj?tab=arsip',
                        icon: FileText,
                        badge: sidebar_badges?.spj_pending_verifikasi > 0
                            ? sidebar_badges.spj_pending_verifikasi
                            : null,
                        badgeColor: 'bg-amber-400 text-neutral-900',
                        subItems: [
                            {
                                name: 'Arsip & Rekap SPJ',
                                href: '/spj?tab=arsip',
                                icon: FileText,
                            },
                            {
                                name: 'Antrean Verifikasi',
                                href: '/spj?tab=verifikasi',
                                icon: CheckCircle,
                            },
                            {
                                name: 'Monitoring Konsolidasi',
                                href: '/spj?tab=konsolidasi',
                                icon: FileText,
                            },
                        ],
                    },
                    {
                        name: 'Aset BMD',
                        href: '/aset',
                        icon: Package,
                        badge: null,
                        subItems: [
                            {
                                name: 'Daftar Aset',
                                href: '/aset',
                                icon: Package,
                            },
                            {
                                name: 'KIB / KIR',
                                href: '/aset?tab=kib_kir',
                                icon: FileSpreadsheet,
                            },
                        ],
                    },
                    {
                        name: 'Laporan Wilayah',
                        href: '/laporan',
                        icon: TrendingUp,
                        badge: null,
                    },
                ];

            default:
                return [
                    {
                        name: 'Dashboard',
                        href: '/dashboard',
                        icon: BarChart3,
                        badge: null,
                    },
                ];
        }
    };

    const menuItems = getMenuItems();

    return (
        <>
            {/* Mobile backdrop */}
            {mobileOpen && (
                <div
                    className="fixed inset-0 z-40 bg-neutral-900/60 backdrop-blur-sm lg:hidden transition-opacity"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            {/* Sidebar element */}
            <aside
                className={`fixed top-0 bottom-0 left-0 z-50 flex flex-col transition-all duration-300 ease-in-out bg-gradient-to-b from-[#1a5b94] via-[#164a78] to-[#0f3252] text-white shadow-xl ${
                    collapsed ? 'w-[72px]' : 'w-[260px]'
                } ${
                    mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
                }`}
            >
                {/* Header / Brand */}
                <div className="h-16 flex items-center justify-between px-4 border-b border-white/10">
                    <Link
                        href="/dashboard"
                        className="flex items-center gap-3 overflow-hidden focus:outline-none"
                    >
                        <div className="w-10 h-10 rounded-xl bg-white/10 p-1 flex items-center justify-center shrink-0 border border-white/20 shadow-inner overflow-hidden">
                            <img src="/logo.png" alt="Logo SIMPEL KAN" className="w-full h-full object-contain" />
                        </div>
                        {!collapsed && (
                            <div className="flex flex-col min-w-0">
                                <span className="font-bold text-lg tracking-wider text-white leading-tight">
                                    SIMPEL KAN
                                </span>
                                <span className="text-[11px] text-white/70 truncate">
                                    Kecamatan Caringin
                                </span>
                            </div>
                        )}
                    </Link>

                    {/* Desktop Collapse Toggle */}
                    <button
                        onClick={() => setCollapsed(!collapsed)}
                        className="hidden lg:flex w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 items-center justify-center text-white/80 hover:text-white transition-colors"
                        title={collapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'}
                    >
                        {collapsed ? (
                            <ChevronRight className="w-4 h-4" />
                        ) : (
                            <ChevronLeft className="w-4 h-4" />
                        )}
                    </button>
                </div>

                {/* Navigation Items */}
                <div className="flex-1 overflow-y-auto py-4 px-3 space-y-1.5 scrollbar-thin scrollbar-thumb-white/10">
                    {menuItems.map((item) => {
                        const active = isActive(item.href);
                        const Icon = item.icon;

                        return (
                            <div key={item.name} className="space-y-1">
                                <Link
                                    href={item.href}
                                    title={collapsed ? item.name : undefined}
                                    className={`group flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 relative ${
                                        active
                                            ? 'bg-white text-[#1a5b94] font-semibold shadow-md'
                                            : 'text-white/85 hover:bg-white/10 hover:text-white'
                                    }`}
                                >
                                    <Icon
                                        className={`w-5 h-5 shrink-0 transition-transform group-hover:scale-110 ${
                                            active ? 'text-[#1a5b94]' : 'text-white/80'
                                        }`}
                                    />

                                    {!collapsed && (
                                        <span className="truncate flex-1">
                                            {item.name}
                                        </span>
                                    )}

                                    {/* Badge count */}
                                    {item.badge !== null && item.badge !== undefined && (
                                        <span
                                            className={`${
                                                item.badgeColor || 'bg-amber-400 text-neutral-900'
                                            } ${
                                                collapsed
                                                    ? 'absolute top-1.5 right-1.5 w-4 h-4 text-[10px] flex items-center justify-center rounded-full font-bold shadow'
                                                    : 'ml-auto px-2 py-0.5 text-xs rounded-full font-bold shadow-sm'
                                            }`}
                                        >
                                            {item.badge}
                                        </span>
                                    )}
                                </Link>

                                {/* Sub items (e.g. KIB/KIR under Aset) */}
                                {!collapsed && item.subItems && item.subItems.length > 0 && (
                                    <div className="ml-7 pl-3 border-l border-white/15 space-y-1 mt-1">
                                        {item.subItems.map((sub) => {
                                            const subActive = url === sub.href;
                                            const SubIcon = sub.icon;
                                            return (
                                                <Link
                                                    key={sub.name}
                                                    href={sub.href}
                                                    className={`flex items-center gap-2 px-2.5 py-1.5 rounded-md text-xs font-medium transition-colors ${
                                                        subActive
                                                            ? 'bg-white/20 text-white'
                                                            : 'text-white/70 hover:bg-white/10 hover:text-white'
                                                    }`}
                                                >
                                                    <SubIcon className="w-3.5 h-3.5" />
                                                    <span>{sub.name}</span>
                                                </Link>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        );
                    })}
                </div>

                {/* Footer Info / Tahun Anggaran */}
                <div className="p-3 border-t border-white/10 bg-black/10">
                    {!collapsed ? (
                        <div className="flex items-center justify-between text-xs text-white/75 px-1">
                            <span>Tahun Anggaran:</span>
                            <span className="px-2 py-0.5 rounded bg-white/15 font-semibold text-white">
                                2026
                            </span>
                        </div>
                    ) : (
                        <div className="text-center text-[10px] font-bold text-white/75">
                            2026
                        </div>
                    )}
                </div>
            </aside>
        </>
    );
}
