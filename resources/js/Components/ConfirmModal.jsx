import React, { Fragment } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { AlertTriangle, CheckCircle, Info, X } from 'lucide-react';

export default function ConfirmModal({
    isOpen,
    onClose,
    onConfirm,
    title = 'Konfirmasi Tindakan',
    message = 'Apakah Anda yakin ingin melanjutkan tindakan ini?',
    confirmText = 'Konfirmasi',
    cancelText = 'Batal',
    variant = 'primary', // 'primary' | 'danger' | 'success' | 'warning'
    isLoading = false,
    children,
}) {
    const getVariantStyles = () => {
        switch (variant) {
            case 'danger':
                return {
                    icon: AlertTriangle,
                    iconBg: 'bg-rose-100 text-rose-600',
                    confirmBtn: 'bg-rose-600 hover:bg-rose-700 text-white focus:ring-rose-500',
                };
            case 'success':
                return {
                    icon: CheckCircle,
                    iconBg: 'bg-emerald-100 text-emerald-600',
                    confirmBtn: 'bg-emerald-600 hover:bg-emerald-700 text-white focus:ring-emerald-500',
                };
            case 'warning':
                return {
                    icon: AlertTriangle,
                    iconBg: 'bg-amber-100 text-amber-600',
                    confirmBtn: 'bg-amber-600 hover:bg-amber-700 text-white focus:ring-amber-500',
                };
            default:
                return {
                    icon: Info,
                    iconBg: 'bg-primary/10 text-primary',
                    confirmBtn: 'bg-primary hover:bg-primary-dark text-white focus:ring-primary',
                };
        }
    };

    const variantStyles = getVariantStyles();
    const Icon = variantStyles.icon;

    return (
        <Transition appear show={isOpen} as={Fragment}>
            <Dialog as="div" className="relative z-50" onClose={isLoading ? () => {} : onClose}>
                <Transition.Child
                    as={Fragment}
                    enter="ease-out duration-200"
                    enterFrom="opacity-0"
                    enterTo="opacity-100"
                    leave="ease-in duration-150"
                    leaveFrom="opacity-100"
                    leaveTo="opacity-0"
                >
                    <div className="fixed inset-0 bg-neutral-900/60 backdrop-blur-xs transition-opacity" />
                </Transition.Child>

                <div className="fixed inset-0 z-10 overflow-y-auto">
                    <div className="flex min-h-full items-center justify-center p-4 text-center">
                        <Transition.Child
                            as={Fragment}
                            enter="ease-out duration-200"
                            enterFrom="opacity-0 scale-95"
                            enterTo="opacity-100 scale-100"
                            leave="ease-in duration-150"
                            leaveFrom="opacity-100 scale-100"
                            leaveTo="opacity-0 scale-95"
                        >
                            <Dialog.Panel className="w-full max-w-lg transform overflow-hidden rounded-modal bg-surface p-6 text-left align-middle shadow-xl transition-all border border-neutral-200/80">
                                <div className="flex items-start gap-4">
                                    <div className={`p-3 rounded-xl shrink-0 ${variantStyles.iconBg}`}>
                                        <Icon className="w-6 h-6" />
                                    </div>

                                    <div className="flex-1 min-w-0">
                                        <div className="flex justify-between items-center">
                                            <Dialog.Title as="h3" className="text-base font-bold text-neutral-900 leading-6">
                                                {title}
                                            </Dialog.Title>
                                            {!isLoading && (
                                                <button
                                                    onClick={onClose}
                                                    className="text-neutral-400 hover:text-neutral-600 rounded-lg p-1 transition-colors"
                                                >
                                                    <X className="w-4 h-4" />
                                                </button>
                                            )}
                                        </div>

                                        <p className="text-xs text-neutral-600 mt-2 leading-relaxed">
                                            {message}
                                        </p>

                                        {children && (
                                            <div className="mt-4">
                                                {children}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="mt-6 flex justify-end gap-3 pt-4 border-t border-neutral-100">
                                    <button
                                        type="button"
                                        disabled={isLoading}
                                        onClick={onClose}
                                        className="px-4 py-2 text-xs font-semibold text-neutral-700 bg-neutral-100 hover:bg-neutral-200 rounded-btn transition-colors disabled:opacity-50"
                                    >
                                        {cancelText}
                                    </button>
                                    <button
                                        type="button"
                                        disabled={isLoading}
                                        onClick={onConfirm}
                                        className={`px-4 py-2 text-xs font-semibold rounded-btn shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 ${variantStyles.confirmBtn}`}
                                    >
                                        {isLoading ? 'Memproses...' : confirmText}
                                    </button>
                                </div>
                            </Dialog.Panel>
                        </Transition.Child>
                    </div>
                </div>
            </Dialog>
        </Transition>
    );
}
