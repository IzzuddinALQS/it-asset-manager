import { ToneBadge, type BadgeTone } from '@/components/StatusBadge';
import type { AlertSeverity } from '@/types/alerts';

const tones: Record<AlertSeverity, BadgeTone> = {
    critical: 'danger',
    warning: 'warning',
    info: 'info',
};

export default function SeverityBadge({ severity, label }: { severity: AlertSeverity; label: string }) {
    return <ToneBadge tone={tones[severity]}>{label}</ToneBadge>;
}
