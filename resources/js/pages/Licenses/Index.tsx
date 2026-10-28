import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { CalendarClock, KeyRound, LayoutGrid, List, Plus, Users, Wallet } from 'lucide-react';
import { useState } from 'react';
import DataTable from '@/components/DataTable';
import EmptyState from '@/components/EmptyState';
import ExpiryBadge from '@/components/licenses/ExpiryBadge';
import SeatMeter from '@/components/licenses/SeatMeter';
import PageHeader from '@/components/PageHeader';
import Pagination, { type Paginator } from '@/components/Pagination';
import SearchInput from '@/components/SearchInput';
import StatCard from '@/components/StatCard';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout';
import { cn, formatCurrency } from '@/lib/utils';
import type { LicenseListItem, LicenseStatusFilter } from '@/types/licenses';

interface Filters {
    search: string;
    status: LicenseStatusFilter;
}

type View = 'cards' | 'table';

const VIEW_STORAGE_KEY = 'licenses.view';

const statusOptions: { value: LicenseStatusFilter; label: string }[] = [
    { value: '', label: 'Semua' },
    { value: 'expiring', label: 'Segera habis' },
    { value: 'expired', label: 'Sudah habis' },
    { value: 'full', label: 'Seat penuh' },
];

export default function LicensesIndex({
    filters,
    licenses,
    summary,
    warningDays,
    can,
}: {
    filters: Filters;
    licenses: Paginator<LicenseListItem>;
    summary: { count: number; annual_cost: string; expiring: number; seats_total: number; seats_used: number };
    warningDays: number;
    can: { create: boolean };
}) {
    const [view, setView] = useState<View>(() => {
        try {
            return window.localStorage.getItem(VIEW_STORAGE_KEY) === 'table' ? 'table' : 'cards';
        } catch {
            // Storage can be unavailable; the card view is the default.
            return 'cards';
        }
    });

    const changeView = (next: View): void => {
        setView(next);
        try {
            window.localStorage.setItem(VIEW_STORAGE_KEY, next);
        } catch {
            // Ignore: the choice just won't be remembered.
        }
    };

    const visit = (changes: Partial<Filters>): void => {
        const query = Object.fromEntries(
            Object.entries({ ...filters, ...changes }).filter(([, value]) => value !== ''),
        );

        router.get('/licenses', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const columns: ColumnDef<LicenseListItem, unknown>[] = [
        {
            id: 'name',
            header: 'Lisensi',
            cell: ({ row }) => (
                <Link href={`/licenses/${row.original.id}`} className="block min-w-0 hover:underline">
                    <p className="truncate font-medium text-slate-800">{row.original.name}</p>
                    <p className="text-muted-foreground truncate text-xs">
                        {[row.original.manufacturer, row.original.license_type_label].filter(Boolean).join(' · ')}
                    </p>
                </Link>
            ),
        },
        {
            id: 'seats',
            header: 'Seat',
            cell: ({ row }) => (
                <SeatMeter used={row.original.seats_used} total={row.original.seats_total} className="w-40" />
            ),
        },
        {
            id: 'expires_at',
            header: 'Masa berlaku',
            cell: ({ row }) => <ExpiryBadge expiresAt={row.original.expires_at} warningDays={warningDays} />,
        },
        {
            id: 'annual_cost',
            header: 'Biaya/tahun',
            cell: ({ row }) =>
                row.original.annual_cost === null ? (
                    <span className="text-muted-foreground">—</span>
                ) : (
                    <span className="tabular-nums">{formatCurrency(row.original.annual_cost)}</span>
                ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Lisensi" />

            <PageHeader
                title="Lisensi"
                description="Lisensi software, pemakaian seat, dan masa berlaku."
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Lisensi' }]}
                actions={
                    can.create && (
                        <Button asChild>
                            <Link href="/licenses/create">
                                <Plus /> Tambah lisensi
                            </Link>
                        </Button>
                    )
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Lisensi" value={summary.count} icon={KeyRound} />
                <StatCard
                    label="Seat terpakai"
                    value={`${summary.seats_used} / ${summary.seats_total}`}
                    icon={Users}
                    tone="info"
                />
                <StatCard
                    label={`Habis ≤ ${warningDays} hari`}
                    value={summary.expiring}
                    icon={CalendarClock}
                    tone={summary.expiring > 0 ? 'warning' : 'success'}
                />
                <StatCard label="Biaya tahunan" value={formatCurrency(summary.annual_cost)} icon={Wallet} />
            </div>

            <div className="border-border mb-4 flex flex-wrap items-center gap-3 rounded-xl border bg-white p-4 shadow-sm">
                <SearchInput
                    defaultValue={filters.search}
                    placeholder="Cari nama lisensi atau manufaktur"
                    onSearch={(search) => visit({ search })}
                    className="sm:w-80"
                />
                <div className="flex flex-wrap gap-2" role="radiogroup" aria-label="Status lisensi">
                    {statusOptions.map((option) => {
                        const active = option.value === filters.status;

                        return (
                            <button
                                key={option.value}
                                type="button"
                                role="radio"
                                aria-checked={active}
                                onClick={() => visit({ status: option.value })}
                                className={cn(
                                    'rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                    active
                                        ? 'border-primary bg-primary text-white'
                                        : 'border-border bg-white text-slate-600 hover:bg-slate-50',
                                )}
                            >
                                {option.label}
                            </button>
                        );
                    })}
                </div>
                <div className="ml-auto flex gap-1" role="group" aria-label="Tampilan">
                    <Button
                        variant={view === 'cards' ? 'secondary' : 'ghost'}
                        size="sm"
                        onClick={() => changeView('cards')}
                        aria-pressed={view === 'cards'}
                        aria-label="Tampilan kartu"
                    >
                        <LayoutGrid />
                    </Button>
                    <Button
                        variant={view === 'table' ? 'secondary' : 'ghost'}
                        size="sm"
                        onClick={() => changeView('table')}
                        aria-pressed={view === 'table'}
                        aria-label="Tampilan tabel"
                    >
                        <List />
                    </Button>
                </div>
            </div>

            {licenses.data.length === 0 ? (
                <EmptyState
                    icon={KeyRound}
                    title="Tidak ada lisensi yang cocok"
                    description="Ubah kata kunci atau filter untuk melihat lisensi lain."
                />
            ) : view === 'cards' ? (
                <>
                    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        {licenses.data.map((license) => (
                            <LicenseCard key={license.id} license={license} warningDays={warningDays} />
                        ))}
                    </div>
                    <div className="border-border mt-4 overflow-hidden rounded-xl border bg-white">
                        <Pagination paginator={licenses} />
                    </div>
                </>
            ) : (
                <div className="border-border overflow-hidden rounded-xl border bg-white shadow-sm">
                    <DataTable columns={columns} data={licenses.data} />
                    <Pagination paginator={licenses} />
                </div>
            )}
        </AppLayout>
    );
}

function LicenseCard({ license, warningDays }: { license: LicenseListItem; warningDays: number }) {
    return (
        <Link
            href={`/licenses/${license.id}`}
            className="border-border group flex flex-col gap-4 rounded-xl border bg-white p-5 shadow-sm transition-shadow hover:shadow-md"
        >
            <div className="min-w-0">
                <p className="truncate font-semibold text-slate-900 group-hover:underline">{license.name}</p>
                <p className="text-muted-foreground truncate text-xs">
                    {[license.manufacturer, license.license_type_label, license.category].filter(Boolean).join(' · ')}
                </p>
            </div>
            <SeatMeter used={license.seats_used} total={license.seats_total} />
            <div className="mt-auto flex flex-wrap items-center justify-between gap-2">
                <ExpiryBadge expiresAt={license.expires_at} warningDays={warningDays} />
                <span className="text-sm text-slate-700 tabular-nums">
                    {license.annual_cost === null ? '—' : `${formatCurrency(license.annual_cost)}/thn`}
                </span>
            </div>
        </Link>
    );
}
