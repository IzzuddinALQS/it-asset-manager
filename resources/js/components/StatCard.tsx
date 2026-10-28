import type { LucideIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

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
    tone?: 'default' | 'success' | 'warning' | 'danger' | 'info';
    valueClassName?: string;
}) {
    const iconTone = {
        default: 'bg-slate-100 text-slate-500',
        success: 'bg-emerald-100 text-emerald-700',
        warning: 'bg-amber-100 text-amber-700',
        danger: 'bg-red-100 text-red-700',
        info: 'bg-blue-100 text-blue-700',
    }[tone];
    const valueText = String(value);

    const defaultValueSize =
        valueText.length > 18
            ? 'text-sm sm:text-base'
            : valueText.length > 15
              ? 'text-base sm:text-lg'
              : valueText.length > 12
                ? 'text-lg sm:text-xl'
                : 'text-3xl';
    return (
        <div className="border-border bg-card rounded-xl border p-5 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <p className="text-muted-foreground text-xs font-medium">{label}</p>
                {Icon && (
                    <span className={cn('flex size-8 items-center justify-center rounded-lg', iconTone)}>
                        <Icon className="size-4" />
                    </span>
                )}
            </div>
            <p
                className={cn(
                    'mt-2 min-w-0 font-bold tracking-tight [overflow-wrap:anywhere] break-words text-slate-900',
                    valueClassName ?? defaultValueSize,
                )}
            >
                {value}
            </p>
            {hint && <p className="text-muted-foreground mt-1 text-[11px]">{hint}</p>}
        </div>
    );
}
