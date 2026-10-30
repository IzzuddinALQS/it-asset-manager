import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, LogIn, LogOut, Package, Pencil } from 'lucide-react';
import { useState } from 'react';
import DateDisplay from '@/components/DateDisplay';
import EmptyState from '@/components/EmptyState';
import QtyDialog from '@/components/inventory/QtyDialog';
import PageHeader from '@/components/PageHeader';
import StatCard from '@/components/StatCard';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout';
import { formatCurrency } from '@/lib/utils';
import { isLowStock, type AccessoryDetail, type AccessoryLoan } from '@/types/inventory';

export default function AccessoryShow({
    accessory,
    can,
}: {
    accessory: AccessoryDetail;
    can: { update: boolean; checkout: boolean };
}) {
    const [checkoutOpen, setCheckoutOpen] = useState(false);
    const [returning, setReturning] = useState<AccessoryLoan | null>(null);
    const activeLoans = accessory.checkouts.filter((loan) => loan.checked_in_at === null);
    const pastLoans = accessory.checkouts.filter((loan) => loan.checked_in_at !== null);
    const lowStock = isLowStock(accessory);

    return (
        <AppLayout>
            <Head title={accessory.name} />

            <PageHeader
                title={accessory.name}
                description={[accessory.model, accessory.category, accessory.manufacturer].filter(Boolean).join(' · ')}
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Komponen & Aksesori', href: '/inventory?tab=accessories' },
                    { label: accessory.name },
                ]}
                actions={
                    <div className="flex flex-wrap justify-end gap-2">
                        {can.checkout && (
                            <Button onClick={() => setCheckoutOpen(true)} disabled={accessory.qty_available === 0}>
                                <LogOut /> Pinjamkan
                            </Button>
                        )}
                        {can.update && (
                            <Button variant="outline" asChild>
                                <Link href={`/inventory/accessories/${accessory.id}/edit`}>
                                    <Pencil /> Edit
                                </Link>
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <StatCard label="Total" value={accessory.qty_total} icon={Package} />
                <StatCard label="Dipinjam" value={accessory.qty_out} icon={LogOut} tone="info" />
                <StatCard
                    label="Tersedia"
                    value={accessory.qty_available}
                    icon={lowStock ? AlertTriangle : LogIn}
                    tone={lowStock ? 'warning' : 'success'}
                    hint={lowStock ? 'Stok menipis' : undefined}
                />
                <StatCard
                    label="Minimum"
                    value={accessory.min_qty}
                    hint={[
                        accessory.location,
                        accessory.unit_cost === null ? null : `${formatCurrency(accessory.unit_cost)}/unit`,
                    ]
                        .filter(Boolean)
                        .join(' · ')}
                />
            </div>

            <section className="border-border bg-card mb-6 rounded-xl border p-5">
                <h2 className="mb-3 text-[15px] font-semibold text-zinc-950">Sedang dipinjam ({activeLoans.length})</h2>
                {activeLoans.length === 0 ? (
                    <EmptyState icon={Package} title="Tidak ada yang sedang meminjam" />
                ) : (
                    <ul className="divide-border divide-y">
                        {activeLoans.map((loan) => (
                            <li key={loan.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                                <LoanSummary loan={loan} />
                                <div className="flex items-center gap-3">
                                    <DateDisplay value={loan.checked_out_at} className="text-xs" />
                                    {can.checkout && (
                                        <Button size="sm" variant="outline" onClick={() => setReturning(loan)}>
                                            <LogIn /> Kembalikan
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </section>

            {pastLoans.length > 0 && (
                <section className="border-border bg-card rounded-xl border p-5">
                    <h2 className="mb-3 text-[15px] font-semibold text-zinc-950">Riwayat pengembalian</h2>
                    <ul className="divide-border divide-y">
                        {pastLoans.map((loan) => (
                            <li key={loan.id} className="flex flex-wrap items-center justify-between gap-3 py-3">
                                <LoanSummary loan={loan} />
                                <span className="text-muted-foreground text-xs">
                                    Dikembalikan <DateDisplay value={loan.checked_in_at} />
                                </span>
                            </li>
                        ))}
                    </ul>
                </section>
            )}

            <QtyDialog
                open={checkoutOpen}
                onOpenChange={setCheckoutOpen}
                title={`Pinjamkan ${accessory.name}`}
                description="Stok tersedia berkurang sebanyak jumlah yang dipinjamkan."
                submitUrl={`/inventory/accessories/${accessory.id}/checkout`}
                submitLabel="Pinjamkan"
                maxQty={accessory.qty_available}
                target={{
                    field: 'employee_id',
                    label: 'Karyawan',
                    placeholder: 'Cari karyawan aktif',
                    searchUrl: '/api/employees/search',
                }}
            />

            {returning !== null && (
                <QtyDialog
                    key={returning.id}
                    open
                    onOpenChange={(open) => !open && setReturning(null)}
                    title={`Kembalikan dari ${returning.employee ?? 'karyawan'}`}
                    description="Isi jumlah lebih kecil untuk pengembalian sebagian; sisanya tetap tercatat dipinjam."
                    submitUrl={`/accessory-checkouts/${returning.id}/checkin`}
                    submitLabel="Kembalikan"
                    maxQty={returning.qty}
                    defaultQty={returning.qty}
                />
            )}
        </AppLayout>
    );
}

function LoanSummary({ loan }: { loan: AccessoryLoan }) {
    return (
        <div>
            <p className="text-sm font-medium text-zinc-800">
                {loan.qty}× · {loan.employee ?? '—'}
            </p>
            <p className="text-muted-foreground text-xs">
                {[loan.employee_number, loan.department, loan.note].filter(Boolean).join(' · ')}
            </p>
        </div>
    );
}
