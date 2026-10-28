import { Head, router, useForm } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Pencil, Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/components/DataTable';
import FormField from '@/components/FormField';
import OptionSelect from '@/components/OptionSelect';
import PageHeader from '@/components/PageHeader';
import SettingsNav from '@/components/settings/SettingsNav';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout';
import { useConfirm } from '@/lib/confirm';
import type { EnumOption } from '@/types/assets';

type FieldValue = string | number | boolean | null;

interface Field {
    key: string;
    label: string;
    input: 'text' | 'email' | 'number' | 'textarea' | 'select' | 'checkbox';
    required: boolean;
    options?: string;
}

type MasterRecord = { id: number } & Record<string, FieldValue>;

interface MasterDataProps {
    type: string;
    types: EnumOption[];
    fields: Field[];
    records: MasterRecord[];
    options: Record<string, EnumOption[]>;
}

export default function SettingsMasterData(props: MasterDataProps) {
    return (
        <AppLayout>
            <MasterDataPage {...props} />
        </AppLayout>
    );
}

/** Rendered inside AppLayout so `useConfirm` has its provider. */
function MasterDataPage({ type, types, fields, records, options }: MasterDataProps) {
    const confirm = useConfirm();
    const [editing, setEditing] = useState<MasterRecord | 'new' | null>(null);
    const label = types.find((option) => option.value === type)?.label ?? 'Data master';

    const display = (field: Field, value: FieldValue): string => {
        if (field.input === 'checkbox') {
            return value ? 'Ya' : 'Tidak';
        }

        if (field.input === 'select' && field.options) {
            return options[field.options]?.find((option) => option.value === String(value))?.label ?? '—';
        }

        return value === null || value === '' ? '—' : String(value);
    };

    const remove = async (record: MasterRecord): Promise<void> => {
        const confirmed = await confirm({
            title: `Hapus ${label.toLowerCase()} ${String(record.name)}?`,
            description: 'Data yang masih dipakai aset, inventaris, atau lisensi tidak bisa dihapus.',
            confirmLabel: 'Hapus',
            destructive: true,
        });

        if (confirmed) {
            router.delete(`/settings/master/${type}/${record.id}`, { preserveScroll: true });
        }
    };

    const columns: ColumnDef<MasterRecord, unknown>[] = [
        ...fields.map((field): ColumnDef<MasterRecord, unknown> => ({
            id: field.key,
            header: field.label,
            cell: ({ row }) => (
                <span className={field.key === 'name' ? 'font-medium text-slate-800' : undefined}>
                    {display(field, row.original[field.key])}
                </span>
            ),
        })),
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => (
                <div className="flex justify-end gap-1">
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => setEditing(row.original)}
                        aria-label={`Ubah ${String(row.original.name)}`}
                    >
                        <Pencil />
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => remove(row.original)}
                        aria-label={`Hapus ${String(row.original.name)}`}
                    >
                        <Trash2 />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title={label} />

            <PageHeader
                title="Pengaturan"
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Data master' }, { label }]}
                actions={
                    <Button onClick={() => setEditing('new')}>
                        <Plus /> Tambah {label.toLowerCase()}
                    </Button>
                }
            />
            <SettingsNav active={`master:${type}`} />

            <div className="border-border overflow-hidden rounded-xl border bg-white shadow-sm">
                <DataTable columns={columns} data={records} emptyMessage={`Belum ada ${label.toLowerCase()}.`} />
            </div>

            {editing !== null && (
                <RecordDialog
                    key={editing === 'new' ? 'new' : editing.id}
                    type={type}
                    label={label}
                    fields={fields}
                    options={options}
                    record={editing === 'new' ? null : editing}
                    onClose={() => setEditing(null)}
                />
            )}
        </>
    );
}

function RecordDialog({
    type,
    label,
    fields,
    options,
    record,
    onClose,
}: {
    type: string;
    label: string;
    fields: Field[];
    options: Record<string, EnumOption[]>;
    record: MasterRecord | null;
    onClose: () => void;
}) {
    const { data, setData, post, put, processing, errors } = useForm<Record<string, FieldValue>>(
        Object.fromEntries(
            fields.map((field) => [field.key, record?.[field.key] ?? (field.input === 'checkbox' ? false : null)]),
        ),
    );

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        const visitOptions = { preserveScroll: true, onSuccess: onClose };

        if (record === null) {
            post(`/settings/master/${type}`, visitOptions);
        } else {
            put(`/settings/master/${type}/${record.id}`, visitOptions);
        }
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>
                            {record === null ? `Tambah ${label.toLowerCase()}` : `Ubah ${String(record.name)}`}
                        </DialogTitle>
                    </DialogHeader>

                    {fields.map((field) => {
                        const id = `field_${field.key}`;
                        const value = data[field.key];
                        const error = errors[field.key];

                        if (field.input === 'checkbox') {
                            return (
                                <label key={field.key} className="flex items-center gap-2 text-sm text-slate-700">
                                    <Checkbox
                                        checked={Boolean(value)}
                                        onCheckedChange={(checked) => setData(field.key, checked === true)}
                                    />
                                    {field.label}
                                </label>
                            );
                        }

                        return (
                            <FormField
                                key={field.key}
                                label={field.label}
                                htmlFor={id}
                                error={error}
                                required={field.required}
                            >
                                {field.input === 'select' ? (
                                    <OptionSelect
                                        id={id}
                                        value={value === null ? null : String(value)}
                                        onChange={(next) => setData(field.key, next)}
                                        options={options[field.options ?? ''] ?? []}
                                        emptyLabel={field.required ? undefined : 'Tidak ada'}
                                        invalid={error !== undefined}
                                    />
                                ) : field.input === 'textarea' ? (
                                    <Textarea
                                        id={id}
                                        value={String(value ?? '')}
                                        onChange={(event) => setData(field.key, event.target.value)}
                                        aria-invalid={error !== undefined || undefined}
                                    />
                                ) : (
                                    <Input
                                        id={id}
                                        type={field.input}
                                        min={field.input === 'number' ? 1 : undefined}
                                        value={String(value ?? '')}
                                        onChange={(event) =>
                                            setData(field.key, event.target.value === '' ? null : event.target.value)
                                        }
                                        aria-invalid={error !== undefined || undefined}
                                    />
                                )}
                            </FormField>
                        );
                    })}

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={onClose}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
