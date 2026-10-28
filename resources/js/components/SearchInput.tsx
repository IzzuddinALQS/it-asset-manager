import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Text search that calls `onSearch` after the user stops typing (or presses Enter).
 */
export default function SearchInput({
    defaultValue = '',
    onSearch,
    placeholder = 'Cari…',
    delay = 300,
    className,
}: {
    defaultValue?: string;
    onSearch: (query: string) => void;
    placeholder?: string;
    delay?: number;
    className?: string;
}) {
    const [value, setValue] = useState(defaultValue);
    const timer = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    useEffect(() => () => clearTimeout(timer.current), []);

    const search = (query: string, debounce: boolean): void => {
        clearTimeout(timer.current);

        if (debounce) {
            timer.current = setTimeout(() => onSearch(query.trim()), delay);
        } else {
            onSearch(query.trim());
        }
    };

    return (
        <div className={cn('relative w-full sm:w-72', className)}>
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="search"
                value={value}
                placeholder={placeholder}
                aria-label={placeholder}
                onChange={(event) => {
                    setValue(event.target.value);
                    search(event.target.value, true);
                }}
                onKeyDown={(event) => {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        search(value, false);
                    }
                }}
                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 h-9 w-full rounded-md border bg-white pr-8 pl-9 text-sm shadow-xs outline-none focus-visible:ring-[3px] [&::-webkit-search-cancel-button]:hidden"
            />
            {value !== '' && (
                <button
                    type="button"
                    aria-label="Hapus pencarian"
                    onClick={() => {
                        setValue('');
                        search('', false);
                    }}
                    className="absolute top-1/2 right-2 -translate-y-1/2 rounded p-0.5 text-slate-400 hover:text-slate-700"
                >
                    <X className="size-3.5" />
                </button>
            )}
        </div>
    );
}
