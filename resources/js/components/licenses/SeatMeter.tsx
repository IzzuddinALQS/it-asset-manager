import { cn } from '@/lib/utils';

/**
 * Seat usage bar: green while seats remain, amber at 80 %, red when full.
 */
export default function SeatMeter({ used, total, className }: { used: number; total: number; className?: string }) {
    const ratio = total > 0 ? Math.min(1, used / total) : 0;
    const tone = used >= total ? 'bg-red-500' : ratio >= 0.8 ? 'bg-amber-500' : 'bg-emerald-500';

    return (
        <div className={className}>
            <div className="mb-1 flex items-baseline justify-between text-xs">
                <span className="text-muted-foreground">Seat terpakai</span>
                <span className="font-semibold text-slate-800 tabular-nums">
                    {used} / {total}
                </span>
            </div>
            <div
                className="h-2 overflow-hidden rounded-full bg-slate-100"
                role="meter"
                aria-valuemin={0}
                aria-valuemax={total}
                aria-valuenow={used}
                aria-label={`${used} dari ${total} seat terpakai`}
            >
                <div className={cn('h-full rounded-full transition-all', tone)} style={{ width: `${ratio * 100}%` }} />
            </div>
        </div>
    );
}
