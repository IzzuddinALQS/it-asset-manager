import { Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import CurrencyInput from '@/components/CurrencyInput';
import FormField from '@/components/FormField';
import OptionSelect from '@/components/OptionSelect';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Option } from '@/types/assets';
import type { StockFormData, StockFormOptions } from '@/types/inventory';

/**
 * Create/edit form shared by components and accessories; only the "detail"
 * field differs (`spec` vs `model`) and is renamed on submit.
 */
export default function StockForm({
    initial,
    options,
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
    options: StockFormOptions;
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

    const toOptions = (items: Option[]) => items.map((item) => ({ value: String(item.id), label: item.name }));
    const toNumber = (value: string): number | null => (value === '' ? null : Number(value));

    return (
        <form onSubmit={handleSubmit} className="border-border space-y-8 rounded-xl border bg-white p-6 shadow-sm">
            <section className="space-y-4">
                <h2 className="text-base font-semibold text-slate-900">Identitas</h2>
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
                        <OptionSelect
                            id="category_id"
                            value={data.category_id === null ? null : String(data.category_id)}
                            onChange={(value) => setData('category_id', value === null ? null : Number(value))}
                            options={toOptions(options.categories)}
                            placeholder="Pilih kategori"
                            invalid={errors.category_id !== undefined}
                        />
                    </FormField>
                    <FormField label="Manufaktur" htmlFor="manufacturer_id" error={errors.manufacturer_id}>
                        <OptionSelect
                            id="manufacturer_id"
                            value={data.manufacturer_id === null ? null : String(data.manufacturer_id)}
                            onChange={(value) => setData('manufacturer_id', value === null ? null : Number(value))}
                            options={toOptions(options.manufacturers)}
                            emptyLabel="Tidak ditentukan"
                        />
                    </FormField>
                    <FormField label="Lokasi penyimpanan" htmlFor="location_id" error={errors.location_id}>
                        <OptionSelect
                            id="location_id"
                            value={data.location_id === null ? null : String(data.location_id)}
                            onChange={(value) => setData('location_id', value === null ? null : Number(value))}
                            options={toOptions(options.locations)}
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
            </section>

            <section className="space-y-4">
                <h2 className="text-base font-semibold text-slate-900">Stok</h2>
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
            </section>

            <div className="flex justify-end gap-2">
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
