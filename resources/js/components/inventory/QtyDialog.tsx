import { router, useForm } from '@inertiajs/react';
import type { FormEvent, ReactNode } from 'react';
import FormField from '@/components/FormField';
import OptionSelect, { type SelectOption } from '@/components/OptionSelect';
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

interface QtyForm {
    target_id: number | null;
    qty: number | null;
    is_factory: boolean;
    note: string;
}

/**
 * Dialog for every quantity-based stock movement: install a component, lend an
 * accessory, or return part of a loan. `target` adds a picker (component or
 * employee) whose options are fetched lazily through `lazyProp`.
 */
export default function QtyDialog({
    open,
    onOpenChange,
    title,
    description,
    submitUrl,
    submitLabel,
    maxQty,
    defaultQty = 1,
    target,
    extra,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    submitUrl: string;
    submitLabel: string;
    /** Upper bound shown in the hint; the server still enforces stock. */
    maxQty?: number;
    defaultQty?: number;
    target?: {
        field: string;
        label: string;
        placeholder: string;
        options: SelectOption[] | undefined;
        lazyProp: string;
        onChange?: (id: number | null) => void;
    };
    extra?: (form: { isFactory: boolean; setIsFactory: (value: boolean) => void }) => ReactNode;
}) {
    const { data, setData, post, transform, processing, errors, reset, clearErrors } = useForm<QtyForm>({
        target_id: null,
        qty: defaultQty,
        is_factory: false,
        note: '',
    });
    const fieldErrors = errors as Partial<Record<string, string>>;

    const changeOpen = (next: boolean): void => {
        if (next && target !== undefined && target.options === undefined) {
            router.reload({ only: [target.lazyProp] });
        }

        if (!next) {
            reset();
            clearErrors();
        }

        onOpenChange(next);
    };

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        transform(({ target_id, ...rest }) => (target === undefined ? rest : { ...rest, [target.field]: target_id }));
        post(submitUrl, { preserveScroll: true, onSuccess: () => changeOpen(false) });
    };

    return (
        <Dialog open={open} onOpenChange={changeOpen}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit} className="space-y-5">
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>

                    {target !== undefined && (
                        <FormField label={target.label} htmlFor="target_id" error={fieldErrors[target.field]} required>
                            <OptionSelect
                                id="target_id"
                                value={data.target_id === null ? null : String(data.target_id)}
                                onChange={(value) => {
                                    const id = value === null ? null : Number(value);
                                    setData('target_id', id);
                                    target.onChange?.(id);
                                }}
                                options={target.options ?? []}
                                placeholder={target.options === undefined ? 'Memuat…' : target.placeholder}
                                invalid={fieldErrors[target.field] !== undefined}
                            />
                        </FormField>
                    )}

                    <FormField
                        label="Jumlah"
                        htmlFor="qty"
                        error={errors.qty}
                        hint={maxQty === undefined ? undefined : `Maksimal ${maxQty} unit.`}
                        required
                    >
                        <Input
                            id="qty"
                            type="number"
                            min={1}
                            value={data.qty ?? ''}
                            onChange={(event) =>
                                setData('qty', event.target.value === '' ? null : Number(event.target.value))
                            }
                        />
                    </FormField>

                    {extra?.({ isFactory: data.is_factory, setIsFactory: (value) => setData('is_factory', value) })}

                    <FormField label="Catatan" htmlFor="qty_note" error={errors.note}>
                        <Textarea
                            id="qty_note"
                            rows={2}
                            value={data.note}
                            onChange={(event) => setData('note', event.target.value)}
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => changeOpen(false)}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {submitLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
