import { router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import CurrencyInput from '@/components/CurrencyInput';
import FormField from '@/components/FormField';
import OptionSelect from '@/components/OptionSelect';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import type { EmployeeOption, EnumOption } from '@/types/assets';
import type { MaintenanceAssetOption, SupplierOption } from '@/types/maintenance';

interface TicketForm {
    asset_id: number | null;
    type: string;
    priority: string;
    title: string;
    description: string;
    handled_by: string;
    supplier_id: number | null;
    reported_by_employee_id: number | null;
    estimated_cost: number | null;
    start_date: string;
    target_date: string;
}

function today(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

/**
 * Opens a maintenance ticket. With `asset` set the asset is fixed (asset page);
 * otherwise the user picks one from `assets` (board).
 */
export default function CreateTicketDialog({
    open,
    onOpenChange,
    asset,
    assets,
    employees,
    suppliers,
    types,
    priorities,
    handledBy,
    warrantyUntil,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    asset?: { id: number; asset_tag: string };
    /** Lazily loaded lists; `undefined` means not loaded yet. */
    assets?: MaintenanceAssetOption[];
    employees?: EmployeeOption[];
    suppliers?: SupplierOption[];
    types: EnumOption[];
    priorities: EnumOption[];
    handledBy: EnumOption[];
    warrantyUntil?: string | null;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<TicketForm>({
        asset_id: asset?.id ?? null,
        type: 'repair',
        priority: 'medium',
        title: '',
        description: '',
        handled_by: 'internal',
        supplier_id: null,
        reported_by_employee_id: null,
        estimated_cost: null,
        start_date: today(),
        target_date: '',
    });

    const changeOpen = (next: boolean): void => {
        if (next) {
            const missing = [
                asset === undefined && assets === undefined ? 'assets' : null,
                employees === undefined ? 'employees' : null,
                suppliers === undefined ? 'suppliers' : null,
            ].filter((key): key is string => key !== null);

            if (missing.length > 0) {
                router.reload({ only: missing });
            }
        }

        if (!next) {
            reset();
            clearErrors();
        }

        onOpenChange(next);
    };

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        post('/maintenance', { preserveScroll: true, onSuccess: () => changeOpen(false) });
    };

    const underWarranty = warrantyUntil != null && warrantyUntil >= today();

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>
                            {asset ? `Kirim ${asset.asset_tag} ke maintenance` : 'Buat tiket maintenance'}
                        </DialogTitle>
                        <DialogDescription>
                            Status aset berubah menjadi Perbaikan. Status sebelumnya disimpan agar bisa dikembalikan
                            saat tiket selesai.
                            {underWarranty && ' Aset ini masih dalam masa garansi.'}
                        </DialogDescription>
                    </DialogHeader>

                    {asset === undefined && (
                        <FormField label="Aset" htmlFor="asset_id" error={errors.asset_id} required>
                            <OptionSelect
                                id="asset_id"
                                value={data.asset_id === null ? null : String(data.asset_id)}
                                onChange={(value) => setData('asset_id', value === null ? null : Number(value))}
                                options={(assets ?? []).map((option) => ({
                                    value: String(option.id),
                                    label: option.asset_tag,
                                    hint: [option.model, option.status_label].filter(Boolean).join(' · '),
                                }))}
                                placeholder={assets === undefined ? 'Memuat aset…' : 'Pilih aset'}
                                invalid={errors.asset_id !== undefined}
                            />
                        </FormField>
                    )}
                    {asset !== undefined && errors.asset_id && (
                        <p className="text-destructive text-sm" role="alert">
                            {errors.asset_id}
                        </p>
                    )}

                    <FormField label="Judul" htmlFor="title" error={errors.title} required>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(event) => setData('title', event.target.value)}
                            placeholder="Mis. Layar berkedip, ganti keyboard"
                            maxLength={200}
                        />
                    </FormField>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Jenis" htmlFor="type" error={errors.type} required>
                            <OptionSelect
                                id="type"
                                value={data.type}
                                onChange={(value) => setData('type', value ?? 'repair')}
                                options={types.map((option) => ({ value: option.value, label: option.label }))}
                            />
                        </FormField>
                        <FormField label="Prioritas" htmlFor="priority" error={errors.priority} required>
                            <OptionSelect
                                id="priority"
                                value={data.priority}
                                onChange={(value) => setData('priority', value ?? 'medium')}
                                options={priorities.map((option) => ({ value: option.value, label: option.label }))}
                            />
                        </FormField>
                        <FormField label="Ditangani oleh" htmlFor="handled_by" error={errors.handled_by} required>
                            <OptionSelect
                                id="handled_by"
                                value={data.handled_by}
                                onChange={(value) => setData('handled_by', value ?? 'internal')}
                                options={handledBy.map((option) => ({ value: option.value, label: option.label }))}
                            />
                        </FormField>
                        <FormField
                            label="Vendor"
                            htmlFor="supplier_id"
                            error={errors.supplier_id}
                            required={data.handled_by === 'vendor'}
                        >
                            <OptionSelect
                                id="supplier_id"
                                value={data.supplier_id === null ? null : String(data.supplier_id)}
                                onChange={(value) => setData('supplier_id', value === null ? null : Number(value))}
                                options={(suppliers ?? []).map((supplier) => ({
                                    value: String(supplier.id),
                                    label: supplier.name,
                                    hint: supplier.is_service_vendor ? 'vendor servis' : null,
                                }))}
                                placeholder={suppliers === undefined ? 'Memuat…' : 'Pilih vendor'}
                                emptyLabel="Tanpa vendor"
                                invalid={errors.supplier_id !== undefined}
                            />
                        </FormField>
                        <FormField
                            label="Pelapor"
                            htmlFor="reported_by_employee_id"
                            error={errors.reported_by_employee_id}
                        >
                            <OptionSelect
                                id="reported_by_employee_id"
                                value={
                                    data.reported_by_employee_id === null ? null : String(data.reported_by_employee_id)
                                }
                                onChange={(value) =>
                                    setData('reported_by_employee_id', value === null ? null : Number(value))
                                }
                                options={(employees ?? []).map((employee) => ({
                                    value: String(employee.id),
                                    label: employee.name,
                                    hint: employee.department,
                                }))}
                                placeholder={employees === undefined ? 'Memuat…' : 'Pilih karyawan'}
                                emptyLabel="Tidak ada"
                            />
                        </FormField>
                        <FormField label="Estimasi biaya" htmlFor="estimated_cost" error={errors.estimated_cost}>
                            <CurrencyInput
                                id="estimated_cost"
                                value={data.estimated_cost}
                                onChange={(value) => setData('estimated_cost', value)}
                            />
                        </FormField>
                        <FormField label="Mulai" htmlFor="start_date" error={errors.start_date}>
                            <Input
                                id="start_date"
                                type="date"
                                value={data.start_date}
                                onChange={(event) => setData('start_date', event.target.value)}
                            />
                        </FormField>
                        <FormField label="Target selesai" htmlFor="target_date" error={errors.target_date}>
                            <Input
                                id="target_date"
                                type="date"
                                value={data.target_date}
                                onChange={(event) => setData('target_date', event.target.value)}
                            />
                        </FormField>
                    </div>

                    <FormField label="Deskripsi" htmlFor="description" error={errors.description}>
                        <Textarea
                            id="description"
                            rows={3}
                            value={data.description}
                            onChange={(event) => setData('description', event.target.value)}
                            placeholder="Gejala, kronologi, kelengkapan yang diserahkan…"
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => changeOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Buat tiket
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
