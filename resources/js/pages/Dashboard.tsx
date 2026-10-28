import { Head } from '@inertiajs/react';
import { ArrowRight, HardDrive, ScrollText, Wrench } from 'lucide-react';
import AppLayout from '@/layouts/AppLayout';

const lifecycle = [
    { step: '01', title: 'Pengadaan', status: 'Pending', tone: 'purple' as const },
    { step: '02', title: 'Penerimaan & Tagging', status: 'Pending', tone: 'purple' as const },
    { step: '03', title: 'Siap Pakai', status: 'Ready', tone: 'green' as const },
    { step: '04', title: 'Dipakaikan', status: 'Deployed', tone: 'blue' as const },
    { step: '05', title: 'Dikembalikan', status: 'Ready / Repair', tone: 'green' as const },
];

export default function Dashboard() {
    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-6">
                <p className="text-muted-foreground text-[11px] font-semibold tracking-[0.18em] uppercase">
                    Alur Perputaran Aset
                </p>
                <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    Dari pengadaan sampai pemusnahan
                </h1>
                <p className="text-muted-foreground mt-2 max-w-2xl text-sm">
                    Setiap aset selalu berada di satu status. Perpindahan status tercatat di riwayat aset beserta siapa
                    yang melakukannya.
                </p>
            </div>

            <div className="grid gap-3 md:grid-cols-3 lg:grid-cols-5">
                {lifecycle.map((item, index) => (
                    <div key={item.step} className="relative">
                        <div
                            className={
                                'bg-card h-full rounded-xl border p-4 shadow-sm ' +
                                (index === 2 ? 'border-emerald-500' : index === 3 ? 'border-primary' : 'border-border')
                            }
                        >
                            <p className="font-tag text-muted-foreground text-[10px]">{item.step}</p>
                            <p className="mt-2 text-sm font-semibold text-slate-900">{item.title}</p>
                            <span
                                className={
                                    'mt-3 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold ' +
                                    (item.tone === 'purple'
                                        ? 'bg-purple-100 text-purple-800'
                                        : item.tone === 'green'
                                          ? 'bg-emerald-100 text-emerald-800'
                                          : 'bg-blue-100 text-blue-800')
                                }
                            >
                                {item.status}
                            </span>
                        </div>
                        {index < lifecycle.length - 1 && (
                            <ArrowRight className="absolute top-1/2 -right-2.5 hidden size-4 -translate-y-1/2 text-slate-300 lg:block" />
                        )}
                    </div>
                ))}
            </div>

            <div className="mt-6 grid gap-4 md:grid-cols-3">
                {[
                    { label: 'Aset terdaftar', value: '—', icon: HardDrive },
                    { label: 'Lisensi aktif', value: '—', icon: ScrollText },
                    { label: 'Tiket maintenance', value: '—', icon: Wrench },
                ].map((stat) => (
                    <div key={stat.label} className="border-border bg-card rounded-xl border p-5 shadow-sm">
                        <div className="flex items-center justify-between">
                            <p className="text-muted-foreground text-xs font-medium">{stat.label}</p>
                            <stat.icon className="size-4 text-slate-400" />
                        </div>
                        <p className="mt-3 text-3xl font-bold tracking-tight text-slate-900">{stat.value}</p>
                        <p className="text-muted-foreground mt-1 text-[11px]">Akan terisi setelah modul aktif</p>
                    </div>
                ))}
            </div>

            <div className="border-border mt-6 rounded-xl border border-dashed bg-white p-6">
                <p className="text-sm font-semibold text-slate-800">Fondasi siap</p>
                <p className="text-muted-foreground mt-1 text-sm">
                    Fase 0 selesai: Laravel 13, Inertia + React (TypeScript), Tailwind, PostgreSQL, dan layout aplikasi.
                    Modul aset akan dibangun pada fase berikutnya.
                </p>
            </div>
        </AppLayout>
    );
}
