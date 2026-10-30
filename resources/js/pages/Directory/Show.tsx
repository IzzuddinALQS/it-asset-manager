import { Head, Link } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { HardDrive, KeyRound, Package } from 'lucide-react';
import DateDisplay from '@/components/DateDisplay';
import PageHeader from '@/components/PageHeader';
import StatusBadge from '@/components/StatusBadge';
import AppLayout from '@/layouts/AppLayout';
import type { EmployeeDetail, HeldAccessory, HeldAsset, HeldLicense } from '@/types/directory';

export default function DirectoryShow({
    employee,
    assets,
    accessories,
    licenses,
}: {
    employee: EmployeeDetail;
    assets: HeldAsset[];
    accessories: HeldAccessory[];
    licenses: HeldLicense[];
}) {
    const details: [string, ReactNode][] = [
        ['NIK', <span className="font-tag">{employee.employee_number}</span>],
        ['Email', employee.email ?? '—'],
        ['Jabatan', employee.job_title ?? '—'],
        ['Departemen', employee.department ?? '—'],
        ['Lokasi', employee.location ?? '—'],
        [
            'Atasan',
            employee.manager ? (
                <Link href={`/directory/${employee.manager.id}`} className="hover:underline">
                    {employee.manager.name}
                </Link>
            ) : (
                '—'
            ),
        ],
        ['Status berlaku', <DateDisplay value={employee.status_effective_date} />],
        ['Sinkron HERD', <DateDisplay value={employee.synced_at} withTime />],
    ];

    return (
        <AppLayout>
            <Head title={employee.name} />

            <PageHeader
                title={employee.name}
                description={[employee.job_title, employee.department].filter(Boolean).join(' · ') || undefined}
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Direktori Karyawan', href: '/directory' },
                    { label: employee.name },
                ]}
                actions={<StatusBadge status={employee.employment_status} className="text-xs" />}
            />

            <div className="grid gap-6 lg:grid-cols-[320px_1fr] [&>*]:min-w-0">
                <section className="border-border bg-card h-fit rounded-xl border p-5">
                    <h2 className="mb-4 text-[15px] font-semibold text-zinc-950">Data karyawan</h2>
                    <dl className="space-y-3 text-sm">
                        {details.map(([label, value]) => (
                            <div key={label} className="flex justify-between gap-4">
                                <dt className="text-muted-foreground shrink-0">{label}</dt>
                                <dd className="min-w-0 text-right font-medium break-words text-zinc-900">{value}</dd>
                            </div>
                        ))}
                    </dl>
                </section>

                <div className="space-y-6">
                    <HoldingSection
                        icon={HardDrive}
                        title="Inventaris"
                        count={assets.length}
                        empty="Tidak memegang inventaris."
                    >
                        {assets.map((asset) => (
                            <li key={asset.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                <Link href={`/assets/${asset.id}`} className="min-w-0 hover:underline">
                                    <p className="font-tag text-sm font-semibold text-zinc-800">{asset.asset_tag}</p>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {[asset.model, asset.category, asset.serial_number].filter(Boolean).join(' · ')}
                                    </p>
                                </Link>
                                <div className="flex shrink-0 flex-col items-end gap-1">
                                    <StatusBadge status={asset.status} />
                                    <DateDisplay value={asset.assigned_at} className="text-muted-foreground text-xs" />
                                </div>
                            </li>
                        ))}
                    </HoldingSection>

                    <HoldingSection
                        icon={Package}
                        title="Aksesori"
                        count={accessories.length}
                        empty="Tidak memegang aksesori."
                    >
                        {accessories.map((checkout) => (
                            <li key={checkout.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                <Link
                                    href={`/inventory/accessories/${checkout.accessory_id}`}
                                    className="min-w-0 hover:underline"
                                >
                                    <p className="truncate text-sm font-medium text-zinc-800">{checkout.name}</p>
                                    <p className="text-muted-foreground truncate text-xs">{checkout.model}</p>
                                </Link>
                                <div className="shrink-0 text-right text-xs">
                                    <p className="font-semibold text-zinc-800 tabular-nums">{checkout.qty} unit</p>
                                    <DateDisplay value={checkout.checked_out_at} className="text-muted-foreground" />
                                </div>
                            </li>
                        ))}
                    </HoldingSection>

                    <HoldingSection
                        icon={KeyRound}
                        title="Lisensi"
                        count={licenses.length}
                        empty="Tidak memakai kuota lisensi."
                    >
                        {licenses.map((seat) => (
                            <li key={seat.id} className="flex items-center justify-between gap-3 px-5 py-3">
                                <Link href={`/licenses/${seat.license_id}`} className="min-w-0 hover:underline">
                                    <p className="truncate text-sm font-medium text-zinc-800">{seat.name}</p>
                                    <p className="text-muted-foreground text-xs">
                                        Berlaku hingga <DateDisplay value={seat.expires_at} />
                                    </p>
                                </Link>
                                <DateDisplay
                                    value={seat.assigned_at}
                                    className="text-muted-foreground shrink-0 text-xs"
                                />
                            </li>
                        ))}
                    </HoldingSection>
                </div>
            </div>
        </AppLayout>
    );
}

function HoldingSection({
    icon: Icon,
    title,
    count,
    empty,
    children,
}: {
    icon: typeof HardDrive;
    title: string;
    count: number;
    empty: string;
    children: ReactNode;
}) {
    return (
        <section className="border-border bg-card overflow-hidden rounded-xl border">
            <header className="border-border flex items-center gap-2 border-b px-5 py-3">
                <Icon className="size-4 text-zinc-500" />
                <h2 className="text-[15px] font-semibold text-zinc-950">{title}</h2>
                <span className="text-muted-foreground ml-auto text-xs tabular-nums">{count}</span>
            </header>
            {count === 0 ? (
                <p className="text-muted-foreground px-5 py-6 text-center text-xs">{empty}</p>
            ) : (
                <ul className="divide-border/70 divide-y">{children}</ul>
            )}
        </section>
    );
}
