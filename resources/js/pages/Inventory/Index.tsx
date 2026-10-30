import { Head, Link, router } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { AlertTriangle, Boxes, Pencil, Plus } from 'lucide-react';
import DataTable from '@/components/DataTable';
import EmptyState from '@/components/EmptyState';
import FilterTabs from '@/components/FilterTabs';
import PageHeader from '@/components/PageHeader';
import Pagination, { type Paginator } from '@/components/Pagination';
import SearchInput from '@/components/SearchInput';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/AppLayout';
import { cn } from '@/lib/utils';
import { isLowStock, type InventoryTab, type StockListItem } from '@/types/inventory';

interface Filters {
    search: string;
    low_stock: boolean;
}

const tabs: { value: InventoryTab; label: string; usedLabel: string }[] = [
    { value: 'components', label: 'Komponen', usedLabel: 'Terpasang' },
    { value: 'accessories', label: 'Aksesori', usedLabel: 'Dipinjam' },
];

export default function InventoryIndex({
    tab,
    filters,
    items,
    lowStockCounts,
    can,
}: {
    tab: InventoryTab;
    filters: Filters;
    items: Paginator<StockListItem>;
    lowStockCounts: Record<InventoryTab, number>;
    can: { createComponent: boolean; createAccessory: boolean };
}) {
    const current = tabs.find((option) => option.value === tab) ?? tabs[0];

    const visit = (changes: Partial<Filters & { tab: InventoryTab }>): void => {
        const next = { tab, ...filters, ...changes };
        const query = Object.fromEntries(
            Object.entries({ ...next, low_stock: next.low_stock ? 1 : null }).filter(
                ([, value]) => value !== null && value !== '',
            ),
        );

        router.get('/inventory', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const columns: ColumnDef<StockListItem, unknown>[] = [
        {
            id: 'name',
            header: 'Nama',
            cell: ({ row }) => {
                const content = (
                    <>
                        <p className="truncate font-medium text-zinc-800">{row.original.name}</p>
                        <p className="text-muted-foreground truncate text-xs">
                            {[row.original.detail, row.original.category, row.original.manufacturer]
                                .filter(Boolean)
                                .join(' · ')}
                        </p>
                    </>
                );

                return tab === 'accessories' ? (
                    <Link href={`/inventory/accessories/${row.original.id}`} className="block min-w-0 hover:underline">
                        {content}
                    </Link>
                ) : (
                    <div className="min-w-0">{content}</div>
                );
            },
        },
        { id: 'qty_total', header: 'Total', cell: ({ row }) => <Qty value={row.original.qty_total} /> },
        { id: 'qty_used', header: current.usedLabel, cell: ({ row }) => <Qty value={row.original.qty_used} /> },
        {
            id: 'qty_available',
            header: 'Tersedia',
            cell: ({ row }) => (
                <span
                    className={cn(
                        'inline-flex items-center gap-1 font-semibold tabular-nums',
                        isLowStock(row.original) ? 'text-amber-700' : 'text-zinc-900',
                    )}
                >
                    {isLowStock(row.original) && <AlertTriangle className="size-3.5" aria-label="Stok menipis" />}
                    {row.original.qty_available}
                </span>
            ),
        },
        { id: 'min_qty', header: 'Minimum', cell: ({ row }) => <Qty value={row.original.min_qty} /> },
        {
            id: 'location',
            header: 'Lokasi',
            cell: ({ row }) => row.original.location ?? <span className="text-muted-foreground">—</span>,
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) =>
                (tab === 'components' ? can.createComponent : can.createAccessory) && (
                    <Button variant="ghost" size="sm" asChild>
                        <Link
                            href={`/inventory/${tab}/${row.original.id}/edit`}
                            aria-label={`Edit ${row.original.name}`}
                        >
                            <Pencil />
                        </Link>
                    </Button>
                ),
        },
    ];

    const canCreate = tab === 'components' ? can.createComponent : can.createAccessory;

    return (
        <AppLayout>
            <Head title="Komponen & Aksesori" />

            <PageHeader
                title="Komponen & Aksesori"
                description="Stok komponen yang dipasang ke inventaris dan aksesori yang dipinjamkan ke karyawan."
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Komponen & Aksesori' }]}
                actions={
                    canCreate && (
                        <Button asChild>
                            <Link href={`/inventory/${tab}/create`}>
                                <Plus /> Tambah {current.label.toLowerCase()}
                            </Link>
                        </Button>
                    )
                }
            />

            <FilterTabs
                label="Jenis stok"
                tabs={tabs.map((option) => ({
                    value: option.value,
                    label: option.label,
                    count: lowStockCounts[option.value] > 0 ? lowStockCounts[option.value] : undefined,
                    countTone: 'warning',
                    title: lowStockCounts[option.value] > 0 ? 'Item dengan stok menipis' : undefined,
                }))}
                value={tab}
                onChange={(value) => visit({ tab: value, search: '', low_stock: false })}
            />

            <div className="border-border bg-card overflow-hidden rounded-xl border">
                <div className="border-border flex flex-wrap items-center gap-4 border-b p-4">
                    <SearchInput
                        key={tab}
                        defaultValue={filters.search}
                        placeholder={tab === 'components' ? 'Cari nama atau spesifikasi' : 'Cari nama atau model'}
                        onSearch={(search) => visit({ search })}
                        className="sm:w-96"
                    />
                    <label className="flex items-center gap-2 text-sm text-zinc-600">
                        <Checkbox
                            checked={filters.low_stock}
                            onCheckedChange={(checked) => visit({ low_stock: checked === true })}
                        />
                        Hanya stok menipis
                    </label>
                </div>

                {items.data.length === 0 ? (
                    <div className="p-6">
                        <EmptyState
                            icon={Boxes}
                            title={`Tidak ada ${current.label.toLowerCase()} yang cocok`}
                            description="Ubah kata kunci atau filter untuk melihat item lain."
                        />
                    </div>
                ) : (
                    <>
                        <DataTable columns={columns} data={items.data} />
                        <Pagination paginator={items} />
                    </>
                )}
            </div>
        </AppLayout>
    );
}

function Qty({ value }: { value: number }) {
    return <span className="tabular-nums">{value}</span>;
}
