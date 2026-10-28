import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import CurrencyInput from '@/components/CurrencyInput';
import FormField from '@/components/FormField';
import OptionSelect from '@/components/OptionSelect';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { AssetFormData, AssetModelOption, Option } from '@/types/assets';

export default function AssetForm({
    initial,
    models,
    locations,
    suppliers,
    submitUrl,
    method,
    submitLabel,
    cancelUrl,
}: {
    initial: AssetFormData;
    models: AssetModelOption[];
    locations: Option[];
    suppliers: Option[];
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
        <form onSubmit={handleSubmit} className="border-border space-y-8 rounded-xl border bg-white p-6 shadow-sm">
            <section className="space-y-4">
                <h2 className="text-base font-semibold text-slate-900">Identitas perangkat</h2>
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Model aset" htmlFor="asset_model_id" error={errors.asset_model_id} required>
                        <OptionSelect
                            id="asset_model_id"
                            value={data.asset_model_id === null ? null : String(data.asset_model_id)}
                            onChange={(value) => setData('asset_model_id', value === null ? null : Number(value))}
                            options={models.map((model) => ({
                                value: String(model.id),
                                label: model.name,
                                hint: model.category,
                            }))}
                            placeholder="Pilih model"
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
                    <FormField label="Lokasi fisik" htmlFor="location_id" error={errors.location_id}>
                        <OptionSelect
                            id="location_id"
                            value={data.location_id === null ? null : String(data.location_id)}
                            onChange={(value) => setData('location_id', value === null ? null : Number(value))}
                            options={locations.map((location) => ({
                                value: String(location.id),
                                label: location.name,
                            }))}
                            emptyLabel="Belum ditentukan"
                        />
                    </FormField>
                </div>
            </section>

            <section className="space-y-4">
                <h2 className="text-base font-semibold text-slate-900">Pengadaan &amp; garansi</h2>
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Supplier" htmlFor="supplier_id" error={errors.supplier_id}>
                        <OptionSelect
                            id="supplier_id"
                            value={data.supplier_id === null ? null : String(data.supplier_id)}
                            onChange={(value) => setData('supplier_id', value === null ? null : Number(value))}
                            options={suppliers.map((supplier) => ({
                                value: String(supplier.id),
                                label: supplier.name,
                            }))}
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
            </section>

            <FormField label="Catatan" htmlFor="notes" error={errors.notes}>
                <Textarea
                    id="notes"
                    rows={3}
                    value={data.notes}
                    onChange={(event) => setData('notes', event.target.value)}
                />
            </FormField>

            <div className="border-border flex justify-end gap-2 border-t pt-6">
                <Button variant="outline" asChild>
                    <Link href={cancelUrl}>Batal</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
