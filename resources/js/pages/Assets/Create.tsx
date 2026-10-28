import { Head } from '@inertiajs/react';
import AssetForm from '@/components/assets/AssetForm';
import PageHeader from '@/components/PageHeader';
import AppLayout from '@/layouts/AppLayout';
import type { AssetModelOption, Option } from '@/types/assets';

export default function AssetsCreate({
    models,
    locations,
    suppliers,
}: {
    models: AssetModelOption[];
    locations: Option[];
    suppliers: Option[];
}) {
    return (
        <AppLayout>
            <Head title="Tambah aset" />

            <PageHeader
                title="Tambah aset"
                description="Aset baru tercatat sebagai Menunggu dengan tag sementara. Tandai siap pakai setelah label QR ditempel."
                breadcrumbs={[
                    { label: 'Dashboard', href: '/dashboard' },
                    { label: 'Aset', href: '/assets' },
                    { label: 'Tambah' },
                ]}
            />

            <div className="max-w-4xl">
                <AssetForm
                    initial={{
                        asset_model_id: null,
                        serial_number: '',
                        location_id: null,
                        supplier_id: null,
                        purchase_order: '',
                        purchase_date: '',
                        purchase_cost: null,
                        warranty_until: '',
                        notes: '',
                    }}
                    models={models}
                    locations={locations}
                    suppliers={suppliers}
                    submitUrl="/assets"
                    method="post"
                    submitLabel="Simpan aset"
                    cancelUrl="/assets"
                />
            </div>
        </AppLayout>
    );
}
