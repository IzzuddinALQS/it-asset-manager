import { Head } from '@inertiajs/react';
import StockForm from '@/components/inventory/StockForm';
import PageHeader from '@/components/PageHeader';
import AppLayout from '@/layouts/AppLayout';
import type { StockFormOptions } from '@/types/inventory';

interface EditableAccessory {
    id: number;
    name: string;
    model: string | null;
    category_id: number;
    manufacturer_id: number | null;
    qty_total: number;
    min_qty: number;
    unit_cost: number | null;
    location_id: number | null;
    qty_used: number;
}

export default function AccessoryFormPage({
    accessory,
    ...options
}: StockFormOptions & { accessory: EditableAccessory | null }) {
    const title = accessory === null ? 'Tambah aksesori' : `Edit ${accessory.name}`;

    return (
        <AppLayout>
            <Head title={title} />

            <PageHeader
                title={title}
                description="Aksesori dipinjamkan ke karyawan; stok tersedia dihitung dari jumlah total dikurangi yang dipinjam."
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Komponen & Aksesori', href: '/inventory?tab=accessories' },
                    { label: accessory === null ? 'Tambah' : 'Edit' },
                ]}
            />

            <div className="max-w-4xl">
                <StockForm
                    initial={{
                        name: accessory?.name ?? '',
                        detail: accessory?.model ?? '',
                        category_id: accessory?.category_id ?? null,
                        manufacturer_id: accessory?.manufacturer_id ?? null,
                        qty_total: accessory?.qty_total ?? 0,
                        min_qty: accessory?.min_qty ?? 0,
                        unit_cost: accessory?.unit_cost ?? null,
                        location_id: accessory?.location_id ?? null,
                    }}
                    options={options}
                    detailField="model"
                    detailLabel="Model"
                    usedLabel="Dipinjam"
                    qtyUsed={accessory?.qty_used ?? 0}
                    submitUrl={accessory === null ? '/inventory/accessories' : `/inventory/accessories/${accessory.id}`}
                    method={accessory === null ? 'post' : 'put'}
                    submitLabel={accessory === null ? 'Simpan aksesori' : 'Simpan perubahan'}
                    cancelUrl={
                        accessory === null ? '/inventory?tab=accessories' : `/inventory/accessories/${accessory.id}`
                    }
                />
            </div>
        </AppLayout>
    );
}
