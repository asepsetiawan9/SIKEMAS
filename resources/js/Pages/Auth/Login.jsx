import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { LogIn, KeyRound } from 'lucide-react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Masuk ke Sistem" />

            <div className="mb-5">
                <h2 className="text-lg font-bold text-neutral-900">
                    Masuk ke Akun Anda
                </h2>
                <p className="text-xs text-neutral-500 mt-0.5">
                    Gunakan kredensial resmi pegawai Kecamatan Caringin
                </p>
            </div>

            {status && (
                <div className="mb-4 p-3 rounded-lg bg-emerald-50 text-xs font-semibold text-emerald-700 border border-emerald-200">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <InputLabel htmlFor="email" value="Alamat Email" />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full text-sm"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="contoh@sikemas.test"
                    />
                    <InputError message={errors.email} className="mt-1.5 text-xs text-rose-600" />
                </div>

                <div>
                    <InputLabel htmlFor="password" value="Kata Sandi" />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full text-sm"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password} className="mt-1.5 text-xs text-rose-600" />
                </div>

                <div className="flex items-center justify-between pt-1">
                    <label className="flex items-center cursor-pointer">
                        <Checkbox
                            name="remember"
                            checked={data.remember}
                            onChange={(e) => setData('remember', e.target.checked)}
                        />
                        <span className="ms-2 text-xs text-neutral-600">
                            Ingat saya di perangkat ini
                        </span>
                    </label>

                    {canResetPassword && (
                        <Link
                            href={route('password.request')}
                            className="text-xs text-primary hover:text-primary-dark underline"
                        >
                            Lupa kata sandi?
                        </Link>
                    )}
                </div>

                <div className="pt-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-primary hover:bg-primary-dark text-white rounded-btn text-sm font-bold shadow-md transition-colors disabled:opacity-60 cursor-pointer"
                    >
                        <LogIn className="w-4 h-4" />
                        {processing ? 'Memverifikasi...' : 'Masuk ke Sistem'}
                    </button>
                </div>
            </form>
        </GuestLayout>
    );
}
