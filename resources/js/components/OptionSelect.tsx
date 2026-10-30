import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const EMPTY = '__none__';

export interface SelectOption {
    value: string;
    label: string;
    hint?: string | null;
    /** Extra data a search endpoint attaches, e.g. available stock. */
    meta?: Record<string, unknown>;
}

/**
 * Select over a list of options that can also be left empty (Radix Select has no empty value).
 */
export default function OptionSelect({
    id,
    value,
    onChange,
    options,
    placeholder = 'Pilih…',
    emptyLabel,
    invalid = false,
    className,
}: {
    id?: string;
    value: string | null;
    onChange: (value: string | null) => void;
    options: SelectOption[];
    placeholder?: string;
    /** When set, an extra item with this label clears the selection. */
    emptyLabel?: string;
    invalid?: boolean;
    className?: string;
}) {
    return (
        <Select
            value={value ?? (emptyLabel ? EMPTY : '')}
            onValueChange={(next) => onChange(next === EMPTY ? null : next)}
        >
            <SelectTrigger id={id} aria-invalid={invalid || undefined} className={className ?? 'w-full bg-white'}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent position="popper" className="max-h-72 w-(--radix-select-trigger-width)">
                {emptyLabel && <SelectItem value={EMPTY}>{emptyLabel}</SelectItem>}
                {options.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                        {option.label}
                        {option.hint && <span className="text-muted-foreground ml-1">· {option.hint}</span>}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
