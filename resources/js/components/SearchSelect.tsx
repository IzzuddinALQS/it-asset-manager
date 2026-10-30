import { Check, ChevronDown, Loader2, Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { SelectOption } from '@/components/OptionSelect';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

const DEBOUNCE_MS = 250;

function fetchOptions(url: string, params: Record<string, string>): Promise<SelectOption[]> {
    return fetch(`${url}?${new URLSearchParams(params)}`, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    })
        .then((response) => (response.ok ? response.json() : { data: [] }))
        .then((body: { data: SelectOption[] }) => body.data)
        .catch(() => []);
}

/**
 * Type-ahead picker backed by a JSON search endpoint returning `{ data: SelectOption[] }`.
 * Only the first page of matches is fetched, so it stays fast however large the table grows.
 * A value set from outside (edit form, URL filter) has its label resolved through `?id=`.
 */
export default function SearchSelect({
    id,
    url,
    params,
    value,
    onChange,
    placeholder = 'Pilih…',
    searchPlaceholder = 'Ketik untuk mencari…',
    emptyLabel,
    invalid = false,
    className,
}: {
    id?: string;
    url: string;
    /** Extra query-string filters sent with every search. */
    params?: Record<string, string>;
    value: string | null;
    onChange: (value: string | null, option: SelectOption | null) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    /** When set, an extra item with this label clears the selection. */
    emptyLabel?: string;
    invalid?: boolean;
    className?: string;
}) {
    const [open, setOpen] = useState(false);
    const [term, setTerm] = useState('');
    const [options, setOptions] = useState<SelectOption[]>([]);
    const [loading, setLoading] = useState(false);
    const [selected, setSelected] = useState<SelectOption | null>(null);
    const paramsKey = JSON.stringify(params ?? {});
    const requestId = useRef(0);
    // Show the picked label only while it still matches the form value (reset clears it).
    const shown = value !== null && selected?.value === value ? selected : null;

    useEffect(() => {
        if (value === null || selected?.value === value) {
            return;
        }

        let cancelled = false;

        fetchOptions(url, { ...JSON.parse(paramsKey), id: value }).then((found) => {
            if (!cancelled && found[0] !== undefined) {
                setSelected(found[0]);
            }
        });

        return () => {
            cancelled = true;
        };
    }, [value, selected?.value, url, paramsKey]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const current = ++requestId.current;

        const timer = window.setTimeout(
            () => {
                setLoading(true);
                fetchOptions(url, { ...JSON.parse(paramsKey), q: term.trim() }).then((found) => {
                    if (current === requestId.current) {
                        setOptions(found);
                        setLoading(false);
                    }
                });
            },
            term === '' ? 0 : DEBOUNCE_MS,
        );

        return () => window.clearTimeout(timer);
    }, [open, term, url, paramsKey]);

    const choose = (option: SelectOption | null): void => {
        setSelected(option);
        onChange(option?.value ?? null, option);
        setOpen(false);
        setTerm('');
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    id={id}
                    type="button"
                    role="combobox"
                    aria-expanded={open}
                    aria-invalid={invalid || undefined}
                    className={cn(
                        'border-input focus-visible:ring-ring/40 aria-invalid:border-destructive flex h-9 w-full items-center justify-between gap-2 rounded-md border bg-white px-3 text-left text-sm shadow-xs outline-none focus-visible:ring-[3px]',
                        className,
                    )}
                >
                    <span className={cn('truncate', shown === null && 'text-muted-foreground')}>
                        {shown === null ? (
                            value === null ? (
                                (emptyLabel ?? placeholder)
                            ) : (
                                'Memuat…'
                            )
                        ) : (
                            <>
                                {shown.label}
                                {shown.hint && <span className="text-muted-foreground ml-1">· {shown.hint}</span>}
                            </>
                        )}
                    </span>
                    <ChevronDown className="size-4 shrink-0 opacity-50" />
                </button>
            </PopoverTrigger>
            <PopoverContent align="start" className="w-(--radix-popover-trigger-width) min-w-64 p-0">
                <div className="border-border flex items-center gap-2 border-b px-3">
                    <Search className="text-muted-foreground size-4 shrink-0" />
                    <input
                        autoFocus
                        value={term}
                        onChange={(event) => setTerm(event.target.value)}
                        placeholder={searchPlaceholder}
                        className="h-10 w-full bg-transparent text-sm outline-none"
                    />
                    {loading && <Loader2 className="text-muted-foreground size-4 shrink-0 animate-spin" />}
                </div>
                <ul role="listbox" className="max-h-64 overflow-y-auto p-1">
                    {emptyLabel && value !== null && (
                        <li>
                            <button
                                type="button"
                                role="option"
                                aria-selected={false}
                                onClick={() => choose(null)}
                                className="hover:bg-accent text-muted-foreground flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm"
                            >
                                <X className="size-4 shrink-0" /> {emptyLabel}
                            </button>
                        </li>
                    )}
                    {!loading && options.length === 0 && (
                        <li className="text-muted-foreground px-3 py-6 text-center text-sm">Tidak ada hasil.</li>
                    )}
                    {options.map((option) => (
                        <li key={option.value}>
                            <button
                                type="button"
                                role="option"
                                aria-selected={option.value === value}
                                onClick={() => choose(option)}
                                className="hover:bg-accent flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm"
                            >
                                <span className="min-w-0 flex-1 truncate">
                                    {option.label}
                                    {option.hint && <span className="text-muted-foreground ml-1">· {option.hint}</span>}
                                </span>
                                {option.value === value && <Check className="size-4 shrink-0" />}
                            </button>
                        </li>
                    ))}
                </ul>
            </PopoverContent>
        </Popover>
    );
}
