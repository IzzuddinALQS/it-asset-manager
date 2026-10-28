import { cn } from '@/lib/utils';
import type { MaintenancePriority } from '@/types/maintenance';

const tones: Record<MaintenancePriority, string> = {
    high: 'bg-red-100 text-red-800',
    medium: 'bg-amber-100 text-amber-800',
    low: 'bg-slate-100 text-slate-600',
};

export default function PriorityBadge({
    priority,
    label,
    className,
}: {
    priority: MaintenancePriority;
    label: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap',
                tones[priority],
                className,
            )}
        >
            {label}
        </span>
    );
}
