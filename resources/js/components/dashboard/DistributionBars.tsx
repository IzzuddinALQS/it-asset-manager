import { cn } from '@/lib/utils';

export interface DistributionItem {
    key: string;
    label: string;
    total: number;
}

/** Horizontal bar list; bars are scaled to the largest value so small groups stay readable. */
export default function DistributionBars({
    items,
    colors,
    emptyMessage = 'Belum ada data.',
}: {
    items: DistributionItem[];
    colors?: Record<string, string>;
    emptyMessage?: string;
}) {
    const max = Math.max(1, ...items.map((item) => item.total));
    const sum = items.reduce((total, item) => total + item.total, 0);

    if (sum === 0) {
        return <p className="text-muted-foreground py-6 text-center text-xs">{emptyMessage}</p>;
    }

    return (
        <ul className="space-y-3">
            {items.map((item) => (
                <li key={item.key}>
                    <div className="mb-1 flex items-baseline justify-between gap-2 text-xs">
                        <span className="truncate font-medium text-slate-700">{item.label}</span>
                        <span className="text-muted-foreground tabular-nums">
                            <span className="font-semibold text-slate-900">{item.total}</span> ·{' '}
                            {Math.round((item.total / sum) * 100)}%
                        </span>
                    </div>
                    <div className="h-2 rounded-full bg-slate-100">
                        <div
                            className={cn('h-2 rounded-full', colors?.[item.key] ?? 'bg-primary')}
                            style={{ width: `${(item.total / max) * 100}%` }}
                        />
                    </div>
                </li>
            ))}
        </ul>
    );
}
