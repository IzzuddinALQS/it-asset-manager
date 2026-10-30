import { cn } from '@/lib/utils';

export interface FilterTab<T extends string = string> {
    value: T;
    label: string;
    count?: number;
    /** Highlights the count, e.g. items that need attention. */
    countTone?: 'warning';
    title?: string;
}

/**
 * Single-choice filter that triggers a visit. `underline` sits above a page section;
 * `segmented` fits inside a toolbar next to search and selects.
 */
export default function FilterTabs<T extends string>({
    tabs,
    value,
    onChange,
    label,
    variant = 'underline',
    className,
}: {
    tabs: FilterTab<T>[];
    value: T;
    onChange: (value: T) => void;
    label: string;
    variant?: 'underline' | 'segmented';
    className?: string;
}) {
    const segmented = variant === 'segmented';

    return (
        <div
            role="tablist"
            aria-label={label}
            className={cn(
                'flex scrollbar-thin overflow-x-auto',
                segmented
                    ? 'w-fit max-w-full gap-0.5 rounded-lg bg-zinc-100 p-0.5'
                    : 'border-border mb-5 gap-1 border-b',
                className,
            )}
        >
            {tabs.map((tab) => {
                const active = tab.value === value;

                return (
                    <button
                        key={tab.value || 'all'}
                        type="button"
                        role="tab"
                        aria-selected={active}
                        title={tab.title}
                        onClick={() => onChange(tab.value)}
                        className={cn(
                            'flex shrink-0 items-center gap-2 text-sm font-medium whitespace-nowrap transition-colors',
                            segmented
                                ? cn(
                                      'h-8 rounded-md px-3',
                                      active
                                          ? 'bg-white text-zinc-950 shadow-xs ring-1 ring-zinc-200'
                                          : 'text-zinc-500 hover:text-zinc-900',
                                  )
                                : cn(
                                      '-mb-px border-b-2 px-3 pt-1 pb-2.5',
                                      active
                                          ? 'border-primary text-zinc-950'
                                          : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-900',
                                  ),
                        )}
                    >
                        {tab.label}
                        {tab.count !== undefined && (
                            <span
                                className={cn(
                                    'rounded-full px-1.5 text-xs tabular-nums',
                                    tab.countTone === 'warning'
                                        ? 'bg-amber-50 text-amber-700 ring-1 ring-amber-200 ring-inset'
                                        : active
                                          ? 'bg-indigo-50 text-indigo-700'
                                          : 'bg-zinc-100 text-zinc-500',
                                )}
                            >
                                {tab.count}
                            </span>
                        )}
                    </button>
                );
            })}
        </div>
    );
}
