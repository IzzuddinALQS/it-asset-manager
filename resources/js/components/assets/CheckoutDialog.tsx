import { useForm } from '@inertiajs/react';
import { MapPin, User } from 'lucide-react';
import type { FormEvent } from 'react';
import FormField from '@/components/FormField';
import SearchSelect from '@/components/SearchSelect';
import { optionsUrl } from '@/lib/options';
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
import { cn } from '@/lib/utils';

interface CheckoutForm {
    recipient: 'employee' | 'location';
    assigned_employee_id: number | null;
    assigned_location_id: number | null;
    expected_return_date: string;
    note: string;
}

export default function CheckoutDialog({
    assetId,
    assetTag,
    open,
    onOpenChange,
}: {
    assetId: number;
    assetTag: string;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<CheckoutForm>({
        recipient: 'employee',
        assigned_employee_id: null,
        assigned_location_id: null,
        expected_return_date: '',
        note: '',
    });

    const changeOpen = (next: boolean): void => {
        if (!next) {
            reset();
            clearErrors();
        }

        onOpenChange(next);
    };

    const chooseRecipient = (recipient: CheckoutForm['recipient']): void => {
        setData({ ...data, recipient, assigned_employee_id: null, assigned_location_id: null });
    };

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        post(`/assets/${assetId}/checkout`, {
            preserveScroll: true,
            onSuccess: () => changeOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>Berikan {assetTag}</DialogTitle>
                        <DialogDescription>
                            Serahkan inventaris ke karyawan atau tempatkan di sebuah lokasi. Riwayat akan mencatat
                            penyerahan ini.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Penerima">
                        {(
                            [
                                ['employee', 'Karyawan', User],
                                ['location', 'Lokasi', MapPin],
                            ] as const
                        ).map(([value, label, Icon]) => (
                            <button
                                key={value}
                                type="button"
                                role="radio"
                                aria-checked={data.recipient === value}
                                onClick={() => chooseRecipient(value)}
                                className={cn(
                                    'flex items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium',
                                    data.recipient === value
                                        ? 'border-primary bg-primary/5 text-primary'
                                        : 'border-border text-zinc-600 hover:bg-zinc-50',
                                )}
                            >
                                <Icon className="size-4" /> {label}
                            </button>
                        ))}
                    </div>

                    {data.recipient === 'employee' ? (
                        <FormField
                            label="Karyawan"
                            htmlFor="assigned_employee_id"
                            error={errors.assigned_employee_id}
                            required
                        >
                            <SearchSelect
                                id="assigned_employee_id"
                                url="/api/employees/search"
                                value={data.assigned_employee_id === null ? null : String(data.assigned_employee_id)}
                                onChange={(value) =>
                                    setData('assigned_employee_id', value === null ? null : Number(value))
                                }
                                placeholder="Cari karyawan aktif"
                                invalid={errors.assigned_employee_id !== undefined}
                            />
                        </FormField>
                    ) : (
                        <FormField
                            label="Lokasi"
                            htmlFor="assigned_location_id"
                            error={errors.assigned_location_id}
                            required
                        >
                            <SearchSelect
                                id="assigned_location_id"
                                url={optionsUrl('locations')}
                                value={data.assigned_location_id === null ? null : String(data.assigned_location_id)}
                                onChange={(value) =>
                                    setData('assigned_location_id', value === null ? null : Number(value))
                                }
                                placeholder="Cari lokasi"
                                invalid={errors.assigned_location_id !== undefined}
                            />
                        </FormField>
                    )}

                    <FormField
                        label="Rencana dikembalikan"
                        htmlFor="expected_return_date"
                        error={errors.expected_return_date}
                        hint="Isi untuk peminjaman sementara; kosongkan untuk pemakaian tetap."
                    >
                        <Input
                            id="expected_return_date"
                            type="date"
                            value={data.expected_return_date}
                            onChange={(event) => setData('expected_return_date', event.target.value)}
                        />
                    </FormField>

                    <FormField label="Catatan" htmlFor="checkout_note" error={errors.note}>
                        <Textarea
                            id="checkout_note"
                            rows={2}
                            value={data.note}
                            onChange={(event) => setData('note', event.target.value)}
                            placeholder="Nomor BAST, kelengkapan, kondisi…"
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => changeOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" loading={processing}>
                            Berikan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
