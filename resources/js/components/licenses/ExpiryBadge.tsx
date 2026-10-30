import { CalendarClock } from 'lucide-react';
import { cn, formatDate } from '@/lib/utils';
import { daysUntil, expiryTone } from '@/types/licenses';

const toneClass = {
    none: 'bg-zinc-50 text-zinc-600 ring-zinc-200',
    ok: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    warning: 'bg-amber-50 text-amber-800 ring-amber-200',
    critical: 'bg-red-50 text-red-700 ring-red-200',
    expired: 'bg-red-600 text-white ring-red-600',
};

export default function ExpiryBadge({ expiresAt, warningDays }: { expiresAt: string | null; warningDays: number }) {
    const tone = expiryTone(expiresAt, warningDays);
    const days = expiresAt === null ? null : daysUntil(expiresAt);
    const label =
        expiresAt === null || days === null
            ? 'Tanpa masa berlaku'
            : days < 0
              ? `Habis ${formatDate(expiresAt)}`
              : tone === 'ok'
                ? `s.d. ${formatDate(expiresAt)}`
                : `${days === 0 ? 'Habis hari ini' : `${days} hari lagi`} · ${formatDate(expiresAt)}`;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset',
                toneClass[tone],
            )}
        >
            <CalendarClock className="size-3.5" />
            {label}
        </span>
    );
}
