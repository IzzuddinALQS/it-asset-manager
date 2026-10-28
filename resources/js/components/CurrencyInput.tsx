import type { ComponentProps } from 'react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

const grouping = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

/**
 * Rupiah input: shows thousand separators while keeping a plain number (or null) as the value.
 */
export default function CurrencyInput({
    value,
    onChange,
    className,
    ...props
}: Omit<ComponentProps<'input'>, 'value' | 'onChange' | 'type'> & {
    value: number | null;
    onChange: (value: number | null) => void;
}) {
    return (
        <div className="relative">
            <span className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm">
                Rp
            </span>
            <Input
                {...props}
                type="text"
                inputMode="numeric"
                value={value === null ? '' : grouping.format(value)}
                onChange={(event) => {
                    const digits = event.target.value.replace(/\D/g, '');

                    onChange(digits === '' ? null : Number(digits));
                }}
                className={cn('pl-9 tabular-nums', className)}
            />
        </div>
    );
}
