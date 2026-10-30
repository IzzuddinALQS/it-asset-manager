import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import CurrencyInput from '@/components/CurrencyInput';
import FormField from '@/components/FormField';
import FormSection from '@/components/FormSection';
import SearchSelect from '@/components/SearchSelect';
import { optionsUrl } from '@/lib/options';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { StockFormData } from '@/types/inventory';

/**
 * Create/edit form shared by components and accessories; only the "detail"
 * field differs (`spec` vs `model`) and is renamed on submit.
 */
export default function StockForm({
    initial,
    detailField,
    detailLabel,
    usedLabel,
    qtyUsed,
    submitUrl,
    method,
    submitLabel,
    cancelUrl,
}: {
    initial: StockFormData;
    detailField: 'spec' | 'model';
    detailLabel: string;
    usedLabel: string;
    qtyUsed: number;
    submitUrl: string;
    method: 'post' | 'put';
    submitLabel: string;
    cancelUrl: string;
}) {
    const { data, setData, submit, transform, processing, errors } = useForm<StockFormData>(initial);
    const fieldErrors = errors as Partial<Record<string, string>>;

    const handleSubmit = (event: FormEvent): void => {
        event.preventDefault();
        transform(({ detail, ...rest }) => ({ ...rest, [detailField]: detail }));
        submit(method, submitUrl, { preserveScroll: true });
    };

    // Components and accessories each keep their own category list.
    const categoryType = detailField === 'spec' ? 'component' : 'accessory';
    const toNumber = (value: string): number | null => (value === '' ? null : Number(value));

    return (
        <form
            onSubmit={handleSubmit}
            className="border-border bg-card divide-border divide-y overflow-hidden rounded-xl border"
        >
            <FormSection title="Identitas" description="Nama dan informasi dasar yang tampil di daftar.">
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField label="Nama" htmlFor="name" error={errors.name} required>
                        <Input id="name" value={data.name} onChange={(event) => setData('name', event.target.value)} />
                    </FormField>
                    <FormField label={detailLabel} htmlFor="detail" error={fieldErrors[detailField]}>
                        <Input
                            id="detail"
                            value={data.detail}
                            onChange={(event) => setData('detail', event.target.value)}
                        />
                    </FormField>
                    <FormField label="Kategori" htmlFor="category_id" error={errors.category_id} required>
                        <SearchSelect
                            id="category_id"
                            url={optionsUrl('categories')}
                            params={{ category_type: categoryType }}
                            value={data.category_id === null ? null : String(data.category_id)}
                            onChange={(value) => setData('category_id', value === null ? null : Number(value))}
                            placeholder="Cari kategori"
                            invalid={errors.category_id !== undefined}
                        />
                    </FormField>
                    <FormField label="Manufaktur" htmlFor="manufacturer_id" error={errors.manufacturer_id}>
                        <SearchSelect
                            id="manufacturer_id"
                            url={optionsUrl('manufacturers')}
                            value={data.manufacturer_id === null ? null : String(data.manufacturer_id)}
                            onChange={(value) => setData('manufacturer_id', value === null ? null : Number(value))}
                            emptyLabel="Tidak ditentukan"
                        />
                    </FormField>
                    <FormField label="Lokasi penyimpanan" htmlFor="location_id" error={errors.location_id}>
                        <SearchSelect
                            id="location_id"
                            url={optionsUrl('locations')}
                            value={data.location_id === null ? null : String(data.location_id)}
                            onChange={(value) => setData('location_id', value === null ? null : Number(value))}
                            emptyLabel="Belum ditentukan"
                        />
                    </FormField>
                    <FormField label="Harga satuan" htmlFor="unit_cost" error={errors.unit_cost}>
                        <CurrencyInput
                            id="unit_cost"
                            value={data.unit_cost}
                            onChange={(value) => setData('unit_cost', value)}
                        />
                    </FormField>
                </div>
            </FormSection>

            <FormSection title="Stok" description="Jumlah tersedia dan batas minimum sebelum peringatan.">
                <div className="grid gap-4 md:grid-cols-2">
                    <FormField
                        label="Jumlah total"
                        htmlFor="qty_total"
                        error={errors.qty_total}
                        hint={qtyUsed > 0 ? `${qtyUsed} unit sedang ${usedLabel.toLowerCase()}.` : undefined}
                        required
                    >
                        <Input
                            id="qty_total"
                            type="number"
                            min={qtyUsed}
                            value={data.qty_total ?? ''}
                            onChange={(event) => setData('qty_total', toNumber(event.target.value))}
                        />
                    </FormField>
                    <FormField
                        label="Stok minimum"
                        htmlFor="min_qty"
                        error={errors.min_qty}
                        hint="Peringatan stok menipis muncul saat stok tersedia ≤ angka ini."
                        required
                    >
                        <Input
                            id="min_qty"
                            type="number"
                            min={0}
                            value={data.min_qty ?? ''}
                            onChange={(event) => setData('min_qty', toNumber(event.target.value))}
                        />
                    </FormField>
                </div>
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
