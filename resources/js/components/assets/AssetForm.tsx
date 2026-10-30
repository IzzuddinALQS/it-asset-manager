import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import CurrencyInput from '@/components/CurrencyInput';
import FormField from '@/components/FormField';
import FormSection from '@/components/FormSection';
import SearchSelect from '@/components/SearchSelect';
import { optionsUrl } from '@/lib/options';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { AssetFormData } from '@/types/assets';

export default function AssetForm({
    initial,
    submitUrl,
    method,
    submitLabel,
    cancelUrl,
}: {
    initial: AssetFormData;
    submitUrl: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
}) {
    const { data, setData, submit, processing, errors } = useForm<AssetFormData>(initial);

    const handleSubmit = (event: FormEvent): void => {
        event.preventDefault();
        submit(method, submitUrl, { preserveScroll: true });
    };

    return (
        <form
            onSubmit={handleSubmit}
            className="border-border bg-card divide-border divide-y overflow-hidden rounded-xl border"
        >
            <FormSection
                title="Identitas perangkat"
                description="Tag, model, dan nomor seri yang membedakan perangkat ini."
            >
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Model inventaris" htmlFor="asset_model_id" error={errors.asset_model_id} required>
                        <SearchSelect
                            id="asset_model_id"
                            url={optionsUrl('asset-models')}
                            value={data.asset_model_id === null ? null : String(data.asset_model_id)}
                            onChange={(value) => setData('asset_model_id', value === null ? null : Number(value))}
                            placeholder="Cari model"
                            invalid={errors.asset_model_id !== undefined}
                        />
                    </FormField>
                    <FormField
                        label="Nomor seri"
                        htmlFor="serial_number"
                        error={errors.serial_number}
                        hint="Kosongkan bila perangkat tidak punya nomor seri."
                    >
                        <Input
                            id="serial_number"
                            value={data.serial_number}
                            onChange={(event) => setData('serial_number', event.target.value)}
                            className="font-tag"
                        />
                    </FormField>
                    <FormField
                        label="IMEI"
                        htmlFor="imei"
                        error={errors.imei}
                        hint="15 digit, untuk perangkat seluler. Kosongkan bila tidak ada."
                    >
                        <Input
                            id="imei"
                            inputMode="numeric"
                            maxLength={15}
                            value={data.imei}
                            onChange={(event) => setData('imei', event.target.value.replace(/\D/g, ''))}
                            className="font-tag"
                        />
                    </FormField>
                    <FormField
                        label="Nomor HP"
                        htmlFor="phone_number"
                        error={errors.phone_number}
                        hint="Nomor SIM di perangkat, diawali 0."
                    >
                        <Input
                            id="phone_number"
                            type="tel"
                            inputMode="tel"
                            maxLength={15}
                            value={data.phone_number}
                            onChange={(event) => setData('phone_number', event.target.value.replace(/\D/g, ''))}
                            placeholder="081234567890"
                            className="font-tag"
                        />
                    </FormField>
                    <FormField label="Lokasi fisik" htmlFor="location_id" error={errors.location_id}>
                        <SearchSelect
                            id="location_id"
                            url={optionsUrl('locations')}
                            value={data.location_id === null ? null : String(data.location_id)}
                            onChange={(value) => setData('location_id', value === null ? null : Number(value))}
                            emptyLabel="Belum ditentukan"
                        />
                    </FormField>
                </div>
            </FormSection>

            <FormSection
                title="Pengadaan & garansi"
                description="Data pembelian untuk nilai perolehan dan pengingat garansi."
            >
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Supplier" htmlFor="supplier_id" error={errors.supplier_id}>
                        <SearchSelect
                            id="supplier_id"
                            url={optionsUrl('suppliers')}
                            value={data.supplier_id === null ? null : String(data.supplier_id)}
                            onChange={(value) => setData('supplier_id', value === null ? null : Number(value))}
                            emptyLabel="Tanpa supplier"
                        />
                    </FormField>
                    <FormField label="Nomor PO" htmlFor="purchase_order" error={errors.purchase_order}>
                        <Input
                            id="purchase_order"
                            value={data.purchase_order}
                            onChange={(event) => setData('purchase_order', event.target.value)}
                            placeholder="PO-2026-0001"
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
                    <FormField label="Harga perolehan" htmlFor="purchase_cost" error={errors.purchase_cost}>
                        <CurrencyInput
                            id="purchase_cost"
                            value={data.purchase_cost}
                            onChange={(value) => setData('purchase_cost', value)}
                        />
                    </FormField>
                    <FormField label="Garansi sampai" htmlFor="warranty_until" error={errors.warranty_until}>
                        <Input
                            id="warranty_until"
                            type="date"
                            value={data.warranty_until}
                            onChange={(event) => setData('warranty_until', event.target.value)}
                        />
                    </FormField>
                </div>
            </FormSection>

            <FormSection title="Catatan" description="Informasi tambahan yang perlu diketahui tim IT.">
                <FormField label="Catatan" htmlFor="notes" error={errors.notes}>
                    <Textarea
                        id="notes"
                        rows={3}
                        value={data.notes}
                        onChange={(event) => setData('notes', event.target.value)}
                    />
                </FormField>
            </FormSection>

            <div className="flex flex-col-reverse gap-2 bg-zinc-50/70 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <Button variant="outline" asChild>
                    <Link href={cancelUrl}>Batal</Link>
                </Button>
                <Button type="submit" loading={processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
