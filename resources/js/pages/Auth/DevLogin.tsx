import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ArrowRight, KeyRound, ShieldCheck, TriangleAlert } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import AppLogo from '@/components/AppLogo';
import AppWordmark from '@/components/AppWordmark';
import FormField from '@/components/FormField';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { PageProps } from '@/types';

interface DevUser {
    id: number;
    role: 'admin' | 'technician' | 'viewer';
    role_label: string;
    name: string;
    employee_number: string;
    job_title: string | null;
    department: string | null;
}

const roleTone: Record<DevUser['role'], string> = {
    admin: 'bg-indigo-50 text-indigo-700',
    technician: 'bg-emerald-50 text-emerald-700',
    viewer: 'bg-zinc-100 text-zinc-700',
};

/** NIK + password stored in this app, for when HERD is unreachable. */
function LocalLoginForm() {
    const { data, setData, post, processing, errors } = useForm({ employee_number: '', password: '', remember: false });

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        post('/login', { onFinish: () => setData('password', '') });
    };

    return (
        <form onSubmit={submit} className="space-y-4">
            <FormField label="NIK" htmlFor="employee_number" error={errors.employee_number}>
                <Input
                    id="employee_number"
                    autoComplete="username"
                    autoFocus
                    value={data.employee_number}
                    onChange={(event) => setData('employee_number', event.target.value)}
                />
            </FormField>
            <FormField label="Password" htmlFor="password" error={errors.password}>
                <Input
                    id="password"
                    type="password"
                    autoComplete="current-password"
                    value={data.password}
                    onChange={(event) => setData('password', event.target.value)}
                />
            </FormField>
            <label className="flex items-center gap-2 text-sm text-zinc-700">
                <input
                    type="checkbox"
                    className="accent-primary size-4 rounded"
                    checked={data.remember}
                    onChange={(event) => setData('remember', event.target.checked)}
                />
                Ingat saya
            </label>
            <Button type="submit" size="lg" className="w-full" loading={processing}>
                Masuk
            </Button>
        </form>
    );
}

export default function DevLogin({ users, ssoEnabled }: { users: DevUser[]; ssoEnabled: boolean }) {
    const [loadingId, setLoadingId] = useState<number | null>(null);
    const error = usePage<PageProps>().props.flash.error;

    const login = (id: number) => {
        setLoadingId(id);
        router.post(`/dev-login/${id}`, {}, { onFinish: () => setLoadingId(null) });
    };

    return (
        <div className="bg-background flex min-h-screen flex-col items-center px-4 py-12 sm:py-20">
            <Head title="Masuk" />

            <div className="w-full max-w-sm">
                <div className="mb-6 flex flex-col items-center text-center">
                    <AppLogo className="size-12" />
                    <AppWordmark className="mt-4 text-4xl text-zinc-950" tagClassName="bg-primary" />
                    <p className="text-muted-foreground mt-1 text-xs">IT Asset Manager · every asset knows its owner</p>
                    <h1 className="mt-6 text-xl font-semibold tracking-tight text-zinc-950">Masuk ke akun Anda</h1>
                </div>

                {error && (
                    <div
                        role="alert"
                        className="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800"
                    >
                        <TriangleAlert className="mt-0.5 size-4 shrink-0" />
                        {error}
                    </div>
                )}

                <div className="border-border bg-card rounded-xl border p-6 shadow-xs">
                    <LocalLoginForm />

                    <div className="text-muted-foreground my-5 flex items-center gap-3 text-xs">
                        <span className="bg-border h-px flex-1" /> atau
                        <span className="bg-border h-px flex-1" />
                    </div>

                    {ssoEnabled ? (
                        <a
                            href="/auth/redirect"
                            className="border-input inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border bg-white px-4 text-sm font-medium text-zinc-800 shadow-xs transition-colors hover:bg-zinc-50"
                        >
                            <KeyRound className="size-4" /> Lanjutkan dengan SSO HERD
                        </a>
                    ) : (
                        <p className="text-muted-foreground text-center text-sm">SSO HERD belum dikonfigurasi.</p>
                    )}
                </div>

                <p className="text-muted-foreground mt-6 text-center text-sm">Belum punya akses? Hubungi tim IT.</p>

                {users.length > 0 && (
                    <div className="mt-10 rounded-xl border border-dashed border-amber-300 bg-amber-50/40 p-4">
                        <div className="mb-3 flex items-center gap-2 text-[13px] font-semibold text-amber-800">
                            <ShieldCheck className="size-4" /> Mode pengembangan
                        </div>
                        <div className="space-y-2">
                            {users.map((user) => (
                                <button
                                    key={user.id}
                                    type="button"
                                    onClick={() => login(user.id)}
                                    disabled={loadingId !== null}
                                    className="flex w-full items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 text-left transition-colors hover:border-zinc-300 hover:bg-zinc-50 disabled:opacity-60"
                                >
                                    <span className="min-w-0 flex-1">
                                        <span className="flex items-center gap-2">
                                            <span className="truncate text-sm font-medium text-zinc-900">
                                                {user.name}
                                            </span>
                                            <Badge className={roleTone[user.role]}>{user.role_label}</Badge>
                                        </span>
                                        <span className="text-muted-foreground font-tag block truncate text-xs">
                                            {user.employee_number}
                                        </span>
                                    </span>
                                    <ArrowRight className="size-4 shrink-0 text-zinc-400" />
                                </button>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
