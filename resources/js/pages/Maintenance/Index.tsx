import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    CalendarClock,
    CalendarRange,
    CheckCircle2,
    EllipsisVertical,
    Plus,
    Wallet,
    Wrench,
} from 'lucide-react';
import { useState } from 'react';
import DateDisplay from '@/components/DateDisplay';
import CreateTicketDialog from '@/components/maintenance/CreateTicketDialog';
import PriorityBadge from '@/components/maintenance/PriorityBadge';
import OptionSelect from '@/components/OptionSelect';
import PageHeader from '@/components/PageHeader';
import SearchInput from '@/components/SearchInput';
import StatCard from '@/components/StatCard';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout';
import { useConfirm } from '@/lib/confirm';
import { cn, formatCurrency } from '@/lib/utils';
import type { EnumOption } from '@/types/assets';
import type { MaintenanceCard, MaintenanceStatus } from '@/types/maintenance';

interface Filters {
    search: string;
    type: string;
    priority: string;
    show_cancelled: boolean;
}

interface BoardProps {
    filters: Filters;
    tickets: MaintenanceCard[];
    summary: { open: number; overdue: number; cost_this_year: string; plans_due: number };
    closedVisibleDays: number;
    types: EnumOption[];
    priorities: EnumOption[];
    handledBy: EnumOption[];
    can: { create: boolean; update: boolean };
}

const columns: { status: MaintenanceStatus; label: string; accent: string }[] = [
    { status: 'reported', label: 'Dilaporkan', accent: 'bg-amber-500' },
    { status: 'in_progress', label: 'Dikerjakan', accent: 'bg-indigo-500' },
    { status: 'waiting_parts', label: 'Menunggu Sparepart', accent: 'bg-orange-500' },
    { status: 'done', label: 'Selesai', accent: 'bg-emerald-500' },
    { status: 'cancelled', label: 'Dibatalkan', accent: 'bg-zinc-400' },
];

export default function MaintenanceIndex(props: BoardProps) {
    return (
        <AppLayout>
            <MaintenanceBoard {...props} />
        </AppLayout>
    );
}

/** Rendered inside AppLayout so `useConfirm` has its provider. */
function MaintenanceBoard({
    filters,
    tickets,
    summary,
    closedVisibleDays,
    types,
    priorities,
    handledBy,
    can,
}: BoardProps) {
    const confirm = useConfirm();
    const [createOpen, setCreateOpen] = useState(false);
    const visibleColumns = columns.filter((column) => column.status !== 'cancelled' || filters.show_cancelled);

    const visit = (changes: Partial<Filters>): void => {
        const query = Object.fromEntries(
            Object.entries({ ...filters, ...changes }).filter(([, value]) => value !== '' && value !== false),
        );

        router.get('/maintenance', query, { preserveState: true, preserveScroll: true, replace: true });
    };

    const move = (ticket: MaintenanceCard, status: MaintenanceStatus): void => {
        router.post(`/maintenance/${ticket.id}/status`, { status }, { preserveScroll: true });
    };

    const cancel = async (ticket: MaintenanceCard): Promise<void> => {
        const confirmed = await confirm({
            title: `Batalkan ${ticket.ticket_number}?`,
            description: 'Inventaris dikembalikan ke status sebelum masuk maintenance.',
            confirmLabel: 'Batalkan tiket',
            destructive: true,
        });

        if (confirmed) {
            router.post(`/maintenance/${ticket.id}/cancel`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Maintenance" />

            <PageHeader
                title="Maintenance"
                description="Papan tiket perbaikan, upgrade, dan perawatan preventif."
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Maintenance' }]}
                actions={
                    <div className="flex flex-wrap justify-end gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/maintenance/plans">
                                <CalendarRange /> Jadwal preventif
                            </Link>
                        </Button>
                        {can.create && (
                            <Button onClick={() => setCreateOpen(true)}>
                                <Plus /> Buat tiket
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Tiket terbuka" value={summary.open} icon={Wrench} tone="info" />
                <StatCard
                    label="Lewat target"
                    value={summary.overdue}
                    icon={AlertTriangle}
                    tone={summary.overdue > 0 ? 'danger' : 'default'}
                />
                <StatCard label="Biaya aktual tahun ini" value={formatCurrency(summary.cost_this_year)} icon={Wallet} />
                <StatCard
                    label="Jadwal preventif jatuh tempo"
                    value={summary.plans_due}
                    icon={CalendarClock}
                    tone={summary.plans_due > 0 ? 'warning' : 'default'}
                />
            </div>

            <div className="mb-4 flex flex-wrap items-center gap-3">
                <SearchInput
                    defaultValue={filters.search}
                    onSearch={(search) => visit({ search })}
                    placeholder="Cari nomor tiket, judul, atau tag inventaris…"
                    className="w-full sm:w-80"
                />
                <OptionSelect
                    value={filters.type === '' ? null : filters.type}
                    onChange={(value) => visit({ type: value ?? '' })}
                    options={types.map((option) => ({ value: option.value, label: option.label }))}
                    emptyLabel="Semua jenis"
                    className="w-full bg-white sm:w-44"
                />
                <OptionSelect
                    value={filters.priority === '' ? null : filters.priority}
                    onChange={(value) => visit({ priority: value ?? '' })}
                    options={priorities.map((option) => ({ value: option.value, label: option.label }))}
                    emptyLabel="Semua prioritas"
                    className="w-full bg-white sm:w-44"
                />
                <label className="flex items-center gap-2 text-sm text-zinc-600">
                    <Checkbox
                        checked={filters.show_cancelled}
                        onCheckedChange={(checked) => visit({ show_cancelled: checked === true })}
                    />
                    Tampilkan dibatalkan
                </label>
            </div>

            <div className="-mx-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
                <div
                    className={cn(
                        'grid min-w-[960px] gap-4',
                        visibleColumns.length === 5 ? 'grid-cols-5' : 'grid-cols-4',
                    )}
                >
                    {visibleColumns.map((column) => {
                        const columnTickets = tickets.filter((ticket) => ticket.status === column.status);

                        return (
                            <section
                                key={column.status}
                                className="flex min-h-64 flex-col rounded-xl bg-zinc-100/70 p-2.5"
                                aria-label={column.label}
                            >
                                <header className="mb-3 flex items-center gap-2 px-1">
                                    <span className={cn('size-2 rounded-full', column.accent)} />
                                    <h2 className="text-sm font-semibold text-zinc-900">{column.label}</h2>
                                    <span className="ml-auto rounded-full bg-white px-1.5 text-xs font-medium text-zinc-600 tabular-nums ring-1 ring-zinc-200">
                                        {columnTickets.length}
                                    </span>
                                </header>
                                {(column.status === 'done' || column.status === 'cancelled') && (
                                    <p className="text-muted-foreground mb-2 px-1 text-xs">
                                        {closedVisibleDays} hari terakhir
                                    </p>
                                )}
                                <div className="flex flex-1 flex-col gap-2">
                                    {columnTickets.length === 0 ? (
                                        <p className="text-muted-foreground py-6 text-center text-xs">Kosong</p>
                                    ) : (
                                        columnTickets.map((ticket) => (
                                            <TicketCard
                                                key={ticket.id}
                                                ticket={ticket}
                                                canUpdate={can.update}
                                                onMove={(status) => move(ticket, status)}
                                                onCancel={() => cancel(ticket)}
                                            />
                                        ))
                                    )}
                                </div>
                            </section>
                        );
                    })}
                </div>
            </div>

            {can.create && (
                <CreateTicketDialog
                    open={createOpen}
                    onOpenChange={setCreateOpen}
                    types={types}
                    priorities={priorities}
                    handledBy={handledBy}
                />
            )}
        </>
    );
}

function TicketCard({
    ticket,
    canUpdate,
    onMove,
    onCancel,
}: {
    ticket: MaintenanceCard;
    canUpdate: boolean;
    onMove: (status: MaintenanceStatus) => void;
    onCancel: () => void;
}) {
    const isOpen = ['reported', 'in_progress', 'waiting_parts'].includes(ticket.status);
    const moves = (
        [
            ['in_progress', 'Pindah ke Dikerjakan'],
            ['waiting_parts', 'Pindah ke Menunggu Sparepart'],
        ] as const
    ).filter(([status]) => status !== ticket.status);

    return (
        <article className="border-border bg-card rounded-lg border p-3 shadow-xs transition-colors hover:border-zinc-300">
            <div className="flex items-start justify-between gap-2">
                <Link
                    href={`/maintenance/${ticket.id}`}
                    className="font-tag text-xs font-semibold text-zinc-500 hover:underline"
                >
                    {ticket.ticket_number}
                </Link>
                <div className="flex items-center gap-1">
                    <PriorityBadge priority={ticket.priority} label={ticket.priority_label} />
                    {canUpdate && isOpen && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button size="icon-xs" variant="ghost" aria-label="Aksi tiket">
                                    <EllipsisVertical className="size-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                {moves.map(([status, label]) => (
                                    <DropdownMenuItem key={status} onSelect={() => onMove(status)}>
                                        {label}
                                    </DropdownMenuItem>
                                ))}
                                <DropdownMenuItem asChild>
                                    <Link href={`/maintenance/${ticket.id}?complete=1`}>
                                        <CheckCircle2 /> Selesaikan…
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem variant="destructive" onSelect={onCancel}>
                                    Batalkan tiket
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>
            </div>
            <Link
                href={`/maintenance/${ticket.id}`}
                className="mt-1 block text-sm font-medium text-zinc-800 hover:underline"
            >
                {ticket.title}
            </Link>
            <p className="text-muted-foreground mt-1 text-xs">
                <Link href={`/assets/${ticket.asset.id}`} className="font-tag hover:underline">
                    {ticket.asset.asset_tag}
                </Link>
                {ticket.asset.model && ` · ${ticket.asset.model}`}
            </p>
            {ticket.asset.holder && <p className="text-muted-foreground text-xs">Pengguna: {ticket.asset.holder}</p>}
            <div className="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-zinc-500">
                <span>{ticket.type_label}</span>
                <span>·</span>
                <span>{ticket.supplier ?? ticket.handled_by_label}</span>
                {ticket.status === 'done' ? (
                    <span className="ml-auto">
                        <DateDisplay value={ticket.completed_at} />
                    </span>
                ) : (
                    ticket.target_date && (
                        <span className={cn('ml-auto', ticket.is_overdue && 'font-semibold text-red-600')}>
                            Target <DateDisplay value={ticket.target_date} />
                        </span>
                    )
                )}
            </div>
        </article>
    );
}
