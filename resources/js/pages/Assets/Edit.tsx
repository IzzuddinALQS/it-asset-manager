import { Head } from '@inertiajs/react';
import AssetForm from '@/components/assets/AssetForm';
import PageHeader from '@/components/PageHeader';
import AppLayout from '@/layouts/AppLayout';
import type { AssetModelOption, Option } from '@/types/assets';

interface EditableAsset {
    id: number;
    asset_tag: string;
    status_label: string;
    asset_model_id: number;
    serial_number: string | null;
    location_id: number | null;
    supplier_id: number | null;
    purchase_order: string | null;
    purchase_date: string | null;
    purchase_cost: number | null;
    warranty_until: string | null;
    notes: string | null;
}

export default function AssetsEdit({
    asset,
    models,
    locations,
    suppliers,
}: {
    asset: EditableAsset;
    models: AssetModelOption[];
    locations: Option[];
    suppliers: Option[];
}) {
    return (
        <AppLayout>
            <Head title={`Edit ${asset.asset_tag}`} />

            <PageHeader
                title={`Edit ${asset.asset_tag}`}
                description="Status dan pemegang tidak diubah di sini; gunakan checkout, check-in, atau pensiun di halaman detail."
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Aset', href: '/assets' },
                    { label: asset.asset_tag, href: `/assets/${asset.id}` },
                    { label: 'Edit' },
                ]}
            />

            <div className="max-w-4xl">
                <AssetForm
                    initial={{
                        asset_model_id: asset.asset_model_id,
                        serial_number: asset.serial_number ?? '',
                        location_id: asset.location_id,
                        supplier_id: asset.supplier_id,
                        purchase_order: asset.purchase_order ?? '',
                        purchase_date: asset.purchase_date ?? '',
                        purchase_cost: asset.purchase_cost,
                        warranty_until: asset.warranty_until ?? '',
                        notes: asset.notes ?? '',
                    }}
                    models={models}
                    locations={locations}
                    suppliers={suppliers}
                    submitUrl={`/assets/${asset.id}`}
                    method="put"
                    submitLabel="Simpan perubahan"
                    cancelUrl={`/assets/${asset.id}`}
                />
            </div>
        </AppLayout>
    );
}
