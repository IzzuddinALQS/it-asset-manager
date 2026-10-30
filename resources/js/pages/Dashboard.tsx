import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import type { ReactNode } from 'react';
import AlertRow from '@/components/alerts/AlertRow';
import DistributionBars, { type DistributionItem } from '@/components/dashboard/DistributionBars';
import DateDisplay from '@/components/DateDisplay';
import { ToneBadge } from '@/components/StatusBadge';
import AppLayout from '@/layouts/AppLayout';
import { cn, formatCurrency } from '@/lib/utils';
import type { PageProps } from '@/types';
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
    pending: 'bg-violet-500',
    ready: 'bg-emerald-500',
    deployed: 'bg-indigo-500',
    maintenance: 'bg-amber-500',
    retired: 'bg-zinc-300',
};

function greeting(): string {
    const hour = new Date().getHours();

    if (hour < 11) {
        return 'Selamat pagi';
    }

    if (hour < 15) {
        return 'Selamat siang';
    }

    return hour < 19 ? 'Selamat sore' : 'Selamat malam';
}

export default function Dashboard({
    kpis,
    statusDistribution,
    categoryDistribution,
    expiringLicenses,
    maintenanceDue,
    overdueTickets,
    recentAlerts,
}: DashboardProps) {
    const firstName = usePage<PageProps>().props.auth.user?.employee?.name?.split(' ')[0];

    return (
        <AppLayout>
            <Head title="Dashboard" />

            <div className="mb-8">
                <h1 className="text-2xl font-semibold tracking-tight text-zinc-950 sm:text-[28px]">
                    {greeting()}
                    {firstName ? `, ${firstName}` : ''}
                </h1>
                <p className="text-muted-foreground mt-1.5 text-[15px]">
                    Ringkasan inventaris, lisensi, maintenance, dan peringatan hari ini.
                </p>
            </div>

            <section
                aria-label="Ringkasan inventaris"
                className="border-border bg-card grid grid-cols-2 overflow-hidden rounded-xl border sm:grid-cols-3 lg:grid-cols-5"
            >
                <Kpi
                    label="Total inventaris"
                    value={kpis.total}
                    hint={kpis.retired > 0 ? `Tidak termasuk ${kpis.retired} dipensiunkan` : 'Seluruh inventaris aktif'}
                    href="/assets"
                />
                <Kpi label="Digunakan" value={kpis.deployed} dot="bg-indigo-500" href="/assets?status=deployed" />
                <Kpi label="Siap guna" value={kpis.ready} dot="bg-emerald-500" href="/assets?status=ready" />
                <Kpi label="Perbaikan" value={kpis.maintenance} dot="bg-amber-500" href="/assets?status=maintenance" />
                <Kpi
                    label="Nilai perolehan"
                    value={formatCurrency(kpis.acquisition_value)}
                    hint="Inventaris yang belum dipensiunkan"
                    compact
                    className="col-span-2 sm:col-span-2 lg:col-span-1"
                />
            </section>

            <div className="mt-6 grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Panel title="Distribusi status" description="Komposisi seluruh inventaris" href="/assets">
                        <StatusComposition items={statusDistribution} />
                    </Panel>
                    <Panel title="Inventaris per kategori" description="Hanya inventaris aktif" href="/assets">
                        <DistributionBars items={categoryDistribution} emptyMessage="Belum ada inventaris aktif." />
                    </Panel>
                </div>

                <div className="space-y-6">
                    <Panel
                        title="Lisensi segera habis"
                        description="30 hari ke depan"
                        href="/licenses"
                        count={expiringLicenses.length}
                        flush
                    >
                        {expiringLicenses.length === 0 ? (
                            <Empty>Tidak ada lisensi yang habis dalam 30 hari.</Empty>
                        ) : (
                            <ul className="divide-border divide-y">
                                {expiringLicenses.map((license) => (
                                    <li key={license.id}>
                                        <Link
                                            href={`/licenses/${license.id}`}
                                            className="flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-zinc-50"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-zinc-900">
                                                    {license.name}
                                                </p>
                                                <p className="text-muted-foreground mt-0.5 text-xs">
                                                    Kuota {license.seats_used}/{license.seats_total} ·{' '}
                                                    <DateDisplay value={license.expires_at} />
                                                </p>
                                            </div>
                                            <DaysLeft days={license.days_left} criticalAt={7} />
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>

                    <Panel
                        title="Maintenance jatuh tempo"
                        description={overdueTickets > 0 ? `${overdueTickets} tiket lewat target` : '7 hari ke depan'}
                        descriptionTone={overdueTickets > 0 ? 'danger' : undefined}
                        href="/maintenance/plans"
                        count={maintenanceDue.length}
                        flush
                    >
                        {maintenanceDue.length === 0 ? (
                            <Empty>Tidak ada jadwal preventif yang jatuh tempo.</Empty>
                        ) : (
                            <ul className="divide-border divide-y">
                                {maintenanceDue.map((plan) => (
                                    <li key={plan.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium text-zinc-900">{plan.name}</p>
                                            <p className="text-muted-foreground mt-0.5 text-xs">
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
            </div>

            <div className="mt-6">
                <Panel
                    title="Peringatan terbaru"
                    description="Peringatan terbuka yang perlu ditindaklanjuti"
                    href="/alerts"
                    count={recentAlerts.length}
                    flush
                >
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

function Kpi({
    label,
    value,
    hint,
    dot,
    href,
    compact = false,
    className,
}: {
    label: string;
    value: string | number;
    hint?: string;
    dot?: string;
    href?: string;
    compact?: boolean;
    className?: string;
}) {
    const body = (
        <>
            <p className="flex items-center gap-2 text-[13px] font-medium text-zinc-600">
                {dot && <span className={cn('size-2 rounded-full', dot)} aria-hidden />}
                {label}
            </p>
            <p
                className={cn(
                    'mt-2 font-semibold tracking-tight text-zinc-950 tabular-nums',
                    compact ? 'text-xl leading-9 [overflow-wrap:anywhere]' : 'text-[28px] leading-9',
                )}
            >
                {value}
            </p>
            {hint && <p className="text-muted-foreground mt-0.5 truncate text-xs">{hint}</p>}
        </>
    );

    const cellClass = cn(
        'border-border -mr-px -mb-px border-r border-b p-5 transition-colors',
        href && 'hover:bg-zinc-50',
        className,
    );

    return href ? (
        <Link href={href} className={cellClass}>
            {body}
        </Link>
    ) : (
        <div className={cellClass}>{body}</div>
    );
}

/** Single stacked bar + legend: status is a part-of-whole, so one bar reads faster than five. */
function StatusComposition({ items }: { items: DistributionItem[] }) {
    const sum = items.reduce((total, item) => total + item.total, 0);

    if (sum === 0) {
        return <p className="text-muted-foreground py-6 text-center text-sm">Belum ada data.</p>;
    }

    return (
        <div>
            <div className="flex h-2.5 gap-0.5 overflow-hidden rounded-full bg-zinc-100">
                {items
                    .filter((item) => item.total > 0)
                    .map((item) => (
                        <div
                            key={item.key}
                            className={cn(
                                'h-full first:rounded-l-full last:rounded-r-full',
                                statusColors[item.key] ?? 'bg-primary',
                            )}
                            style={{ width: `${(item.total / sum) * 100}%` }}
                            title={`${item.label}: ${item.total}`}
                        />
                    ))}
            </div>
            <ul className="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 sm:grid-cols-3 lg:grid-cols-5">
                {items.map((item) => (
                    <li key={item.key}>
                        <Link href={`/assets?status=${item.key}`} className="group block rounded-md transition-colors">
                            <span className="flex items-center gap-2 text-[13px] text-zinc-600 group-hover:text-zinc-950">
                                <span
                                    className={cn('size-2 rounded-full', statusColors[item.key] ?? 'bg-primary')}
                                    aria-hidden
                                />
                                {item.label}
                            </span>
                            <span className="mt-1 block text-lg font-semibold text-zinc-950 tabular-nums">
                                {item.total}
                                <span className="text-muted-foreground ml-1.5 text-xs font-normal">
                                    {Math.round((item.total / sum) * 100)}%
                                </span>
                            </span>
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function Panel({
    title,
    description,
    descriptionTone,
    href,
    count,
    flush = false,
    children,
}: {
    title: string;
    description?: string;
    descriptionTone?: 'danger';
    href: string;
    count?: number;
    flush?: boolean;
    children: ReactNode;
}) {
    return (
        <section className="border-border bg-card rounded-xl border">
            <header className={cn('flex items-start gap-3 px-5 pt-4', flush ? 'border-border border-b pb-4' : 'pb-1')}>
                <div className="min-w-0 flex-1">
                    <h2 className="flex items-center gap-2 text-[15px] font-semibold text-zinc-950">
                        {title}
                        {count !== undefined && count > 0 && (
                            <span className="rounded-full bg-zinc-100 px-1.5 text-xs font-medium text-zinc-600 tabular-nums">
                                {count}
                            </span>
                        )}
                    </h2>
                    {description && (
                        <p
                            className={cn(
                                'mt-0.5 text-[13px]',
                                descriptionTone === 'danger' ? 'font-medium text-red-600' : 'text-muted-foreground',
                            )}
                        >
                            {description}
                        </p>
                    )}
                </div>
                <Link
                    href={href}
                    className="group text-muted-foreground flex shrink-0 items-center gap-1 text-[13px] font-medium transition-colors hover:text-zinc-950"
                >
                    Lihat semua
                    <ArrowRight className="size-3.5 transition-transform group-hover:translate-x-0.5" />
                </Link>
            </header>
            <div className={flush ? '' : 'p-5'}>{children}</div>
        </section>
    );
}

function Empty({ children }: { children: ReactNode }) {
    return <p className="text-muted-foreground px-5 py-8 text-center text-sm">{children}</p>;
}

function DaysLeft({ days, criticalAt }: { days: number; criticalAt: number }) {
    const label = days < 0 ? `Terlambat ${-days} hari` : days === 0 ? 'Hari ini' : `${days} hari lagi`;

    return (
        <ToneBadge tone={days <= criticalAt ? 'danger' : 'warning'} className="shrink-0">
            {label}
        </ToneBadge>
    );
}
