import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

const toneDot = {
    default: 'bg-zinc-300',
    success: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-red-500',
    info: 'bg-indigo-500',
} as const;

/**
 * KPI tile: label, one big number, optional hint. Colour is limited to a small
 * status dot so a row of tiles reads as one group instead of five competing boxes.
 */
export default function StatCard({
    label,
    value,
    hint,
    icon: Icon,
    tone = 'default',
    valueClassName,
}: {
    label: string;
    value: string | number;
    hint?: string;
    icon?: LucideIcon;
    tone?: keyof typeof toneDot;
    valueClassName?: string;
}) {
    const valueText = String(value);

    const defaultValueSize =
        valueText.length > 18
            ? 'text-base sm:text-lg'
            : valueText.length > 15
              ? 'text-lg sm:text-xl'
              : valueText.length > 12
                ? 'text-xl'
                : 'text-[28px] leading-9';

    return (
        <div className="border-border bg-card rounded-xl border p-5">
            <div className="flex items-center justify-between gap-3">
                <p className="flex items-center gap-2 text-[13px] font-medium text-zinc-600">
                    {tone !== 'default' && <span className={cn('size-2 rounded-full', toneDot[tone])} aria-hidden />}
                    {label}
                </p>
                {Icon && <Icon className="size-4 text-zinc-400" aria-hidden />}
            </div>
            <p
                className={cn(
                    'mt-3 min-w-0 font-semibold tracking-tight [overflow-wrap:anywhere] break-words text-zinc-950 tabular-nums',
                    valueClassName ?? defaultValueSize,
                )}
            >
                {value}
            </p>
            {hint && <p className="text-muted-foreground mt-1 text-xs">{hint}</p>}
        </div>
    );
}
