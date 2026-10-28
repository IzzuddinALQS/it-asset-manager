import type { ReactNode } from 'react';
import { useCallback, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { ConfirmContext, type ConfirmFn, type ConfirmOptions } from '@/lib/confirm';

export default function ConfirmDialog({
    open,
    onOpenChange,
    onConfirm,
    processing = false,
    title,
    description,
    confirmLabel = 'Ya, lanjutkan',
    cancelLabel = 'Batal',
    destructive = false,
}: ConfirmOptions & {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onConfirm: () => void;
    processing?: boolean;
}) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent showCloseButton={false} className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description && <DialogDescription>{description}</DialogDescription>}
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" disabled={processing} onClick={() => onOpenChange(false)}>
                        {cancelLabel}
                    </Button>
                    <Button variant={destructive ? 'destructive' : 'default'} disabled={processing} onClick={onConfirm}>
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

/**
 * Provides a single app-wide confirmation dialog, used through `useConfirm()`.
 */
export function ConfirmProvider({ children }: { children: ReactNode }) {
    const [open, setOpen] = useState(false);
    const [options, setOptions] = useState<ConfirmOptions>({ title: '' });
    const resolver = useRef<((confirmed: boolean) => void) | null>(null);

    const confirm = useCallback<ConfirmFn>(
        (nextOptions) =>
            new Promise<boolean>((resolve) => {
                resolver.current?.(false);
                resolver.current = resolve;
                setOptions(nextOptions);
                setOpen(true);
            }),
        [],
    );

    const settle = (confirmed: boolean): void => {
        resolver.current?.(confirmed);
        resolver.current = null;
        setOpen(false);
    };

    return (
        <ConfirmContext value={confirm}>
            {children}
            <ConfirmDialog
                {...options}
                open={open}
                onOpenChange={(nextOpen) => {
                    if (!nextOpen) {
                        settle(false);
                    }
                }}
                onConfirm={() => settle(true)}
            />
        </ConfirmContext>
    );
}
