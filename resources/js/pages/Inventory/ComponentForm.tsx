import { Head } from '@inertiajs/react';
import StockForm from '@/components/inventory/StockForm';
import PageHeader from '@/components/PageHeader';
import AppLayout from '@/layouts/AppLayout';

interface EditableComponent {
    id: number;
    name: string;
    spec: string | null;
    category_id: number;
    manufacturer_id: number | null;
    qty_total: number;
    min_qty: number;
    unit_cost: number | null;
    location_id: number | null;
    qty_used: number;
}

export default function ComponentFormPage({ component }: { component: EditableComponent | null }) {
    const title = component === null ? 'Tambah komponen' : `Edit ${component.name}`;

    return (
        <AppLayout>
            <Head title={title} />

            <div className="mx-auto w-full max-w-4xl">
                <PageHeader
                    title={title}
                    description="Komponen dipasang ke inventaris; stok tersedia dihitung dari jumlah total dikurangi yang terpasang."
                    breadcrumbs={[
                        { label: 'Dashboard', href: '/dashboard' },
                        { label: 'Komponen & Aksesori', href: '/inventory' },
                        { label: component === null ? 'Tambah' : 'Edit' },
                    ]}
                />

                <div>
                    <StockForm
                        initial={{
                            name: component?.name ?? '',
                            detail: component?.spec ?? '',
                            category_id: component?.category_id ?? null,
                            manufacturer_id: component?.manufacturer_id ?? null,
                            qty_total: component?.qty_total ?? 0,
                            min_qty: component?.min_qty ?? 0,
                            unit_cost: component?.unit_cost ?? null,
                            location_id: component?.location_id ?? null,
                        }}
                        detailField="spec"
                        detailLabel="Spesifikasi"
                        usedLabel="Terpasang"
                        qtyUsed={component?.qty_used ?? 0}
                        submitUrl={
                            component === null ? '/inventory/components' : `/inventory/components/${component.id}`
                        }
                        method={component === null ? 'post' : 'put'}
                        submitLabel={component === null ? 'Simpan komponen' : 'Simpan perubahan'}
                        cancelUrl="/inventory"
                    />
                </div>
            </div>
        </AppLayout>
    );
}
