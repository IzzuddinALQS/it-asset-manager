import { Head, router } from '@inertiajs/react';
import { ArrowRight, Boxes, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

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
    admin: 'bg-blue-100 text-blue-800',
    technician: 'bg-emerald-100 text-emerald-800',
    viewer: 'bg-slate-100 text-slate-700',
};

export default function DevLogin({ users }: { users: DevUser[] }) {
    const [loadingId, setLoadingId] = useState<number | null>(null);

    const login = (id: number) => {
        setLoadingId(id);
        router.post(`/dev-login/${id}`, {}, { onFinish: () => setLoadingId(null) });
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
            <Head title="Masuk" />

            <div className="w-full max-w-3xl">
                <div className="mb-8 flex items-center gap-3">
                    <div className="bg-primary flex size-11 items-center justify-center rounded-xl text-sm font-bold text-white">
                        AM
                    </div>
                    <div>
                        <p className="text-lg font-semibold text-slate-900">IT Asset Manager</p>
                        <p className="text-muted-foreground text-xs">Internal IT Operations</p>
                    </div>
                </div>

                <Card className="shadow-sm">
                    <CardHeader>
                        <div className="text-muted-foreground flex items-center gap-2 text-xs font-semibold tracking-wider uppercase">
                            <ShieldCheck className="size-4" /> Mode pengembangan
                        </div>
                        <CardTitle className="mt-2 text-2xl">Pilih pengguna untuk masuk</CardTitle>
                        <CardDescription>
                            Halaman ini hanya aktif di lingkungan lokal. Login produksi akan memakai SSO HERD.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        {users.map((user) => (
                            <button
                                key={user.id}
                                type="button"
                                onClick={() => login(user.id)}
                                disabled={loadingId !== null}
                                className="border-border hover:border-primary/40 flex w-full items-center gap-4 rounded-lg border bg-white px-4 py-3 text-left transition-colors hover:bg-blue-50/40 disabled:opacity-60"
                            >
                                <span className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-full text-xs font-bold">
                                    {user.name
                                        .split(' ')
                                        .slice(0, 2)
                                        .map((part) => part[0])
                                        .join('')}
                                </span>
                                <span className="min-w-0 flex-1">
                                    <span className="flex flex-wrap items-center gap-2">
                                        <span className="truncate text-sm font-semibold text-slate-900">
                                            {user.name}
                                        </span>
                                        <Badge className={roleTone[user.role]}>{user.role_label}</Badge>
                                    </span>
                                    <span className="text-muted-foreground mt-0.5 block truncate text-xs">
                                        <span className="font-tag">{user.employee_number}</span>
                                        {user.job_title ? ` · ${user.job_title}` : ''}
                                        {user.department ? ` · ${user.department}` : ''}
                                    </span>
                                </span>
                                <ArrowRight className="size-4 shrink-0 text-slate-400" />
                            </button>
                        ))}

                        <div className="text-muted-foreground mt-4 flex items-start gap-2 rounded-lg bg-slate-50 p-3 text-xs">
                            <Boxes className="mt-0.5 size-4 shrink-0" />
                            Data karyawan bersumber dari HERD dan bersifat read-only. Role aplikasi diatur terpisah di
                            tabel pengguna.
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
