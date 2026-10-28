import { cn } from '@/lib/utils';
import type { AlertSeverity } from '@/types/alerts';

const tones: Record<AlertSeverity, string> = {
    critical: 'bg-red-100 text-red-800',
    warning: 'bg-amber-100 text-amber-800',
    info: 'bg-blue-100 text-blue-800',
};

export default function SeverityBadge({ severity, label }: { severity: AlertSeverity; label: string }) {
    return (
        <span className={cn('inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold', tones[severity])}>
            {label}
        </span>
    );
}
