import { ToneBadge, type BadgeTone } from '@/components/StatusBadge';
import type { MaintenancePriority } from '@/types/maintenance';

const tones: Record<MaintenancePriority, BadgeTone> = {
    high: 'danger',
    medium: 'warning',
    low: 'neutral',
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
        <ToneBadge tone={tones[priority]} className={className}>
            {label}
        </ToneBadge>
    );
}
