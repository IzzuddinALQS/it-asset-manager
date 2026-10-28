import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormField from '@/components/FormField';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

interface CheckinForm {
    to_status: 'ready' | 'maintenance';
    note: string;
}

const outcomes = [
    { value: 'ready', label: 'Siap Pakai', description: 'Kondisi baik, kembali ke stok.' },
    { value: 'maintenance', label: 'Perlu perbaikan', description: 'Ada kerusakan, masuk antrean perbaikan.' },
] as const;

export default function CheckinDialog({
    assetId,
    assetTag,
    holderName,
    open,
    onOpenChange,
}: {
    assetId: number;
    assetTag: string;
    holderName: string | null;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<CheckinForm>({
        to_status: 'ready',
        note: '',
    });

    const changeOpen = (next: boolean): void => {
        if (!next) {
            reset();
            clearErrors();
        }

        onOpenChange(next);
    };

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        post(`/assets/${assetId}/checkin`, {
            preserveScroll: true,
            onSuccess: () => changeOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>Check-in {assetTag}</DialogTitle>
                        <DialogDescription>
                            {holderName ? `Terima kembali aset dari ${holderName}.` : 'Terima kembali aset ini.'}
                        </DialogDescription>
                    </DialogHeader>

                    <FormField label="Kondisi saat diterima" error={errors.to_status} required>
                        <div className="grid gap-2 sm:grid-cols-2" role="radiogroup" aria-label="Kondisi saat diterima">
                            {outcomes.map((outcome) => (
                                <button
                                    key={outcome.value}
                                    type="button"
                                    role="radio"
                                    aria-checked={data.to_status === outcome.value}
                                    onClick={() => setData('to_status', outcome.value)}
                                    className={cn(
                                        'rounded-lg border p-3 text-left',
                                        data.to_status === outcome.value
                                            ? 'border-primary bg-primary/5'
                                            : 'border-border hover:bg-slate-50',
                                    )}
                                >
                                    <span className="block text-sm font-semibold text-slate-800">{outcome.label}</span>
                                    <span className="text-muted-foreground block text-xs">{outcome.description}</span>
                                </button>
                            ))}
                        </div>
                    </FormField>

                    <FormField label="Catatan" htmlFor="checkin_note" error={errors.note}>
                        <Textarea
                            id="checkin_note"
                            rows={2}
                            value={data.note}
                            onChange={(event) => setData('note', event.target.value)}
                            placeholder="Kelengkapan, kerusakan yang ditemukan…"
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => changeOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Check-in
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
