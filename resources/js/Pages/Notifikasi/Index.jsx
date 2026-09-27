import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDate } from '@/Utils/formatDate';
import { Bell, Check, AlertCircle, Info, AlertTriangle, ExternalLink } from 'lucide-react';
import axios from 'axios';

export default function Index({ notifications }) {
    const items = notifications?.data || [];

    const handleMarkAsRead = (item) => {
        axios.post(`/notifikasi/${item.id}/read`).then(() => {
            if (item.link) {
                router.visit(item.link);
            } else {
                router.reload();
            }
        });
    };

    const handleMarkAll = () => {
        axios.post('/notifikasi/read-all').then(() => {
            router.reload();
        });
    };

    const getIcon = (tipe) => {
        switch (tipe) {
            case 'warning':
                return <AlertTriangle className="w-5 h-5 text-amber-500 shrink-0" />;
            case 'action':
                return <AlertCircle className="w-5 h-5 text-blue-500 shrink-0" />;
            default:
                return <Info className="w-5 h-5 text-emerald-500 shrink-0" />;
        }
    };

    return (
        <AuthenticatedLayout title="Pusat Notifikasi">
            <Head title="Notifikasi" />

            <div className="max-w-4xl mx-auto space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-xl font-bold text-neutral-900">
                            Semua Notifikasi
                        </h2>
                        <p className="text-xs text-neutral-500 mt-1">
                            Pemberitahuan alur SPJ, status kegiatan, dan tindak lanjut aset
                        </p>
                    </div>

                    <button
                        onClick={handleMarkAll}
                        className="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-neutral-300 rounded-btn text-xs font-semibold text-neutral-700 hover:bg-neutral-50 shadow-sm transition-colors"
                    >
                        <Check className="w-4 h-4 text-emerald-600" />
                        Tandai Semua Dibaca
                    </button>
                </div>

                <div className="bg-surface rounded-card border border-neutral-200/80 shadow-sm divide-y divide-neutral-100 overflow-hidden">
                    {items.length === 0 ? (
                        <div className="p-8 text-center text-neutral-500 text-sm">
                            Tidak ada notifikasi yang tersimpan.
                        </div>
                    ) : (
                        items.map((item) => (
                            <div
                                key={item.id}
                                onClick={() => handleMarkAsRead(item)}
                                className={`p-4 flex items-start gap-4 cursor-pointer transition-colors ${
                                    item.is_read ? 'bg-white hover:bg-neutral-50/70' : 'bg-primary-50/30 hover:bg-primary-50/60'
                                }`}
                            >
                                <div className="mt-0.5">{getIcon(item.tipe)}</div>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between gap-2">
                                        <h4 className="text-sm font-semibold text-neutral-900">
                                            {item.judul}
                                        </h4>
                                        <span className="text-[11px] text-neutral-400">
                                            {formatDate(item.created_at)}
                                        </span>
                                    </div>
                                    <p className="text-xs text-neutral-600 mt-1">
                                        {item.pesan}
                                    </p>
                                </div>
                                {!item.is_read && (
                                    <span className="w-2 h-2 rounded-full bg-primary shrink-0 self-center" />
                                )}
                            </div>
                        ))
                    )}
                </div>

                {/* Pagination (UI-01) */}
                {notifications?.links && notifications.links.length > 3 && (
                    <div className="bg-surface p-4 rounded-card border border-neutral-200/80 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-neutral-600">
                        <div>
                            Menampilkan <strong>{notifications.from || 0}</strong> - <strong>{notifications.to || 0}</strong> dari <strong>{notifications.total || 0}</strong> notifikasi
                        </div>
                        <div className="flex items-center gap-1">
                            {notifications.links.map((link, idx) => (
                                <Link
                                    key={idx}
                                    href={link.url || '#'}
                                    preserveState
                                    preserveScroll
                                    className={`px-3 py-1.5 rounded-btn text-xs font-semibold transition-colors ${
                                        link.active
                                            ? 'bg-primary text-white'
                                            : link.url
                                            ? 'bg-white hover:bg-neutral-100 text-neutral-700 border border-neutral-200'
                                            : 'text-neutral-300 pointer-events-none'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
