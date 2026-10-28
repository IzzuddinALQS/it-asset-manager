import { CalendarClock } from 'lucide-react';
import { cn, formatDate } from '@/lib/utils';
import { daysUntil, expiryTone } from '@/types/licenses';

const toneClass = {
    none: 'bg-slate-100 text-slate-600',
    ok: 'bg-emerald-50 text-emerald-700',
    warning: 'bg-amber-100 text-amber-800',
    critical: 'bg-red-100 text-red-700',
    expired: 'bg-red-600 text-white',
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
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap',
                toneClass[tone],
            )}
        >
            <CalendarClock className="size-3.5" />
            {label}
        </span>
    );
}
