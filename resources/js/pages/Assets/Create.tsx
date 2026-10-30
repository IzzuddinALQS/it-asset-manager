import { Head } from '@inertiajs/react';
import AssetForm from '@/components/assets/AssetForm';
import PageHeader from '@/components/PageHeader';
import AppLayout from '@/layouts/AppLayout';

export default function AssetsCreate() {
    return (
        <AppLayout>
            <Head title="Tambah inventaris" />

            <div className="mx-auto w-full max-w-4xl">
                <PageHeader
                    title="Tambah inventaris"
                    description="Inventaris baru tercatat sebagai Menunggu dengan tag sementara. Tandai siap guna setelah label QR ditempel."
                    breadcrumbs={[
                        { label: 'Dashboard', href: '/dashboard' },
                        { label: 'Inventaris', href: '/assets' },
                        { label: 'Tambah' },
                    ]}
                />

                <div>
                    <AssetForm
                        initial={{
                            asset_model_id: null,
                            serial_number: '',
                            imei: '',
                            phone_number: '',
                            location_id: null,
                            supplier_id: null,
                            purchase_order: '',
                            purchase_date: '',
                            purchase_cost: null,
                            warranty_until: '',
                            notes: '',
                        }}
                        submitUrl="/assets"
                        method="post"
                        submitLabel="Simpan inventaris"
                        cancelUrl="/assets"
                    />
                </div>
            </div>
        </AppLayout>
    );
}
