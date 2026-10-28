import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    BarChart3,
    Bell,
    CalendarClock,
    CheckCircle2,
    HardDrive,
    KeyRound,
    Laptop,
    Wallet,
    Wrench,
} from 'lucide-react';
import type { ReactNode } from 'react';
import AlertRow from '@/components/alerts/AlertRow';
import DistributionBars, { type DistributionItem } from '@/components/dashboard/DistributionBars';
import DateDisplay from '@/components/DateDisplay';
import StatCard from '@/components/StatCard';
import AppLayout from '@/layouts/AppLayout';
import { cn, formatCurrency } from '@/lib/utils';
import type { AlertItem } from '@/types/alerts';

interface DashboardProps {
    kpis: {
        total: number;
        retired: number;
        deployed: number;
        ready: number;
        maintenance: number;
        acquisition_value: string;
    };
    statusDistribution: DistributionItem[];
    categoryDistribution: DistributionItem[];
    expiringLicenses: {
        id: number;
        name: string;
        expires_at: string;
        days_left: number;
        seats_used: number;
        seats_total: number;
    }[];
    maintenanceDue: { id: number; name: string; scope: string; next_due_date: string; days_left: number }[];
    overdueTickets: number;
    recentAlerts: AlertItem[];
}

const statusColors: Record<string, string> = {
    pending: 'bg-purple-400',
    ready: 'bg-emerald-500',
    deployed: 'bg-blue-500',
    maintenance: 'bg-amber-500',
    retired: 'bg-slate-400',
};

export default function Dashboard({
    kpis,
    statusDistribution,
    categoryDistribution,
    expiringLicenses,
    maintenanceDue,
    overdueTickets,
    recentAlerts,
}: DashboardProps) {
    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Dashboard</h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Ringkasan aset, lisensi, maintenance, dan peringatan.
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <StatCard
                    label="Total aset"
                    value={kpis.total}
                    icon={HardDrive}
                    hint={kpis.retired > 0 ? `Tidak termasuk ${kpis.retired} dipensiunkan` : undefined}
                />
                <StatCard label="Dipakaikan" value={kpis.deployed} icon={Laptop} tone="info" />
                <StatCard label="Siap pakai" value={kpis.ready} icon={CheckCircle2} tone="success" />
                <StatCard label="Perbaikan" value={kpis.maintenance} icon={Wrench} tone="warning" />
                <StatCard
                    label="Nilai perolehan"
                    value={formatCurrency(kpis.acquisition_value)}
                    icon={Wallet}
                    hint="Aset yang belum dipensiunkan"
                />
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <Panel title="Distribusi status" href="/assets" icon={BarChart3}>
                    <DistributionBars items={statusDistribution} colors={statusColors} />
                </Panel>
                <Panel title="Aset per kategori" href="/assets" icon={BarChart3}>
                    <DistributionBars items={categoryDistribution} emptyMessage="Belum ada aset aktif." />
                </Panel>
            </div>

            <div className="mt-6 grid gap-4 lg:grid-cols-2">
                <Panel title="Lisensi segera habis" subtitle="30 hari ke depan" href="/licenses" icon={KeyRound}>
                    {expiringLicenses.length === 0 ? (
                        <Empty>Tidak ada lisensi yang habis dalam 30 hari.</Empty>
                    ) : (
                        <ul className="divide-border -my-2 divide-y">
                            {expiringLicenses.map((license) => (
                                <li key={license.id} className="flex items-center justify-between gap-3 py-2">
                                    <div className="min-w-0">
                                        <Link
                                            href={`/licenses/${license.id}`}
                                            className="block truncate text-sm font-medium text-slate-800 hover:underline"
                                        >
                                            {license.name}
                                        </Link>
                                        <p className="text-muted-foreground text-[11px]">
                                            Seat {license.seats_used}/{license.seats_total} ·{' '}
                                            <DateDisplay value={license.expires_at} />
                                        </p>
                                    </div>
                                    <DaysLeft days={license.days_left} criticalAt={7} />
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
                <Panel
                    title="Maintenance jatuh tempo"
                    subtitle={overdueTickets > 0 ? `${overdueTickets} tiket lewat target` : '7 hari ke depan'}
                    href="/maintenance/plans"
                    icon={CalendarClock}
                >
                    {maintenanceDue.length === 0 ? (
                        <Empty>Tidak ada jadwal preventif yang jatuh tempo.</Empty>
                    ) : (
                        <ul className="divide-border -my-2 divide-y">
                            {maintenanceDue.map((plan) => (
                                <li key={plan.id} className="flex items-center justify-between gap-3 py-2">
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium text-slate-800">{plan.name}</p>
                                        <p className="text-muted-foreground text-[11px]">
                                            {plan.scope && `${plan.scope} · `}
                                            <DateDisplay value={plan.next_due_date} />
                                        </p>
                                    </div>
                                    <DaysLeft days={plan.days_left} criticalAt={0} />
                                </li>
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>

            <div className="mt-6">
                <Panel title="Peringatan terbaru" href="/alerts" icon={Bell} flush>
                    {recentAlerts.length === 0 ? (
                        <Empty>Tidak ada peringatan terbuka.</Empty>
                    ) : (
                        <ul className="divide-border divide-y">
                            {recentAlerts.map((alert) => (
                                <AlertRow key={alert.id} alert={alert} />
                            ))}
                        </ul>
                    )}
                </Panel>
            </div>
        </AppLayout>
    );
}

function Panel({
    title,
    subtitle,
    href,
    icon: Icon,
    flush = false,
    children,
}: {
    title: string;
    subtitle?: string;
    href: string;
    icon: LucideIcon;
    flush?: boolean;
    children: ReactNode;
}) {
    return (
        <section className="border-border bg-card rounded-xl border shadow-sm">
            <header className="border-border flex items-center gap-2 border-b px-4 py-3">
                <Icon className="size-4 text-slate-400" />
                <h2 className="text-sm font-semibold text-slate-800">{title}</h2>
                {subtitle && <span className="text-muted-foreground text-xs">· {subtitle}</span>}
                <Link href={href} className="text-primary ml-auto text-xs font-medium hover:underline">
                    Lihat semua
                </Link>
            </header>
            <div className={flush ? '' : 'p-4'}>{children}</div>
        </section>
    );
}

function Empty({ children }: { children: ReactNode }) {
    return <p className="text-muted-foreground px-4 py-6 text-center text-xs">{children}</p>;
}

function DaysLeft({ days, criticalAt }: { days: number; criticalAt: number }) {
    const label = days < 0 ? `Terlambat ${-days} hari` : days === 0 ? 'Hari ini' : `${days} hari lagi`;

    return (
        <span
            className={cn(
                'shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold',
                days <= criticalAt ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800',
            )}
        >
            {label}
        </span>
    );
}
