import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import CurrencyInput from '@/components/CurrencyInput';
import FormField from '@/components/FormField';
import FormSection from '@/components/FormSection';
import OptionSelect from '@/components/OptionSelect';
import SearchSelect from '@/components/SearchSelect';
import { optionsUrl } from '@/lib/options';
import PageHeader from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout';
import type { LicenseFormOptions } from '@/types/licenses';

interface EditableLicense {
    id: number;
    name: string;
    manufacturer_id: number | null;
    category_id: number | null;
    license_type: string;
    product_key_masked: string | null;
    seats_total: number;
    seats_used: number;
    supplier_id: number | null;
    purchase_order: string | null;
    purchase_date: string | null;
    annual_cost: number | null;
    expires_at: string | null;
    notes: string | null;
}

interface LicenseFormData {
    name: string;
    manufacturer_id: number | null;
    category_id: number | null;
    license_type: string;
    product_key: string;
    seats_total: number | null;
    supplier_id: number | null;
    purchase_order: string;
    purchase_date: string;
    annual_cost: number | null;
    expires_at: string;
    notes: string;
}

export default function LicenseFormPage({
    license,
    licenseTypes,
}: LicenseFormOptions & { license: EditableLicense | null }) {
    const isEdit = license !== null;
    const title = isEdit ? `Edit ${license.name}` : 'Tambah lisensi';
    const { data, setData, post, put, processing, errors } = useForm<LicenseFormData>({
        name: license?.name ?? '',
        manufacturer_id: license?.manufacturer_id ?? null,
        category_id: license?.category_id ?? null,
        license_type: license?.license_type ?? licenseTypes[0]?.value ?? '',
        product_key: '',
        seats_total: license?.seats_total ?? 1,
        supplier_id: license?.supplier_id ?? null,
        purchase_order: license?.purchase_order ?? '',
        purchase_date: license?.purchase_date ?? '',
        annual_cost: license?.annual_cost ?? null,
        expires_at: license?.expires_at ?? '',
        notes: license?.notes ?? '',
    });

    const submit = (event: FormEvent): void => {
        event.preventDefault();

        if (isEdit) {
            put(`/licenses/${license.id}`, { preserveScroll: true });
        } else {
            post('/licenses', { preserveScroll: true });
        }
    };

    const toId = (value: string | null): number | null => (value === null ? null : Number(value));
    const fromId = (value: number | null): string | null => (value === null ? null : String(value));

    return (
        <AppLayout>
            <Head title={title} />

            <div className="mx-auto w-full max-w-4xl">
                <PageHeader
                    title={title}
                    description="Product key disimpan terenkripsi dan hanya ditampilkan tersamar."
                    breadcrumbs={[
                        { label: 'Dashboard', href: '/dashboard' },
                        { label: 'Lisensi', href: '/licenses' },
                        { label: isEdit ? 'Edit' : 'Tambah' },
                    ]}
                />

                <form
                    onSubmit={submit}
                    className="border-border bg-card divide-border divide-y overflow-hidden rounded-xl border"
                >
                    <FormSection title="Identitas" description="Nama dan informasi dasar yang tampil di daftar.">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label="Nama" htmlFor="name" error={errors.name} required>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(event) => setData('name', event.target.value)}
                                />
                            </FormField>
                            <FormField
                                label="Jenis lisensi"
                                htmlFor="license_type"
                                error={errors.license_type}
                                required
                            >
                                <OptionSelect
                                    id="license_type"
                                    value={data.license_type || null}
                                    onChange={(value) => setData('license_type', value ?? '')}
                                    options={licenseTypes}
                                    invalid={errors.license_type !== undefined}
                                />
                            </FormField>
                            <FormField label="Manufaktur" htmlFor="manufacturer_id" error={errors.manufacturer_id}>
                                <SearchSelect
                                    id="manufacturer_id"
                                    url={optionsUrl('manufacturers')}
                                    value={fromId(data.manufacturer_id)}
                                    onChange={(value) => setData('manufacturer_id', toId(value))}
                                    emptyLabel="Tidak ditentukan"
                                />
                            </FormField>
                            <FormField label="Kategori" htmlFor="category_id" error={errors.category_id}>
                                <SearchSelect
                                    id="category_id"
                                    url={optionsUrl('categories')}
                                    params={{ category_type: 'license' }}
                                    value={fromId(data.category_id)}
                                    onChange={(value) => setData('category_id', toId(value))}
                                    emptyLabel="Tidak ditentukan"
                                />
                            </FormField>
                            <FormField
                                label="Product key"
                                htmlFor="product_key"
                                error={errors.product_key}
                                hint={
                                    license?.product_key_masked
                                        ? `Tersimpan: ${license.product_key_masked}. Kosongkan untuk mempertahankan.`
                                        : undefined
                                }
                                className="md:col-span-2"
                            >
                                <Input
                                    id="product_key"
                                    autoComplete="off"
                                    spellCheck={false}
                                    className="font-mono"
                                    value={data.product_key}
                                    onChange={(event) => setData('product_key', event.target.value)}
                                />
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection
                        title="Kuota & masa berlaku"
                        description="Jumlah seat dan tanggal habis untuk pengingat otomatis."
                    >
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField
                                label="Jumlah kuota"
                                htmlFor="seats_total"
                                error={errors.seats_total}
                                hint={
                                    license && license.seats_used > 0
                                        ? `${license.seats_used} seat sedang terpakai.`
                                        : undefined
                                }
                                required
                            >
                                <Input
                                    id="seats_total"
                                    type="number"
                                    min={Math.max(1, license?.seats_used ?? 0)}
                                    value={data.seats_total ?? ''}
                                    onChange={(event) =>
                                        setData(
                                            'seats_total',
                                            event.target.value === '' ? null : Number(event.target.value),
                                        )
                                    }
                                />
                            </FormField>
                            <FormField
                                label="Berlaku sampai"
                                htmlFor="expires_at"
                                error={errors.expires_at}
                                hint="Peringatan muncul 30 hari sebelum tanggal ini."
                            >
                                <Input
                                    id="expires_at"
                                    type="date"
                                    value={data.expires_at}
                                    onChange={(event) => setData('expires_at', event.target.value)}
                                />
                            </FormField>
                        </div>
                    </FormSection>

                    <FormSection title="Pembelian" description="Vendor, harga, dan referensi pembelian.">
                        <div className="grid gap-4 md:grid-cols-2">
                            <FormField label="Supplier" htmlFor="supplier_id" error={errors.supplier_id}>
                                <SearchSelect
                                    id="supplier_id"
                                    url={optionsUrl('suppliers')}
                                    value={fromId(data.supplier_id)}
                                    onChange={(value) => setData('supplier_id', toId(value))}
                                    emptyLabel="Tidak ditentukan"
                                />
                            </FormField>
                            <FormField label="Nomor PO" htmlFor="purchase_order" error={errors.purchase_order}>
                                <Input
                                    id="purchase_order"
                                    value={data.purchase_order}
                                    onChange={(event) => setData('purchase_order', event.target.value)}
                                />
                            </FormField>
                            <FormField label="Tanggal pembelian" htmlFor="purchase_date" error={errors.purchase_date}>
                                <Input
                                    id="purchase_date"
                                    type="date"
                                    value={data.purchase_date}
                                    onChange={(event) => setData('purchase_date', event.target.value)}
                                />
                            </FormField>
                            <FormField label="Biaya tahunan" htmlFor="annual_cost" error={errors.annual_cost}>
                                <CurrencyInput
                                    id="annual_cost"
                                    value={data.annual_cost}
                                    onChange={(value) => setData('annual_cost', value)}
                                />
                            </FormField>
                            <FormField label="Catatan" htmlFor="notes" error={errors.notes} className="md:col-span-2">
                                <Textarea
                                    id="notes"
                                    rows={3}
                                    value={data.notes}
                                    onChange={(event) => setData('notes', event.target.value)}
                                />
                            </FormField>
                        </div>
                    </FormSection>

                    <div className="flex flex-col-reverse gap-2 bg-zinc-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                        <Button variant="outline" asChild>
                            <Link href={isEdit ? `/licenses/${license.id}` : '/licenses'}>Batal</Link>
                        </Button>
                        <Button type="submit" loading={processing}>
                            {isEdit ? 'Simpan perubahan' : 'Simpan lisensi'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
