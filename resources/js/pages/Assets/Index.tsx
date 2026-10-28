import { Head, Link, router, usePage } from '@inertiajs/react';
import type { ColumnDef, SortingState } from '@tanstack/react-table';
import { HardDrive, MapPin, Plus, Printer, User } from 'lucide-react';
import { useState } from 'react';
import DataTable from '@/components/DataTable';
import DateDisplay from '@/components/DateDisplay';
import EmptyState from '@/components/EmptyState';
import OptionSelect from '@/components/OptionSelect';
import PageHeader from '@/components/PageHeader';
import Pagination, { type Paginator } from '@/components/Pagination';
import SearchInput from '@/components/SearchInput';
import StatusBadge from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';
import type { AssetListItem, AssetStatus, EnumOption, Option } from '@/types/assets';

interface Filters {
    search: string;
    status: string;
    category_id: number | null;
    location_id: number | null;
    sort: string;
    direction: 'asc' | 'desc';
}

export default function AssetsIndex({
    assets,
    filters,
    statusOptions,
    statusCounts,
    categories,
    locations,
}: {
    assets: Paginator<AssetListItem>;
    filters: Filters;
    statusOptions: EnumOption[];
    statusCounts: Record<AssetStatus, number>;
    categories: Option[];
    locations: Option[];
}) {
    const { permissions } = usePage<PageProps>().props;
    const [selected, setSelected] = useState<number[]>([]);

    const applyFilters = (changes: Partial<Filters>): void => {
        const next = { ...filters, ...changes };
        const query = Object.fromEntries(Object.entries(next).filter(([, value]) => value !== null && value !== ''));

        router.get('/assets', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const totalAssets = Object.values(statusCounts).reduce((sum, count) => sum + count, 0);
    const pageIds = assets.data.map((asset) => asset.id);
    const allOnPageSelected = pageIds.length > 0 && pageIds.every((id) => selected.includes(id));

    const toggle = (id: number, checked: boolean): void =>
        setSelected((current) => (checked ? [...current, id] : current.filter((value) => value !== id)));

    const columns: ColumnDef<AssetListItem, unknown>[] = [
        {
            id: 'select',
            enableSorting: false,
            header: () => (
                <Checkbox
                    aria-label="Pilih semua aset di halaman ini"
                    checked={allOnPageSelected}
                    onCheckedChange={(checked) =>
                        setSelected((current) =>
                            checked === true
                                ? Array.from(new Set([...current, ...pageIds]))
                                : current.filter((id) => !pageIds.includes(id)),
                        )
                    }
                />
            ),
            cell: ({ row }) => (
                <Checkbox
                    aria-label={`Pilih ${row.original.asset_tag}`}
                    checked={selected.includes(row.original.id)}
                    onCheckedChange={(checked) => toggle(row.original.id, checked === true)}
                />
            ),
        },
        {
            id: 'asset_tag',
            header: 'Tag',
            cell: ({ row }) => (
                <Link
                    href={`/assets/${row.original.id}`}
                    className="font-tag text-primary font-semibold whitespace-nowrap hover:underline"
                >
                    {row.original.asset_tag}
                </Link>
            ),
        },
        {
            id: 'model',
            header: 'Model',
            enableSorting: false,
            cell: ({ row }) => (
                <div className="min-w-0">
                    <p className="truncate font-medium text-slate-800">{row.original.model ?? '—'}</p>
                    <p className="text-muted-foreground truncate text-xs">
                        {[row.original.category, row.original.serial_number].filter(Boolean).join(' · ')}
                    </p>
                </div>
            ),
        },
        {
            id: 'status',
            header: 'Status',
            cell: ({ row }) => <StatusBadge status={row.original.status} label={row.original.status_label} />,
        },
        {
            id: 'holder',
            header: 'Pemegang',
            enableSorting: false,
            cell: ({ row }) =>
                row.original.holder ? (
                    <span className="flex items-center gap-1.5 whitespace-nowrap">
                        {row.original.holder_type === 'location' ? (
                            <MapPin className="size-3.5 text-slate-400" />
                        ) : (
                            <User className="size-3.5 text-slate-400" />
                        )}
                        {row.original.holder}
                    </span>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
        },
        {
            id: 'location',
            header: 'Lokasi',
            enableSorting: false,
            cell: ({ row }) => row.original.location ?? <span className="text-muted-foreground">—</span>,
        },
        {
            id: 'warranty_until',
            header: 'Garansi',
            cell: ({ row }) => <DateDisplay value={row.original.warranty_until} />,
        },
        {
            id: 'purchase_date',
            header: 'Tgl. beli',
            cell: ({ row }) => <DateDisplay value={row.original.purchase_date} />,
        },
    ];

    const sorting: SortingState = [{ id: filters.sort, desc: filters.direction === 'desc' }];
    const statusTabs = [{ value: '', label: 'Semua', count: totalAssets }].concat(
        statusOptions.map((option) => ({
            value: option.value,
            label: option.label,
            count: statusCounts[option.value as AssetStatus] ?? 0,
        })),
    );

    return (
        <AppLayout>
            <Head title="Aset" />

            <PageHeader
                title="Aset"
                description="Semua perangkat IT beserta status, pemegang, dan masa garansinya."
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Aset' }]}
                actions={
                    <>
                        {selected.length > 0 && (
                            <Button variant="outline" asChild>
                                <a
                                    href={`/assets/labels?${selected.map((id) => `ids[]=${id}`).join('&')}`}
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    <Printer /> Cetak label ({selected.length})
                                </a>
                            </Button>
                        )}
                        {permissions.manage && (
                            <Button asChild>
                                <Link href="/assets/create">
                                    <Plus /> Tambah aset
                                </Link>
                            </Button>
                        )}
                    </>
                }
            />

            <div className="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Filter status">
                {statusTabs.map((tab) => {
                    const active = filters.status === tab.value;

                    return (
                        <button
                            key={tab.value || 'all'}
                            type="button"
                            role="tab"
                            aria-selected={active}
                            onClick={() => applyFilters({ status: tab.value })}
                            className={cn(
                                'flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                active
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-border bg-white text-slate-600 hover:bg-slate-50',
                            )}
                        >
                            {tab.label}
                            <span
                                className={cn(
                                    'rounded-full px-1.5 text-xs tabular-nums',
                                    active ? 'bg-white/20' : 'bg-slate-100 text-slate-500',
                                )}
                            >
                                {tab.count}
                            </span>
                        </button>
                    );
                })}
            </div>

            <div className="border-border overflow-hidden rounded-xl border bg-white shadow-sm">
                <div className="border-border flex flex-wrap items-center gap-3 border-b p-4">
                    <SearchInput
                        defaultValue={filters.search}
                        placeholder="Cari tag, serial, model, atau pemegang"
                        onSearch={(search) => applyFilters({ search })}
                        className="sm:w-96"
                    />
                    <OptionSelect
                        value={filters.category_id === null ? null : String(filters.category_id)}
                        onChange={(value) => applyFilters({ category_id: value === null ? null : Number(value) })}
                        options={categories.map((category) => ({ value: String(category.id), label: category.name }))}
                        emptyLabel="Semua kategori"
                        className="w-full bg-white sm:w-52"
                    />
                    <OptionSelect
                        value={filters.location_id === null ? null : String(filters.location_id)}
                        onChange={(value) => applyFilters({ location_id: value === null ? null : Number(value) })}
                        options={locations.map((location) => ({ value: String(location.id), label: location.name }))}
                        emptyLabel="Semua lokasi"
                        className="w-full bg-white sm:w-52"
                    />
                </div>

                {assets.data.length === 0 ? (
                    <div className="p-6">
                        <EmptyState
                            icon={HardDrive}
                            title="Tidak ada aset yang cocok"
                            description="Ubah kata kunci atau filter untuk melihat aset lain."
                        />
                    </div>
                ) : (
                    <>
                        <DataTable
                            columns={columns}
                            data={assets.data}
                            sorting={sorting}
                            onSortingChange={(next) =>
                                applyFilters({
                                    sort: next[0]?.id ?? 'asset_tag',
                                    direction: next[0]?.desc ? 'desc' : 'asc',
                                })
                            }
                        />
                        <Pagination paginator={assets} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}
