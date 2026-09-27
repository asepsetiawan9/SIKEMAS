import React, { useState, useEffect, useRef } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    Bell,
    Menu,
    User,
    LogOut,
    Check,
    ExternalLink,
    AlertCircle,
    Info,
    AlertTriangle,
} from 'lucide-react';
import axios from 'axios';

export default function Topbar({ setMobileOpen, pageTitle = 'Dashboard' }) {
    const { auth, notif_count } = usePage().props;
    const user = auth?.user;

    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const [notifOpen, setNotifOpen] = useState(false);
    const [notifications, setNotifications] = useState([]);
    const [loadingNotifs, setLoadingNotifs] = useState(false);

    const userMenuRef = useRef(null);
    const notifMenuRef = useRef(null);

    // Format human-readable role badge
    const getRoleBadge = (role, seksi) => {
        switch (role) {
            case 'staf_umum':
                return { text: 'Kasubag Umum', color: 'bg-slate-100 text-slate-700 border-slate-300' };
            case 'staf_keuangan':
                return { text: 'Staf Keuangan', color: 'bg-blue-100 text-blue-700 border-blue-300' };
            case 'kasi':
                const seksiName = seksi ? seksi.charAt(0).toUpperCase() + seksi.slice(1) : '';
                return { text: `Kasi ${seksiName}`, color: 'bg-emerald-100 text-emerald-700 border-emerald-300' };
            case 'sekmat':
                return { text: 'Sekretaris Kecamatan', color: 'bg-purple-100 text-purple-700 border-purple-300' };
            case 'camat':
                return { text: 'Camat Caringin', color: 'bg-amber-100 text-amber-700 border-amber-300' };
            default:
                return { text: role || 'Pengguna', color: 'bg-neutral-100 text-neutral-700 border-neutral-300' };
        }
    };

    const roleBadge = getRoleBadge(user?.role, user?.seksi);

    // Close dropdowns on outside click
    useEffect(() => {
        function handleClickOutside(event) {
            if (userMenuRef.current && !userMenuRef.current.contains(event.target)) {
                setUserMenuOpen(false);
            }
            if (notifMenuRef.current && !notifMenuRef.current.contains(event.target)) {
                setNotifOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Load recent notifications when dropdown opens
    const handleToggleNotif = () => {
        const nextState = !notifOpen;
        setNotifOpen(nextState);
        if (nextState) {
            setLoadingNotifs(true);
            axios
                .get('/notifikasi/recent')
                .then((res) => {
                    setNotifications(res.data.items || []);
                })
                .catch(() => {})
                .finally(() => setLoadingNotifs(false));
        }
    };

    // Mark single notification read & visit link
    const handleNotificationClick = (item) => {
        axios.post(`/notifikasi/${item.id}/read`).then(() => {
            setNotifOpen(false);
            if (item.link) {
                router.visit(item.link);
            } else {
                router.reload();
            }
        });
    };

    // Mark all notifications read
    const handleMarkAllRead = () => {
        axios.post('/notifikasi/read-all').then(() => {
            setNotifications([]);
            setNotifOpen(false);
            router.reload();
        });
    };

    const getNotifIcon = (tipe) => {
        switch (tipe) {
            case 'warning':
                return <AlertTriangle className="w-4 h-4 text-amber-500 shrink-0" />;
            case 'action':
                return <AlertCircle className="w-4 h-4 text-blue-500 shrink-0" />;
            default:
                return <Info className="w-4 h-4 text-emerald-500 shrink-0" />;
        }
    };

    return (
        <header className="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur border-b border-neutral-200 shadow-sm flex items-center justify-between px-4 sm:px-6">
            {/* Left: Mobile hamburger + Page Title */}
            <div className="flex items-center gap-3">
                <button
                    onClick={() => setMobileOpen(true)}
                    className="p-2 rounded-lg text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 lg:hidden focus:outline-none"
                    aria-label="Buka Menu"
                >
                    <Menu className="w-5 h-5" />
                </button>

                <div>
                    <h1 className="text-lg font-bold text-neutral-900 tracking-tight">
                        {pageTitle}
                    </h1>
                </div>
            </div>

            {/* Right: Notifications & User profile */}
            <div className="flex items-center gap-3 sm:gap-4">
                {/* Notification Bell */}
                <div className="relative" ref={notifMenuRef}>
                    <button
                        onClick={handleToggleNotif}
                        className="relative p-2 rounded-full text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100 transition-colors focus:outline-none"
                        aria-label="Notifikasi"
                    >
                        <Bell className="w-5 h-5" />
                        {notif_count > 0 && (
                            <span className="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow">
                                {notif_count > 99 ? '99+' : notif_count}
                            </span>
                        )}
                    </button>

                    {/* Notification Dropdown */}
                    {notifOpen && (
                        <div className="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-lg border border-neutral-200 overflow-hidden z-50 animate-in fade-in duration-150">
                            <div className="p-3 bg-neutral-50 border-b border-neutral-200 flex items-center justify-between">
                                <span className="font-semibold text-sm text-neutral-800">
                                    Notifikasi
                                </span>
                                {notifications.length > 0 && (
                                    <button
                                        onClick={handleMarkAllRead}
                                        className="text-xs text-primary hover:text-primary-dark font-medium flex items-center gap-1"
                                    >
                                        <Check className="w-3.5 h-3.5" /> Tandai Semua Dibaca
                                    </button>
                                )}
                            </div>

                            <div className="max-h-80 overflow-y-auto divide-y divide-neutral-100">
                                {loadingNotifs ? (
                                    <div className="p-4 text-center text-xs text-neutral-500">
                                        Memuat notifikasi...
                                    </div>
                                ) : notifications.length === 0 ? (
                                    <div className="p-6 text-center text-xs text-neutral-500">
                                        Tidak ada notifikasi baru
                                    </div>
                                ) : (
                                    notifications.map((item) => (
                                        <div
                                            key={item.id}
                                            onClick={() => handleNotificationClick(item)}
                                            className="p-3 hover:bg-neutral-50 cursor-pointer transition-colors flex items-start gap-2.5"
                                        >
                                            <div className="mt-0.5">{getNotifIcon(item.tipe)}</div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-xs font-semibold text-neutral-800">
                                                    {item.judul}
                                                </p>
                                                <p className="text-xs text-neutral-600 line-clamp-2 mt-0.5">
                                                    {item.pesan}
                                                </p>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            <div className="p-2.5 bg-neutral-50 border-t border-neutral-200 text-center">
                                <Link
                                    href="/notifikasi"
                                    onClick={() => setNotifOpen(false)}
                                    className="text-xs font-semibold text-primary hover:text-primary-dark inline-flex items-center gap-1"
                                >
                                    Lihat Semua Notifikasi <ExternalLink className="w-3 h-3" />
                                </Link>
                            </div>
                        </div>
                    )}
                </div>

                {/* User Dropdown */}
                <div className="relative" ref={userMenuRef}>
                    <button
                        onClick={() => setUserMenuOpen(!userMenuOpen)}
                        className="flex items-center gap-3 p-1.5 rounded-lg hover:bg-neutral-100 transition-colors focus:outline-none"
                    >
                        <div className="w-8 h-8 rounded-full bg-primary-light border border-primary/20 text-primary flex items-center justify-center font-bold text-sm">
                            {user?.name ? user.name.charAt(0).toUpperCase() : 'U'}
                        </div>
                        <div className="hidden md:flex flex-col items-start text-left">
                            <span className="text-xs font-semibold text-neutral-800 leading-tight">
                                {user?.name}
                            </span>
                            <span
                                className={`text-[10px] font-medium px-1.5 py-0.2 rounded border mt-0.5 ${roleBadge.color}`}
                            >
                                {roleBadge.text}
                            </span>
                        </div>
                    </button>

                    {/* User Menu */}
                    {userMenuOpen && (
                        <div className="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-neutral-200 py-1.5 z-50 animate-in fade-in duration-150">
                            <div className="px-4 py-2 border-b border-neutral-100 md:hidden">
                                <p className="text-xs font-semibold text-neutral-800">{user?.name}</p>
                                <p className="text-[10px] text-neutral-500 mt-0.5">{roleBadge.text}</p>
                            </div>

                            <Link
                                href="/profile"
                                onClick={() => setUserMenuOpen(false)}
                                className="flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-neutral-700 hover:bg-neutral-50 hover:text-primary transition-colors"
                            >
                                <User className="w-4 h-4 text-neutral-500" />
                                Profil Saya
                            </Link>

                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="w-full flex items-center gap-2.5 px-4 py-2 text-xs font-medium text-rose-600 hover:bg-rose-50 transition-colors text-left"
                            >
                                <LogOut className="w-4 h-4 text-rose-500" />
                                Keluar Sistem
                            </Link>
                        </div>
                    )}
                </div>
            </div>
        </header>
    );
}
