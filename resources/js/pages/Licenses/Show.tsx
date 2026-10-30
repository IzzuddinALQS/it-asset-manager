import { Head, Link, router, useForm } from '@inertiajs/react';
import { KeyRound, Laptop, Pencil, User, UserPlus, X } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import ConfirmDialog from '@/components/ConfirmDialog';
import DateDisplay from '@/components/DateDisplay';
import EmptyState from '@/components/EmptyState';
import FormField from '@/components/FormField';
import ExpiryBadge from '@/components/licenses/ExpiryBadge';
import SeatMeter from '@/components/licenses/SeatMeter';
import SearchSelect from '@/components/SearchSelect';
import PageHeader from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout';
import { cn, formatCurrency, formatDate } from '@/lib/utils';
import type { LicenseDetail, LicenseSeatRow } from '@/types/licenses';

export default function LicenseShow({
    license,
    warningDays,
    can,
}: {
    license: LicenseDetail;
    warningDays: number;
    can: { update: boolean; assignSeat: boolean };
}) {
    const [assignOpen, setAssignOpen] = useState(false);
    const [releasing, setReleasing] = useState<LicenseSeatRow | null>(null);
    const [releaseProcessing, setReleaseProcessing] = useState(false);
    const activeSeats = license.seats.filter((seat) => seat.released_at === null);
    const pastSeats = license.seats.filter((seat) => seat.released_at !== null);
    const isFull = license.seats_used >= license.seats_total;

    const release = (): void => {
        if (releasing === null) {
            return;
        }

        router.post(`/license-seats/${releasing.id}/release`, undefined, {
            preserveScroll: true,
            onStart: () => setReleaseProcessing(true),
            onFinish: () => {
                setReleaseProcessing(false);
                setReleasing(null);
            },
        });
    };

    return (
        <AppLayout>
            <Head title={license.name} />

            <PageHeader
                title={license.name}
                description={[license.manufacturer, license.license_type_label, license.category]
                    .filter(Boolean)
                    .join(' · ')}
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Lisensi', href: '/licenses' },
                    { label: license.name },
                ]}
                actions={
                    <div className="flex flex-wrap justify-end gap-2">
                        {can.assignSeat && (
                            <Button onClick={() => setAssignOpen(true)} disabled={isFull}>
                                <UserPlus /> Beri kuota
                            </Button>
                        )}
                        {can.update && (
                            <Button variant="outline" asChild>
                                <Link href={`/licenses/${license.id}/edit`}>
                                    <Pencil /> Edit
                                </Link>
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="mb-6 grid gap-4 lg:grid-cols-3">
                <section className="border-border bg-card rounded-xl border p-5 lg:col-span-2">
                    <SeatMeter used={license.seats_used} total={license.seats_total} />
                    <p className="text-muted-foreground mt-2 text-xs">
                        {isFull
                            ? 'Semua kuota terpakai. Lepas kuota atau tambah jumlah kuota untuk memberi kuota baru.'
                            : `${license.seats_total - license.seats_used} seat tersedia.`}
                    </p>
                    <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                        <Detail label="Masa berlaku">
                            <ExpiryBadge expiresAt={license.expires_at} warningDays={warningDays} />
                        </Detail>
                        <Detail label="Biaya tahunan">
                            {license.annual_cost === null ? '—' : formatCurrency(license.annual_cost)}
                        </Detail>
                        <Detail label="Product key">
                            {license.product_key_masked === null ? (
                                '—'
                            ) : (
                                <span className="font-mono tracking-wider">{license.product_key_masked}</span>
                            )}
                        </Detail>
                        <Detail label="Supplier">{license.supplier ?? '—'}</Detail>
                        <Detail label="Nomor PO">{license.purchase_order ?? '—'}</Detail>
                        <Detail label="Tanggal pembelian">{formatDate(license.purchase_date)}</Detail>
                    </dl>
                </section>
                <section className="border-border bg-card rounded-xl border p-5">
                    <h2 className="mb-2 text-[15px] font-semibold text-zinc-950">Catatan</h2>
                    <p className="text-sm whitespace-pre-line text-zinc-600">{license.notes || '—'}</p>
                </section>
            </div>

            <section className="border-border bg-card mb-6 rounded-xl border p-5">
                <h2 className="mb-3 text-[15px] font-semibold text-zinc-950">Kuota aktif ({activeSeats.length})</h2>
                {activeSeats.length === 0 ? (
                    <EmptyState icon={KeyRound} title="Belum ada kuota yang diberikan" />
                ) : (
                    <ul className="divide-border divide-y">
                        {activeSeats.map((seat) => (
                            <li key={seat.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                                <SeatHolder seat={seat} />
                                <div className="flex items-center gap-3">
                                    <span className="text-muted-foreground text-xs">
                                        Sejak <DateDisplay value={seat.assigned_at} />
                                    </span>
                                    {can.assignSeat && (
                                        <Button size="sm" variant="outline" onClick={() => setReleasing(seat)}>
                                            <X /> Lepas
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            {pastSeats.length > 0 && (
                <section className="border-border bg-card rounded-xl border p-5">
                    <h2 className="mb-3 text-[15px] font-semibold text-zinc-950">Riwayat kuota</h2>
                    <ul className="divide-border divide-y">
                        {pastSeats.map((seat) => (
                            <li key={seat.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                                <SeatHolder seat={seat} />
                                <span className="text-muted-foreground text-xs">
                                    <DateDisplay value={seat.assigned_at} /> – <DateDisplay value={seat.released_at} />
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <AssignSeatDialog open={assignOpen} onOpenChange={setAssignOpen} license={license} />

            <ConfirmDialog
                open={releasing !== null}
                onOpenChange={(open) => !open && setReleasing(null)}
                onConfirm={release}
                processing={releaseProcessing}
                title="Lepas kuota?"
                description={
                    releasing === null
                        ? undefined
                        : `Kuota ${license.name} milik ${holderName(releasing)} dikembalikan ke lisensi.`
                }
                confirmLabel="Lepas kuota"
                destructive
            />
        </AppLayout>
    );
}

function holderName(seat: LicenseSeatRow): string {
    return seat.employee?.name ?? seat.asset?.asset_tag ?? '—';
}

function Detail({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div>
            <dt className="text-muted-foreground text-[13px]">{label}</dt>
            <dd className="mt-1 font-medium text-zinc-900">{children}</dd>
        </div>
    );
}

function SeatHolder({ seat }: { seat: LicenseSeatRow }) {
    const Icon = seat.holder_type === 'employee' ? User : Laptop;
    const detail =
        seat.employee !== null
            ? [seat.employee.employee_number, seat.employee.department].filter(Boolean).join(' · ')
            : (seat.asset?.model ?? '');

    return (
        <div className="flex items-center gap-3">
            <span className="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500">
                <Icon className="size-4" />
            </span>
            <div>
                {seat.asset !== null ? (
                    <Link
                        href={`/assets/${seat.asset.id}`}
                        className="text-sm font-medium text-zinc-800 hover:underline"
                    >
                        {seat.asset.asset_tag}
                    </Link>
                ) : (
                    <p className="text-sm font-medium text-zinc-800">{seat.employee?.name ?? '—'}</p>
                )}
                <p className="text-muted-foreground text-xs">{detail}</p>
            </div>
        </div>
    );
}

interface AssignForm {
    holder_type: 'employee' | 'asset';
    employee_id: number | null;
    asset_id: number | null;
}

function AssignSeatDialog({
    open,
    onOpenChange,
    license,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    license: LicenseDetail;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<AssignForm>({
        holder_type: 'employee',
        employee_id: null,
        asset_id: null,
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
        post(`/licenses/${license.id}/seats`, { preserveScroll: true, onSuccess: () => changeOpen(false) });
    };

    const isEmployee = data.holder_type === 'employee';
    const field = isEmployee ? 'employee_id' : 'asset_id';
    const selected = data[field];

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>Beri kuota {license.name}</DialogTitle>
                        <DialogDescription>
                            {license.seats_total - license.seats_used} dari {license.seats_total} seat tersedia.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Penerima kuota">
                        {(
                            [
                                ['employee', 'Karyawan', User],
                                ['asset', 'Inventaris', Laptop],
                            ] as const
                        ).map(([value, label, Icon]) => (
                            <button
                                key={value}
                                type="button"
                                role="radio"
                                aria-checked={data.holder_type === value}
                                onClick={() => {
                                    setData('holder_type', value);
                                    clearErrors();
                                }}
                                className={cn(
                                    'flex items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors',
                                    data.holder_type === value
                                        ? 'border-primary bg-primary/5 text-primary'
                                        : 'border-border text-zinc-600 hover:bg-zinc-50',
                                )}
                            >
                                <Icon className="size-4" /> {label}
                            </button>
                        ))}
                    </div>

                    <FormField
                        label={isEmployee ? 'Karyawan' : 'Inventaris'}
                        htmlFor="holder_id"
                        error={errors[field]}
                        required
                    >
                        <SearchSelect
                            key={field}
                            id="holder_id"
                            url={isEmployee ? '/api/employees/search' : '/api/assets/search'}
                            value={selected === null ? null : String(selected)}
                            onChange={(value) => setData(field, value === null ? null : Number(value))}
                            placeholder={isEmployee ? 'Cari karyawan aktif' : 'Cari inventaris'}
                            searchPlaceholder={isEmployee ? 'Ketik nama atau nomor…' : 'Ketik tag, serial, atau model…'}
                            invalid={errors[field] !== undefined}
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => changeOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" loading={processing}>
                            Beri kuota
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
