import { Head, router, useForm } from '@inertiajs/react';
import { CalendarRange, Pencil, Play, Plus } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import DateDisplay from '@/components/DateDisplay';
import EmptyState from '@/components/EmptyState';
import FormField from '@/components/FormField';
import SearchSelect from '@/components/SearchSelect';
import { optionsUrl } from '@/lib/options';
import { ToneBadge } from '@/components/StatusBadge';
import PageHeader from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout';
import { useConfirm } from '@/lib/confirm';
import { cn, relativeDays } from '@/lib/utils';
import type { MaintenancePlanRow } from '@/types/maintenance';

interface PlansProps {
    plans: MaintenancePlanRow[];
    warningDays: number;
    can: { manage: boolean };
}

export default function MaintenancePlans(props: PlansProps) {
    return (
        <AppLayout>
            <PlansPage {...props} />
        </AppLayout>
    );
}

function daysUntil(date: string): number {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    return Math.round((new Date(`${date}T00:00:00`).getTime() - today.getTime()) / 86_400_000);
}

/** Rendered inside AppLayout so `useConfirm` has its provider. */
function PlansPage({ plans, warningDays, can }: PlansProps) {
    const confirm = useConfirm();
    const [editing, setEditing] = useState<MaintenancePlanRow | 'new' | null>(null);

    const run = async (plan: MaintenancePlanRow): Promise<void> => {
        const confirmed = await confirm({
            title: `Jalankan ${plan.name}?`,
            description: `${plan.covered_assets} inventaris yang tercakup akan dibuatkan tiket preventif dan berstatus Perbaikan. Jadwal maju ${plan.interval_months} bulan.`,
            confirmLabel: 'Buat tiket',
        });

        if (confirmed) {
            router.post(`/maintenance/plans/${plan.id}/run`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title="Jadwal preventif" />

            <PageHeader
                title="Jadwal preventif"
                description={`Perawatan berkala per kategori inventaris. Jadwal ditandai jatuh tempo mulai H-${warningDays}.`}
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Maintenance', href: '/maintenance' },
                    { label: 'Jadwal preventif' },
                ]}
                actions={
                    can.manage && (
                        <Button onClick={() => setEditing('new')}>
                            <Plus /> Tambah jadwal
                        </Button>
                    )
                }
            />

            {plans.length === 0 ? (
                <EmptyState icon={CalendarRange} title="Belum ada jadwal preventif" />
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {plans.map((plan) => {
                        const daysLeft = daysUntil(plan.next_due_date);
                        const isDue = plan.is_active && daysLeft <= warningDays;

                        return (
                            <article
                                key={plan.id}
                                className={cn(
                                    'border-border bg-card flex flex-col rounded-xl border p-5',
                                    !plan.is_active && 'opacity-60',
                                    isDue && 'border-amber-300',
                                )}
                            >
                                <div className="flex items-start justify-between gap-2">
                                    <h2 className="text-[15px] font-semibold text-zinc-950">{plan.name}</h2>
                                    {!plan.is_active ? (
                                        <ToneBadge tone="neutral">Nonaktif</ToneBadge>
                                    ) : (
                                        isDue && (
                                            <ToneBadge tone={daysLeft < 0 ? 'danger' : 'warning'}>
                                                {daysLeft < 0 ? 'Terlambat' : 'Jatuh tempo'}
                                            </ToneBadge>
                                        )
                                    )}
                                </div>
                                <p className="text-muted-foreground mt-1 text-sm">
                                    {[plan.category, plan.location ?? 'Semua lokasi'].filter(Boolean).join(' · ')}
                                </p>
                                <dl className="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <dt className="text-muted-foreground text-[13px]">Berikutnya</dt>
                                        <dd className="font-medium text-zinc-900">
                                            <DateDisplay value={plan.next_due_date} />
                                            <span className="text-muted-foreground block text-xs">
                                                {relativeDays(plan.next_due_date)}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-[13px]">Interval</dt>
                                        <dd className="font-medium text-zinc-900">{plan.interval_months} bulan</dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-[13px]">Inventaris tercakup</dt>
                                        <dd className="font-medium text-zinc-900">
                                            {plan.covered_assets}
                                            {plan.open_tickets > 0 && (
                                                <span className="text-muted-foreground text-xs">
                                                    {' '}
                                                    · {plan.open_tickets} tiket terbuka
                                                </span>
                                            )}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-[13px]">PIC / vendor</dt>
                                        <dd className="font-medium text-zinc-900">
                                            {plan.supplier ?? plan.pic ?? '—'}
                                        </dd>
                                    </div>
                                </dl>
                                {can.manage && (
                                    <div className="mt-auto flex justify-end gap-2 pt-4">
                                        <Button size="sm" variant="outline" onClick={() => setEditing(plan)}>
                                            <Pencil /> Edit
                                        </Button>
                                        <Button
                                            size="sm"
                                            onClick={() => run(plan)}
                                            disabled={!plan.is_active || plan.covered_assets === 0}
                                        >
                                            <Play /> Buat tiket
                                        </Button>
                                    </div>
                                )}
                            </article>
                        );
                    })}
                </div>
            )}

            {editing !== null && (
                <PlanDialog
                    key={editing === 'new' ? 'new' : editing.id}
                    plan={editing === 'new' ? null : editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </>
    );
}

interface PlanForm {
    name: string;
    category_id: number | null;
    location_id: number | null;
    interval_months: number;
    next_due_date: string;
    pic: string;
    supplier_id: number | null;
    is_active: boolean;
}

function PlanDialog({ plan, onClose }: { plan: MaintenancePlanRow | null; onClose: () => void }) {
    const { data, setData, post, put, processing, errors } = useForm<PlanForm>({
        name: plan?.name ?? '',
        category_id: plan?.category_id ?? null,
        location_id: plan?.location_id ?? null,
        interval_months: plan?.interval_months ?? 6,
        next_due_date: plan?.next_due_date ?? '',
        pic: plan?.pic ?? '',
        supplier_id: plan?.supplier_id ?? null,
        is_active: plan?.is_active ?? true,
    });

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: onClose };

        if (plan === null) {
            post('/maintenance/plans', options);
        } else {
            put(`/maintenance/plans/${plan.id}`, options);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>{plan === null ? 'Tambah jadwal preventif' : `Edit ${plan.name}`}</DialogTitle>
                        <DialogDescription>
                            Jadwal mencakup semua inventaris aktif di kategori (dan lokasi) yang dipilih.
                        </DialogDescription>
                    </DialogHeader>

                    <FormField label="Nama jadwal" htmlFor="plan_name" error={errors.name} required>
                        <Input
                            id="plan_name"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            maxLength={150}
                        />
                    </FormField>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField
                            label="Kategori inventaris"
                            htmlFor="category_id"
                            error={errors.category_id}
                            required
                        >
                            <SearchSelect
                                id="category_id"
                                url={optionsUrl('categories')}
                                params={{ category_type: 'asset' }}
                                value={data.category_id === null ? null : String(data.category_id)}
                                onChange={(value) => setData('category_id', value === null ? null : Number(value))}
                                placeholder="Cari kategori"
                                invalid={errors.category_id !== undefined}
                            />
                        </FormField>
                        <FormField label="Lokasi" htmlFor="location_id" error={errors.location_id}>
                            <SearchSelect
                                id="location_id"
                                url={optionsUrl('locations')}
                                value={data.location_id === null ? null : String(data.location_id)}
                                onChange={(value) => setData('location_id', value === null ? null : Number(value))}
                                emptyLabel="Semua lokasi"
                            />
                        </FormField>
                        <FormField
                            label="Interval (bulan)"
                            htmlFor="interval_months"
                            error={errors.interval_months}
                            required
                        >
                            <Input
                                id="interval_months"
                                type="number"
                                min={1}
                                max={60}
                                value={data.interval_months}
                                onChange={(event) => setData('interval_months', Number(event.target.value))}
                            />
                        </FormField>
                        <FormField
                            label="Jatuh tempo berikutnya"
                            htmlFor="next_due_date"
                            error={errors.next_due_date}
                            required
                        >
                            <Input
                                id="next_due_date"
                                type="date"
                                value={data.next_due_date}
                                onChange={(event) => setData('next_due_date', event.target.value)}
                            />
                        </FormField>
                        <FormField label="PIC" htmlFor="pic" error={errors.pic}>
                            <Input id="pic" value={data.pic} onChange={(event) => setData('pic', event.target.value)} />
                        </FormField>
                        <FormField label="Vendor" htmlFor="plan_supplier_id" error={errors.supplier_id}>
                            <SearchSelect
                                id="plan_supplier_id"
                                url={optionsUrl('suppliers')}
                                value={data.supplier_id === null ? null : String(data.supplier_id)}
                                onChange={(value) => setData('supplier_id', value === null ? null : Number(value))}
                                emptyLabel="Internal IT"
                            />
                        </FormField>
                    </div>

                    <label className="flex items-center gap-2 text-sm text-zinc-700">
                        <Checkbox
                            checked={data.is_active}
                            onCheckedChange={(checked) => setData('is_active', checked === true)}
                        />
                        Jadwal aktif
                    </label>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" loading={processing}>
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
