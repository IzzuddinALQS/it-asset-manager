import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { RefreshCw, Users } from 'lucide-react';
import { useState } from 'react';
import DataTable from '@/components/DataTable';
import DateDisplay from '@/components/DateDisplay';
import { Button } from '@/components/ui/button';
import OptionSelect from '@/components/OptionSelect';
import SearchSelect from '@/components/SearchSelect';
import { optionsUrl } from '@/lib/options';
import PageHeader from '@/components/PageHeader';
import Pagination, { type Paginator } from '@/components/Pagination';
import SearchInput from '@/components/SearchInput';
import StatusBadge from '@/components/StatusBadge';
import AppLayout from '@/layouts/AppLayout';
import type { EnumOption } from '@/types/assets';
import type { DirectoryEmployee } from '@/types/directory';

interface HerdSyncSummary {
    status: 'running' | 'success' | 'failed';
    started_at: string;
    created_count: number;
    updated_count: number;
    offboarding_count: number;
    error_message: string | null;
}

interface Filters {
    search: string;
    status: string;
    department_id: string;
}

export default function DirectoryIndex({
    filters,
    employees,
    statuses,
    lastSync,
    canSync,
}: {
    filters: Filters;
    employees: Paginator<DirectoryEmployee>;
    statuses: EnumOption[];
    lastSync: HerdSyncSummary | null;
    canSync: boolean;
}) {
    const [syncing, setSyncing] = useState(false);

    const visit = (changes: Partial<Filters>): void => {
        const query = Object.fromEntries(
            Object.entries({ ...filters, ...changes }).filter(([, value]) => value !== ''),
        );

        router.get('/directory', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const holding = (count: number) =>
        count === 0 ? <span className="text-muted-foreground">—</span> : <span className="tabular-nums">{count}</span>;

    const columns: ColumnDef<DirectoryEmployee, unknown>[] = [
        {
            id: 'name',
            header: 'Karyawan',
            cell: ({ row }) => (
                <Link href={`/directory/${row.original.id}`} className="block min-w-0 hover:underline">
                    <p className="truncate font-medium text-zinc-800">{row.original.name}</p>
                    <p className="text-muted-foreground truncate text-xs">
                        <span className="font-tag">{row.original.employee_number}</span>
                        {row.original.job_title && ` · ${row.original.job_title}`}
                    </p>
                </Link>
            ),
        },
        {
            id: 'department',
            header: 'Departemen',
            cell: ({ row }) => (
                <div className="min-w-0">
                    <p className="truncate">{row.original.department ?? '—'}</p>
                    <p className="text-muted-foreground truncate text-xs">{row.original.location}</p>
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => <StatusBadge status={row.original.employment_status} />,
        },
        { id: 'assets', header: 'Inventaris', cell: ({ row }) => holding(row.original.assets_count) },
        { id: 'accessories', header: 'Aksesori', cell: ({ row }) => holding(row.original.accessories_count) },
        { id: 'licenses', header: 'Lisensi', cell: ({ row }) => holding(row.original.licenses_count) },
    ];

    return (
        <AppLayout>
            <Head title="Direktori Karyawan" />

            <PageHeader
                title="Direktori Karyawan"
                description="Data karyawan dari HERD beserta inventaris, aksesori, dan lisensi yang dipegang. Hanya baca."
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Direktori Karyawan' }]}
                actions={
                    canSync && (
                        <Button
                            variant="outline"
                            disabled={syncing}
                            onClick={() =>
                                router.post(
                                    '/settings/herd-sync',
                                    {},
                                    {
                                        preserveScroll: true,
                                        onStart: () => setSyncing(true),
                                        onFinish: () => setSyncing(false),
                                    },
                                )
                            }
                        >
                            <RefreshCw className={syncing ? 'animate-spin' : undefined} /> Sync sekarang
                        </Button>
                    )
                }
            />

            {lastSync && (
                <p className="text-muted-foreground mb-3 text-xs">
                    Sinkronisasi HERD terakhir: <DateDisplay value={lastSync.started_at} withTime /> ·{' '}
                    {lastSync.status === 'failed' ? (
                        <span className="text-red-700">gagal ({lastSync.error_message})</span>
                    ) : (
                        `${lastSync.created_count} baru, ${lastSync.updated_count} diperbarui, ${lastSync.offboarding_count} offboarding`
                    )}
                </p>
            )}

            <div className="border-border bg-card mb-4 flex flex-wrap items-center gap-3 rounded-xl border p-4">
                <SearchInput
                    defaultValue={filters.search}
                    placeholder="Cari nama, NIK, atau email"
                    onSearch={(search) => visit({ search })}
                    className="sm:w-80"
                />
                <OptionSelect
                    value={filters.status || null}
                    onChange={(status) => visit({ status: status ?? '' })}
                    options={statuses}
                    emptyLabel="Semua status"
                    placeholder="Semua status"
                    className="w-full bg-white sm:w-44"
                />
                <SearchSelect
                    url={optionsUrl('departments')}
                    value={filters.department_id || null}
                    onChange={(department) => visit({ department_id: department ?? '' })}
                    emptyLabel="Semua departemen"
                    placeholder="Semua departemen"
                    className="sm:w-56"
                />
            </div>

            <div className="border-border bg-card overflow-hidden rounded-xl border">
                <DataTable
                    columns={columns}
                    data={employees.data}
                    emptyMessage="Tidak ada karyawan yang cocok dengan filter."
                />
                {employees.data.length > 0 && <Pagination paginator={employees} />}
            </div>

            {employees.total === 0 && filters.search === '' && filters.status === '' && (
                <p className="text-muted-foreground mt-3 flex items-center gap-2 text-xs">
                    <Users className="size-4" /> Direktori terisi otomatis setelah sinkronisasi HERD.
                </p>
            )}
        </AppLayout>
    );
}
