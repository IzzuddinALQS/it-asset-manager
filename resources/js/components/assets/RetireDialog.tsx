import { useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
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
import { Textarea } from '@/components/ui/textarea';
import type { EnumOption } from '@/types/assets';

interface RetireForm {
    retire_reason: string | null;
    note: string;
}

export default function RetireDialog({
    assetId,
    assetTag,
    reasons,
    open,
    onOpenChange,
}: {
    assetId: number;
    assetTag: string;
    reasons: EnumOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<RetireForm>({
        retire_reason: null,
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
        post(`/assets/${assetId}/retire`, {
            preserveScroll: true,
            onSuccess: () => changeOpen(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>Pensiunkan {assetTag}?</DialogTitle>
                        <DialogDescription>
                            Inventaris yang dipensiunkan tidak bisa dipinjamkan lagi. Pemegang saat ini dilepas dan
                            kuota lisensi pada perangkat ini dikembalikan.
                        </DialogDescription>
                    </DialogHeader>

                    <FormField label="Alasan" htmlFor="retire_reason" error={errors.retire_reason} required>
                        <OptionSelect
                            id="retire_reason"
                            value={data.retire_reason}
                            onChange={(value) => setData('retire_reason', value)}
                            options={reasons.map((reason) => ({ value: reason.value, label: reason.label }))}
                            placeholder="Pilih alasan"
                            invalid={errors.retire_reason !== undefined}
                        />
                    </FormField>

                    <FormField label="Catatan" htmlFor="retire_note" error={errors.note}>
                        <Textarea
                            id="retire_note"
                            rows={2}
                            value={data.note}
                            onChange={(event) => setData('note', event.target.value)}
                            placeholder="Nomor berita acara, pembeli, dll."
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => changeOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" variant="destructive" loading={processing}>
                            Pensiunkan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
