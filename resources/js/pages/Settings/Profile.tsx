import { Head, Link, usePage } from '@inertiajs/react';
import { HardDrive, KeyRound, Package } from 'lucide-react';
import DateDisplay from '@/components/DateDisplay';
import PageHeader from '@/components/PageHeader';
import StatCard from '@/components/StatCard';
import AppLayout from '@/layouts/AppLayout';
import type { PageProps } from '@/types';

interface Profile {
    role_label: string;
    last_login_at: string | null;
    employee_id: number | null;
    job_title: string | null;
    synced_at: string | null;
    holdings: { assets: number; accessories: number; licenses: number };
}

export default function SettingsProfile({ profile }: { profile: Profile }) {
    const employee = usePage<PageProps>().props.auth.user?.employee ?? null;

    const details: [string, string | null | undefined][] = [
        ['Nama', employee?.name],
        ['NIK', employee?.employee_number],
        ['Email', employee?.email],
        ['Jabatan', profile.job_title],
        ['Departemen', employee?.department?.name],
        ['Lokasi', employee?.location?.name],
        ['Peran', profile.role_label],
    ];

    return (
        <AppLayout>
            <Head title="Profil" />

            <PageHeader
                title="Profil saya"
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Profil' }]}
            />

            <div className="grid gap-6 lg:grid-cols-[1fr_1fr]">
                <section className="border-border bg-card rounded-xl border p-5">
                    <h2 className="text-[15px] font-semibold text-zinc-950">Akun saya</h2>
                    <p className="text-muted-foreground mt-1 text-xs">
                        Data pribadi disinkronkan dari HERD dan tidak bisa diubah di sini. Peran diatur oleh
                        administrator.
                    </p>
                    <dl className="mt-4 space-y-3 text-sm">
                        {details.map(([label, value]) => (
                            <div key={label} className="flex justify-between gap-4">
                                <dt className="text-muted-foreground">{label}</dt>
                                <dd className="text-right font-medium text-zinc-900">{value || '—'}</dd>
                            </div>
                        ))}
                        <div className="flex justify-between gap-4">
                            <dt className="text-muted-foreground">Login terakhir</dt>
                            <dd className="font-medium text-zinc-900">
                                <DateDisplay value={profile.last_login_at} withTime />
                            </dd>
                        </div>
                        <div className="flex justify-between gap-4">
                            <dt className="text-muted-foreground">Sinkron HERD</dt>
                            <dd className="font-medium text-zinc-900">
                                <DateDisplay value={profile.synced_at} withTime />
                            </dd>
                        </div>
                    </dl>
                </section>

                <section>
                    <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                        <StatCard label="Inventaris dipegang" value={profile.holdings.assets} icon={HardDrive} />
                        <StatCard label="Aksesori" value={profile.holdings.accessories} icon={Package} />
                        <StatCard label="Kuota lisensi" value={profile.holdings.licenses} icon={KeyRound} />
                    </div>
                    {profile.employee_id !== null && (
                        <Link
                            href={`/directory/${profile.employee_id}`}
                            className="text-primary mt-3 inline-block text-sm font-medium hover:underline"
                        >
                            Lihat rincian di direktori →
                        </Link>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
