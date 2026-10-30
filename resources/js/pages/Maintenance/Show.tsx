import { Head, Link, router, useForm } from '@inertiajs/react';
import { CheckCircle2, CircuitBoard, Hammer, PackageSearch, Plus, Trash2, X } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';
import CurrencyInput from '@/components/CurrencyInput';
import DateDisplay from '@/components/DateDisplay';
import EmptyState from '@/components/EmptyState';
import FormField from '@/components/FormField';
import PriorityBadge from '@/components/maintenance/PriorityBadge';
import SearchSelect from '@/components/SearchSelect';
import { optionsUrl } from '@/lib/options';
import PageHeader from '@/components/PageHeader';
import StatusBadge from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout';
import { useConfirm } from '@/lib/confirm';
import { cn, formatCurrency } from '@/lib/utils';
import type { MaintenanceDetail } from '@/types/maintenance';

interface ShowProps {
    maintenance: MaintenanceDetail;
    canReturnToHolder: boolean;
    can: { move: boolean; complete: boolean; cancel: boolean };
}

export default function MaintenanceShow(props: ShowProps) {
    return (
        <AppLayout>
            <MaintenanceDetailPage {...props} />
        </AppLayout>
    );
}

/** Rendered inside AppLayout so `useConfirm` has its provider. */
function MaintenanceDetailPage({ maintenance, canReturnToHolder, can }: ShowProps) {
    const confirm = useConfirm();
    const [completeOpen, setCompleteOpen] = useState(
        () => can.complete && new URLSearchParams(window.location.search).has('complete'),
    );

    const move = (status: 'in_progress' | 'waiting_parts'): void => {
        router.post(`/maintenance/${maintenance.id}/status`, { status }, { preserveScroll: true });
    };

    const cancel = async (): Promise<void> => {
        const confirmed = await confirm({
            title: `Batalkan ${maintenance.ticket_number}?`,
            description: `${maintenance.asset.asset_tag} dikembalikan ke status sebelum maintenance.`,
            confirmLabel: 'Batalkan tiket',
            destructive: true,
        });

        if (confirmed) {
            router.post(`/maintenance/${maintenance.id}/cancel`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={maintenance.ticket_number} />

            <PageHeader
                title={maintenance.title}
                description={`${maintenance.ticket_number} · ${maintenance.type_label}`}
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Maintenance', href: '/maintenance' },
                    { label: maintenance.ticket_number },
                ]}
                actions={
                    <div className="flex flex-wrap justify-end gap-2">
                        {can.move && maintenance.status !== 'in_progress' && (
                            <Button variant="outline" onClick={() => move('in_progress')}>
                                <Hammer /> Kerjakan
                            </Button>
                        )}
                        {can.move && maintenance.status !== 'waiting_parts' && (
                            <Button variant="outline" onClick={() => move('waiting_parts')}>
                                <PackageSearch /> Menunggu sparepart
                            </Button>
                        )}
                        {can.complete && (
                            <Button onClick={() => setCompleteOpen(true)}>
                                <CheckCircle2 /> Selesaikan
                            </Button>
                        )}
                        {can.cancel && (
                            <Button
                                variant="outline"
                                className="text-destructive hover:text-destructive"
                                onClick={cancel}
                            >
                                <X /> Batalkan
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="border-border bg-card mb-6 flex flex-wrap items-center gap-3 rounded-xl border px-5 py-4">
                <StatusBadge status={maintenance.status} label={maintenance.status_label} className="text-sm" />
                <PriorityBadge priority={maintenance.priority} label={`Prioritas ${maintenance.priority_label}`} />
                {maintenance.completed_at && (
                    <p className="text-muted-foreground text-sm">
                        Selesai <DateDisplay value={maintenance.completed_at} withTime />
                    </p>
                )}
            </div>

            <div className="grid gap-6 lg:grid-cols-3 [&>*]:min-w-0">
                <div className="space-y-6 lg:col-span-2">
                    <Panel title="Detail tiket">
                        <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                            <Detail label="Ditangani oleh">
                                {maintenance.handled_by_label}
                                {maintenance.supplier && ` · ${maintenance.supplier}`}
                            </Detail>
                            <Detail label="Pelapor">{maintenance.reported_by ?? '—'}</Detail>
                            <Detail label="Mulai">
                                <DateDisplay value={maintenance.start_date} />
                            </Detail>
                            <Detail label="Target selesai">
                                <DateDisplay value={maintenance.target_date} />
                            </Detail>
                            <Detail label="Estimasi biaya">
                                {maintenance.estimated_cost === null ? '—' : formatCurrency(maintenance.estimated_cost)}
                            </Detail>
                            <Detail label="Biaya aktual">
                                {maintenance.actual_cost === null ? '—' : formatCurrency(maintenance.actual_cost)}
                            </Detail>
                            {maintenance.plan && <Detail label="Jadwal preventif">{maintenance.plan}</Detail>}
                            <Detail label="Dibuat oleh">
                                {maintenance.created_by ?? '—'}
                                {maintenance.created_at && (
                                    <span className="text-muted-foreground">
                                        {' · '}
                                        <DateDisplay value={maintenance.created_at} />
                                    </span>
                                )}
                            </Detail>
                            {maintenance.description && (
                                <Detail label="Deskripsi" className="sm:col-span-2">
                                    <span className="whitespace-pre-line">{maintenance.description}</span>
                                </Detail>
                            )}
                        </dl>
                    </Panel>

                    <Panel title={`Komponen terpakai (${maintenance.components.length})`}>
                        {maintenance.components.length === 0 ? (
                            <EmptyState icon={CircuitBoard} title="Belum ada komponen dari tiket ini" />
                        ) : (
                            <ul className="divide-border divide-y">
                                {maintenance.components.map((component) => (
                                    <li key={component.id} className="flex items-center justify-between gap-3 py-3">
                                        <div>
                                            <p className="text-sm font-medium text-zinc-800">
                                                {component.qty}× {component.name}
                                            </p>
                                            {component.spec && (
                                                <p className="text-muted-foreground text-xs">{component.spec}</p>
                                            )}
                                        </div>
                                        <DateDisplay value={component.installed_at} className="text-xs" />
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>

                <Panel title="Inventaris">
                    <Link
                        href={`/assets/${maintenance.asset.id}`}
                        className="font-tag text-base font-semibold text-zinc-900 hover:underline"
                    >
                        {maintenance.asset.asset_tag}
                    </Link>
                    <p className="text-muted-foreground text-sm">
                        {[maintenance.asset.model, maintenance.asset.category].filter(Boolean).join(' · ')}
                    </p>
                    <dl className="mt-4 space-y-3">
                        <Detail label="Status saat ini">
                            <StatusBadge status={maintenance.asset.status} label={maintenance.asset.status_label} />
                        </Detail>
                        <Detail label="Status sebelum maintenance">
                            {maintenance.asset_status_before ? (
                                <StatusBadge
                                    status={maintenance.asset_status_before}
                                    label={maintenance.asset_status_before_label ?? undefined}
                                />
                            ) : (
                                '—'
                            )}
                        </Detail>
                        <Detail label="Pengguna">{maintenance.asset.holder ?? '—'}</Detail>
                        <Detail label="Garansi sampai">
                            <DateDisplay value={maintenance.asset.warranty_until} relative />
                        </Detail>
                    </dl>
                </Panel>
            </div>

            {can.complete && (
                <CompleteDialog
                    open={completeOpen}
                    onOpenChange={setCompleteOpen}
                    maintenance={maintenance}
                    canReturnToHolder={canReturnToHolder}
                />
            )}
        </>
    );
}

interface CompleteForm {
    return_status: 'ready' | 'deployed';
    actual_cost: number | null;
    components: { component_id: number | null; qty: number }[];
    note: string;
}

function CompleteDialog({
    open,
    onOpenChange,
    maintenance,
    canReturnToHolder,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    maintenance: MaintenanceDetail;
    canReturnToHolder: boolean;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<CompleteForm>({
        return_status: canReturnToHolder ? 'deployed' : 'ready',
        actual_cost: maintenance.estimated_cost === null ? null : Math.round(Number(maintenance.estimated_cost)),
        components: [],
        note: '',
    });

    const changeOpen = (next: boolean): void => {
        if (!next) {
            reset();
            clearErrors();
        }

        onOpenChange(next);
    };

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        post(`/maintenance/${maintenance.id}/complete`, { preserveScroll: true, onSuccess: () => changeOpen(false) });
    };

    const updateComponent = (index: number, changes: Partial<CompleteForm['components'][number]>): void => {
        setData(
            'components',
            data.components.map((row, rowIndex) => (rowIndex === index ? { ...row, ...changes } : row)),
        );
    };

    const fieldErrors = errors as Record<string, string | undefined>;

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>Selesaikan {maintenance.ticket_number}</DialogTitle>
                        <DialogDescription>
                            Catat biaya dan komponen yang dipakai, lalu tentukan status {maintenance.asset.asset_tag}{' '}
                            setelah maintenance.
                        </DialogDescription>
                    </DialogHeader>

                    <FormField label="Inventaris kembali ke" error={errors.return_status} required>
                        <div className="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Status inventaris">
                            {(
                                [
                                    ['deployed', 'Pengguna', maintenance.asset.holder ?? 'tidak ada pemegang'],
                                    ['ready', 'Siap Guna', 'kembali ke gudang IT'],
                                ] as const
                            ).map(([value, label, hint]) => {
                                const disabled = value === 'deployed' && !canReturnToHolder;

                                return (
                                    <button
                                        key={value}
                                        type="button"
                                        role="radio"
                                        aria-checked={data.return_status === value}
                                        disabled={disabled}
                                        onClick={() => setData('return_status', value)}
                                        className={cn(
                                            'rounded-lg border px-3 py-2 text-left text-sm transition-colors disabled:cursor-not-allowed disabled:opacity-50',
                                            data.return_status === value
                                                ? 'border-primary bg-primary/5 text-primary'
                                                : 'border-border text-zinc-600 hover:bg-zinc-50',
                                        )}
                                    >
                                        <span className="block font-medium">{label}</span>
                                        <span className="text-muted-foreground block text-xs">{hint}</span>
                                    </button>
                                );
                            })}
                        </div>
                    </FormField>

                    <FormField label="Biaya aktual" htmlFor="actual_cost" error={errors.actual_cost}>
                        <CurrencyInput
                            id="actual_cost"
                            value={data.actual_cost}
                            onChange={(value) => setData('actual_cost', value)}
                        />
                    </FormField>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <p className="text-sm font-medium text-zinc-700">Komponen terpakai</p>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                onClick={() =>
                                    setData('components', [...data.components, { component_id: null, qty: 1 }])
                                }
                            >
                                <Plus /> Tambah
                            </Button>
                        </div>
                        {data.components.length === 0 && (
                            <p className="text-muted-foreground text-xs">
                                Tidak ada. Komponen yang ditambahkan diambil dari stok dan dipasang ke inventaris.
                            </p>
                        )}
                        {data.components.map((row, index) => (
                            <div key={index} className="flex items-start gap-2">
                                <div className="min-w-0 flex-1">
                                    <SearchSelect
                                        url={optionsUrl('components')}
                                        value={row.component_id === null ? null : String(row.component_id)}
                                        onChange={(value) =>
                                            updateComponent(index, {
                                                component_id: value === null ? null : Number(value),
                                            })
                                        }
                                        placeholder="Cari komponen"
                                        invalid={fieldErrors[`components.${index}.component_id`] !== undefined}
                                    />
                                    {fieldErrors[`components.${index}.component_id`] && (
                                        <p className="text-destructive mt-1 text-xs">
                                            {fieldErrors[`components.${index}.component_id`]}
                                        </p>
                                    )}
                                </div>
                                <Input
                                    type="number"
                                    min={1}
                                    value={row.qty}
                                    onChange={(event) => updateComponent(index, { qty: Number(event.target.value) })}
                                    className="w-20"
                                    aria-label="Jumlah"
                                />
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    aria-label="Hapus komponen"
                                    onClick={() =>
                                        setData(
                                            'components',
                                            data.components.filter((_, rowIndex) => rowIndex !== index),
                                        )
                                    }
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        ))}
                        {errors.components && (
                            <p className="text-destructive text-xs" role="alert">
                                {errors.components}
                            </p>
                        )}
                    </div>

                    <FormField label="Catatan penyelesaian" htmlFor="complete_note" error={errors.note}>
                        <Textarea
                            id="complete_note"
                            rows={2}
                            value={data.note}
                            onChange={(event) => setData('note', event.target.value)}
                            placeholder="Tindakan yang dilakukan, hasil pengujian…"
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => changeOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" loading={processing}>
                            Selesaikan tiket
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function Panel({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section className="border-border bg-card rounded-xl border p-5">
            <h2 className="mb-4 text-[15px] font-semibold text-zinc-950">{title}</h2>
            {children}
        </section>
    );
}

function Detail({ label, className, children }: { label: string; className?: string; children: ReactNode }) {
    return (
        <div className={className}>
            <dt className="text-muted-foreground text-[13px]">{label}</dt>
            <dd className="mt-1 text-sm font-medium text-zinc-900">{children}</dd>
        </div>
    );
}
