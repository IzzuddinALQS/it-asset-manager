import { Head, router } from '@inertiajs/react';
import { AlertOctagon, AlertTriangle, BellOff, Info } from 'lucide-react';
import AlertRow from '@/components/alerts/AlertRow';
import EmptyState from '@/components/EmptyState';
import OptionSelect from '@/components/OptionSelect';
import PageHeader from '@/components/PageHeader';
import Pagination, { type Paginator } from '@/components/Pagination';
import StatCard from '@/components/StatCard';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout';
import type { AlertItem, AlertSeverity } from '@/types/alerts';
import type { EnumOption } from '@/types/assets';

interface Filters {
    type: string;
    severity: string;
    status: 'open' | 'resolved';
}

interface AlertsProps {
    filters: Filters;
    alerts: Paginator<AlertItem>;
    summary: Record<AlertSeverity, number>;
    types: EnumOption[];
    severities: EnumOption[];
    can: { resolve: boolean };
}

export default function AlertsIndex({ filters, alerts, summary, types, severities, can }: AlertsProps) {
    const visit = (changes: Partial<Filters>): void => {
        const query = Object.fromEntries(
            Object.entries({ ...filters, ...changes }).filter(
                ([key, value]) => value !== '' && !(key === 'status' && value === 'open'),
            ),
        );

        router.get('/alerts', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const resolve = (alert: AlertItem): void => {
        router.post(`/alerts/${alert.id}/resolve`, {}, { preserveScroll: true });
    };

    return (
        <AppLayout>
            <Head title="Peringatan" />

            <PageHeader
                title="Peringatan"
                description="Offboarding, garansi, lisensi, stok, dan maintenance yang perlu ditindaklanjuti. Diperbarui otomatis setiap pagi."
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Peringatan' }]}
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard
                    label="Kritis"
                    value={summary.critical}
                    icon={AlertOctagon}
                    tone={summary.critical > 0 ? 'danger' : 'default'}
                />
                <StatCard
                    label="Peringatan"
                    value={summary.warning}
                    icon={AlertTriangle}
                    tone={summary.warning > 0 ? 'warning' : 'default'}
                />
                <StatCard label="Info" value={summary.info} icon={Info} tone="info" />
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <Tabs value={filters.status} onValueChange={(status) => visit({ status: status as Filters['status'] })}>
                    <TabsList>
                        <TabsTrigger value="open">Terbuka</TabsTrigger>
                        <TabsTrigger value="resolved">Selesai</TabsTrigger>
                    </TabsList>
                </Tabs>
                <OptionSelect
                    value={filters.type === '' ? null : filters.type}
                    onChange={(value) => visit({ type: value ?? '' })}
                    options={types.map((option) => ({ value: option.value, label: option.label }))}
                    emptyLabel="Semua tipe"
                    className="w-full bg-white sm:w-56"
                />
                <OptionSelect
                    value={filters.severity === '' ? null : filters.severity}
                    onChange={(value) => visit({ severity: value ?? '' })}
                    options={severities.map((option) => ({ value: option.value, label: option.label }))}
                    emptyLabel="Semua tingkat"
                    className="w-full bg-white sm:w-44"
                />
            </div>

            {alerts.data.length === 0 ? (
                <EmptyState
                    icon={BellOff}
                    title={filters.status === 'open' ? 'Tidak ada peringatan terbuka' : 'Belum ada peringatan selesai'}
                    description="Coba ubah filter tipe atau tingkat."
                />
            ) : (
                <div className="border-border bg-card rounded-lg border">
                    <ul className="divide-border divide-y">
                        {alerts.data.map((alert) => (
                            <AlertRow
                                key={alert.id}
                                alert={alert}
                                onResolve={can.resolve && alert.resolved_at === null ? () => resolve(alert) : undefined}
                            />
                        ))}
                    </ul>
                    <div className="px-4 pb-3">
                        <Pagination paginator={alerts} />
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
