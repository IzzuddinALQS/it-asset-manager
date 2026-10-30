import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    Archive,
    CircuitBoard,
    History,
    LogIn,
    LogOut,
    MapPin,
    Pencil,
    Download,
    ScrollText,
    Tag,
    User,
    Wrench,
} from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import CheckinDialog from '@/components/assets/CheckinDialog';
import CheckoutDialog from '@/components/assets/CheckoutDialog';
import QtyDialog from '@/components/inventory/QtyDialog';
import { optionsUrl } from '@/lib/options';
import RetireDialog from '@/components/assets/RetireDialog';
import CreateTicketDialog from '@/components/maintenance/CreateTicketDialog';
import DateDisplay from '@/components/DateDisplay';
import EmptyState from '@/components/EmptyState';
import PageHeader from '@/components/PageHeader';
import StatusBadge from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout';
import { useConfirm } from '@/lib/confirm';
import { formatCurrency } from '@/lib/utils';
import type { AssetDetail, EnumOption } from '@/types/assets';

interface Abilities {
    update: boolean;
    tag: boolean;
    checkout: boolean;
    checkin: boolean;
    retire: boolean;
    installComponent: boolean;
    sendToMaintenance: boolean;
}

export default function AssetsShow(props: {
    asset: AssetDetail;
    can: Abilities;
    retireReasons: EnumOption[];
    types: EnumOption[];
    priorities: EnumOption[];
    handledBy: EnumOption[];
}) {
    return (
        <AppLayout>
            <AssetDetailPage {...props} />
        </AppLayout>
    );
}

/** Rendered inside AppLayout so `useConfirm` has its provider. */
function AssetDetailPage({
    asset,
    can,
    retireReasons,
    types,
    priorities,
    handledBy,
}: {
    asset: AssetDetail;
    can: Abilities;
    retireReasons: EnumOption[];
    types: EnumOption[];
    priorities: EnumOption[];
    handledBy: EnumOption[];
}) {
    const confirm = useConfirm();
    const [dialog, setDialog] = useState<'checkout' | 'checkin' | 'retire' | 'install' | 'maintenance' | null>(null);
    const holderName = asset.holder?.name ?? asset.assigned_location?.name ?? null;

    const [selectedComponentStock, setSelectedComponentStock] = useState<number | undefined>(undefined);

    const removeComponent = async (installationId: number, name: string | null): Promise<void> => {
        const confirmed = await confirm({
            title: `Lepas ${name ?? 'komponen'}?`,
            description: 'Komponen dikembalikan ke stok dan dicatat di riwayat inventaris.',
            confirmLabel: 'Lepas',
            destructive: true,
        });

        if (confirmed) {
            router.post(`/component-installations/${installationId}/remove`, {}, { preserveScroll: true });
        }
    };

    const tagAsset = async (): Promise<void> => {
        const confirmed = await confirm({
            title: `Tandai ${asset.asset_tag} siap guna?`,
            description: 'Inventaris mendapat tag permanen sesuai kategorinya. Pastikan label QR sudah siap ditempel.',
            confirmLabel: 'Tandai siap guna',
        });

        if (confirmed) {
            router.post(`/assets/${asset.id}/tag`, {}, { preserveScroll: true });
        }
    };

    return (
        <>
            <Head title={asset.asset_tag} />

            <PageHeader
                title={asset.asset_tag}
                description={[asset.model, asset.category].filter(Boolean).join(' · ')}
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Inventaris', href: '/assets' },
                    { label: asset.asset_tag },
                ]}
                actions={
                    <div className="flex flex-wrap justify-end gap-2">
                        {can.tag && (
                            <Button onClick={tagAsset}>
                                <Tag /> Tandai siap guna
                            </Button>
                        )}
                        {can.checkout && (
                            <Button onClick={() => setDialog('checkout')}>
                                <LogOut /> Berikan
                            </Button>
                        )}
                        {can.checkin && (
                            <Button onClick={() => setDialog('checkin')}>
                                <LogIn /> Kembalikan
                            </Button>
                        )}
                        {can.sendToMaintenance && (
                            <Button variant="outline" onClick={() => setDialog('maintenance')}>
                                <Wrench /> Kirim ke maintenance
                            </Button>
                        )}
                        {asset.open_maintenance && (
                            <Button variant="outline" asChild>
                                <Link href={`/maintenance/${asset.open_maintenance.id}`}>
                                    <Wrench /> {asset.open_maintenance.ticket_number}
                                </Link>
                            </Button>
                        )}
                        {can.update && (
                            <Button variant="outline" asChild>
                                <Link href={`/assets/${asset.id}/edit`}>
                                    <Pencil /> Edit
                                </Link>
                            </Button>
                        )}
                        {can.retire && (
                            <Button
                                variant="outline"
                                className="text-destructive hover:text-destructive"
                                onClick={() => setDialog('retire')}
                            >
                                <Archive /> Pensiunkan
                            </Button>
                        )}
                    </div>
                }
            />

            <div className="border-border bg-card mb-6 flex flex-wrap items-center gap-3 rounded-xl border px-5 py-4">
                <StatusBadge status={asset.status} label={asset.status_label} className="text-sm" />
                <p className="text-muted-foreground text-sm">{asset.status_note}</p>
            </div>

            <div className="grid gap-6 lg:grid-cols-3 [&>*]:min-w-0">
                <div className="space-y-6 lg:col-span-2">
                    <Panel title="Ringkasan">
                        <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                            <Detail label="Model">
                                {asset.model ?? '—'}
                                {asset.model_number && (
                                    <span className="font-tag text-muted-foreground ml-1">({asset.model_number})</span>
                                )}
                            </Detail>
                            <Detail label="Kategori">{asset.category ?? '—'}</Detail>
                            <Detail label="Manufaktur">{asset.manufacturer ?? '—'}</Detail>
                            <Detail label="Nomor seri">
                                <span className="font-tag">{asset.serial_number ?? '—'}</span>
                            </Detail>
                            <Detail label="IMEI">
                                <span className="font-tag">{asset.imei ?? '—'}</span>
                            </Detail>
                            <Detail label="Nomor HP">
                                <span className="font-tag">{asset.phone_number ?? '—'}</span>
                            </Detail>
                            <Detail label="Lokasi fisik">{asset.location ?? '—'}</Detail>
                            <Detail label="Audit terakhir">
                                <DateDisplay value={asset.last_audited_at} />
                            </Detail>
                            {asset.notes && (
                                <Detail label="Catatan" className="sm:col-span-2">
                                    <span className="whitespace-pre-line">{asset.notes}</span>
                                </Detail>
                            )}
                        </dl>
                    </Panel>

                    <Panel title="Pengadaan & garansi">
                        <dl className="grid gap-x-6 gap-y-4 sm:grid-cols-2">
                            <Detail label="Supplier">{asset.supplier ?? '—'}</Detail>
                            <Detail label="Nomor PO">
                                <span className="font-tag">{asset.purchase_order ?? '—'}</span>
                            </Detail>
                            <Detail label="Tanggal pembelian">
                                <DateDisplay value={asset.purchase_date} />
                            </Detail>
                            <Detail label="Harga perolehan">
                                {asset.purchase_cost === null ? '—' : formatCurrency(asset.purchase_cost)}
                            </Detail>
                            <Detail label="Garansi sampai">
                                <DateDisplay value={asset.warranty_until} relative={asset.status !== 'retired'} />
                            </Detail>
                            <Detail label="Dicatat oleh">
                                {asset.created_by ?? '—'}
                                {asset.created_at && (
                                    <span className="text-muted-foreground">
                                        {' · '}
                                        <DateDisplay value={asset.created_at} />
                                    </span>
                                )}
                            </Detail>
                            {asset.retire_reason_label && (
                                <Detail label="Pensiun">
                                    {asset.retire_reason_label}
                                    {' · '}
                                    <DateDisplay value={asset.retired_at} />
                                </Detail>
                            )}
                        </dl>
                    </Panel>

                    <Tabs defaultValue="history" className="border-border bg-card gap-0 rounded-xl border">
                        <TabsList
                            variant="line"
                            className="border-border h-auto w-full scrollbar-thin justify-start gap-4 overflow-x-auto border-b px-5 pt-2 pb-0"
                        >
                            <TabsTrigger value="history">
                                <History /> Riwayat ({asset.logs.length})
                            </TabsTrigger>
                            <TabsTrigger value="components">
                                <CircuitBoard /> Komponen ({asset.components.length})
                            </TabsTrigger>
                            <TabsTrigger value="licenses">
                                <ScrollText /> Lisensi ({asset.licenses.length})
                            </TabsTrigger>
                            <TabsTrigger value="maintenance">
                                <Wrench /> Maintenance ({asset.maintenances.length})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="history" className="p-5">
                            <ol className="space-y-4">
                                {asset.logs.map((log) => (
                                    <li key={log.id} className="border-border relative border-l-2 pl-4">
                                        <span className="bg-primary absolute top-1.5 -left-[5px] size-2 rounded-full" />
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-semibold text-zinc-800">{log.action_label}</p>
                                            {log.to_status && log.from_status !== log.to_status && (
                                                <span className="flex items-center gap-1">
                                                    {log.from_status && (
                                                        <>
                                                            <StatusBadge
                                                                status={log.from_status}
                                                                label={log.from_status_label ?? undefined}
                                                            />
                                                            <ArrowRight className="size-3 text-zinc-400" />
                                                        </>
                                                    )}
                                                    <StatusBadge
                                                        status={log.to_status}
                                                        label={log.to_status_label ?? undefined}
                                                    />
                                                </span>
                                            )}
                                        </div>
                                        {log.note && <p className="mt-1 text-sm text-zinc-600">{log.note}</p>}
                                        <p className="text-muted-foreground mt-1 text-xs">
                                            <DateDisplay value={log.created_at} withTime />
                                            {(log.employee ?? log.location) && ` · ${log.employee ?? log.location}`}
                                            {log.performed_by && ` · oleh ${log.performed_by}`}
                                        </p>
                                    </li>
                                ))}
                            </ol>
                        </TabsContent>

                        <TabsContent value="components" className="p-5">
                            {can.installComponent && (
                                <div className="mb-3 flex justify-end">
                                    <Button size="sm" onClick={() => setDialog('install')}>
                                        <CircuitBoard /> Pasang komponen
                                    </Button>
                                </div>
                            )}
                            {asset.components.length === 0 ? (
                                <EmptyState icon={CircuitBoard} title="Belum ada komponen terpasang" />
                            ) : (
                                <ul className="divide-border divide-y">
                                    {asset.components.map((component) => (
                                        <li key={component.id} className="flex items-center justify-between gap-3 py-3">
                                            <div>
                                                <p className="text-sm font-medium text-zinc-800">
                                                    {component.qty}× {component.name}
                                                </p>
                                                <p className="text-muted-foreground text-xs">
                                                    {[component.spec, component.category].filter(Boolean).join(' · ')}
                                                    {component.is_factory && ' · bawaan pabrik'}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <DateDisplay value={component.installed_at} className="text-xs" />
                                                {can.installComponent && (
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => removeComponent(component.id, component.name)}
                                                    >
                                                        Lepas
                                                    </Button>
                                                )}
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </TabsContent>

                        <TabsContent value="licenses" className="p-5">
                            {asset.licenses.length === 0 ? (
                                <EmptyState icon={ScrollText} title="Tidak ada lisensi di perangkat ini" />
                            ) : (
                                <ul className="divide-border divide-y">
                                    {asset.licenses.map((license) => (
                                        <li key={license.id} className="flex items-center justify-between gap-3 py-3">
                                            <p className="text-sm font-medium text-zinc-800">{license.name}</p>
                                            <span className="text-muted-foreground text-xs">
                                                {license.expires_at ? (
                                                    <>
                                                        Berlaku s.d. <DateDisplay value={license.expires_at} />
                                                    </>
                                                ) : (
                                                    'Tanpa masa berlaku'
                                                )}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </TabsContent>

                        <TabsContent value="maintenance" className="p-5">
                            {asset.maintenances.length === 0 ? (
                                <EmptyState icon={Wrench} title="Belum pernah masuk maintenance" />
                            ) : (
                                <ul className="divide-border divide-y">
                                    {asset.maintenances.map((maintenance) => (
                                        <li
                                            key={maintenance.id}
                                            className="flex flex-wrap items-center justify-between gap-3 py-3"
                                        >
                                            <div>
                                                <Link
                                                    href={`/maintenance/${maintenance.id}`}
                                                    className="text-sm font-medium text-zinc-800 hover:underline"
                                                >
                                                    <span className="font-tag mr-2 text-zinc-500">
                                                        {maintenance.ticket_number}
                                                    </span>
                                                    {maintenance.title}
                                                </Link>
                                                <p className="text-muted-foreground text-xs">
                                                    {maintenance.type_label} ·{' '}
                                                    <DateDisplay value={maintenance.created_at} />
                                                </p>
                                            </div>
                                            <StatusBadge status={maintenance.status} label={maintenance.status_label} />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </TabsContent>
                    </Tabs>
                </div>

                <div className="space-y-6">
                    <Panel title="Pemegang saat ini">
                        {asset.holder ? (
                            <div className="flex items-start gap-3">
                                <span className="bg-primary/10 text-primary flex size-10 shrink-0 items-center justify-center rounded-full">
                                    <User className="size-5" />
                                </span>
                                <div className="min-w-0 space-y-0.5">
                                    <p className="font-semibold text-zinc-800">{asset.holder.name}</p>
                                    <p className="font-tag text-muted-foreground text-xs">
                                        {asset.holder.employee_number}
                                    </p>
                                    <p className="text-muted-foreground text-sm">
                                        {[asset.holder.job_title, asset.holder.department].filter(Boolean).join(' · ')}
                                    </p>
                                </div>
                            </div>
                        ) : asset.assigned_location ? (
                            <div className="flex items-center gap-3">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500">
                                    <MapPin className="size-5" />
                                </span>
                                <p className="font-semibold text-zinc-800">{asset.assigned_location.name}</p>
                            </div>
                        ) : (
                            <p className="text-muted-foreground text-sm">Tidak sedang dipegang siapa pun.</p>
                        )}

                        {(asset.assigned_at || asset.expected_return_date) && (
                            <dl className="border-border mt-4 space-y-3 border-t pt-4">
                                {asset.assigned_at && (
                                    <Detail label="Diserahkan">
                                        <DateDisplay value={asset.assigned_at} withTime />
                                    </Detail>
                                )}
                                {asset.expected_return_date && (
                                    <Detail label="Rencana kembali">
                                        <DateDisplay value={asset.expected_return_date} relative />
                                    </Detail>
                                )}
                            </dl>
                        )}
                    </Panel>

                    <AssetQrPanel assetTag={asset.asset_tag} model={asset.model} svg={asset.qr_svg} />
                </div>
            </div>

            <CheckoutDialog
                assetId={asset.id}
                assetTag={asset.asset_tag}
                open={dialog === 'checkout'}
                onOpenChange={(open) => setDialog(open ? 'checkout' : null)}
            />
            <CheckinDialog
                assetId={asset.id}
                assetTag={asset.asset_tag}
                holderName={holderName}
                open={dialog === 'checkin'}
                onOpenChange={(open) => setDialog(open ? 'checkin' : null)}
            />
            <RetireDialog
                assetId={asset.id}
                assetTag={asset.asset_tag}
                reasons={retireReasons}
                open={dialog === 'retire'}
                onOpenChange={(open) => setDialog(open ? 'retire' : null)}
            />
            {can.sendToMaintenance && (
                <CreateTicketDialog
                    open={dialog === 'maintenance'}
                    onOpenChange={(open) => setDialog(open ? 'maintenance' : null)}
                    asset={{ id: asset.id, asset_tag: asset.asset_tag }}
                    types={types}
                    priorities={priorities}
                    handledBy={handledBy}
                    warrantyUntil={asset.warranty_until}
                />
            )}
            <QtyDialog
                open={dialog === 'install'}
                onOpenChange={(open) => {
                    setDialog(open ? 'install' : null);
                    if (!open) {
                        setSelectedComponentStock(undefined);
                    }
                }}
                title={`Pasang komponen ke ${asset.asset_tag}`}
                description="Komponen dari stok IT mengurangi jumlah tersedia. Bawaan pabrik hanya dicatat, tidak memakai stok."
                submitUrl={`/assets/${asset.id}/components`}
                submitLabel="Pasang"
                maxQty={selectedComponentStock}
                target={{
                    field: 'component_id',
                    label: 'Komponen',
                    placeholder: 'Cari komponen',
                    searchUrl: optionsUrl('components'),
                    onChange: (_id, option) =>
                        setSelectedComponentStock(option?.meta?.qty_available as number | undefined),
                }}
                extra={({ isFactory, setIsFactory }) => (
                    <label className="flex items-center gap-2 text-sm text-zinc-700">
                        <Checkbox checked={isFactory} onCheckedChange={(checked) => setIsFactory(checked === true)} />
                        Bawaan pabrik (tidak mengurangi stok)
                    </label>
                )}
            />
        </>
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

function Detail({ label, children, className }: { label: string; children: ReactNode; className?: string }) {
    return (
        <div className={className}>
            <dt className="text-muted-foreground text-[13px]">{label}</dt>
            <dd className="mt-1 text-sm font-medium text-zinc-900">{children}</dd>
        </div>
    );
}

/** QR generated with the asset; scanning it opens this page. Downloadable as SVG for label printing. */
function AssetQrPanel({ assetTag, model, svg }: { assetTag: string; model: string | null; svg: string }) {
    const downloadHref = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(svg)}`;

    return (
        <Panel title="Kode QR">
            <div className="flex flex-col items-center gap-3">
                <div
                    className="rounded-lg bg-white p-2 ring-1 ring-zinc-200 [&_svg]:size-44"
                    dangerouslySetInnerHTML={{ __html: svg }}
                />
                <div className="text-center">
                    <p className="font-tag text-sm font-semibold text-zinc-800">{assetTag}</p>
                    {model && <p className="text-muted-foreground text-xs">{model}</p>}
                </div>
                <a
                    href={downloadHref}
                    download={`QR-${assetTag}.svg`}
                    className="text-primary inline-flex items-center gap-1.5 text-xs font-medium hover:underline"
                >
                    <Download className="size-3.5" /> Unduh QR
                </a>
            </div>
        </Panel>
    );
}
