import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/** Semantic tones shared by every status-like badge: [surface, dot]. */
const badgeTones = {
    neutral: ['bg-zinc-50 text-zinc-700 ring-zinc-200', 'bg-zinc-400'],
    brand: ['bg-indigo-50 text-indigo-700 ring-indigo-200', 'bg-indigo-500'],
    info: ['bg-sky-50 text-sky-700 ring-sky-200', 'bg-sky-500'],
    success: ['bg-emerald-50 text-emerald-700 ring-emerald-200', 'bg-emerald-500'],
    warning: ['bg-amber-50 text-amber-800 ring-amber-200', 'bg-amber-500'],
    orange: ['bg-orange-50 text-orange-800 ring-orange-200', 'bg-orange-500'],
    danger: ['bg-red-50 text-red-700 ring-red-200', 'bg-red-500'],
    violet: ['bg-violet-50 text-violet-700 ring-violet-200', 'bg-violet-500'],
} as const;

export type BadgeTone = keyof typeof badgeTones;

const tones: Record<string, BadgeTone> = {
    pending: 'violet',
    ready: 'success',
    deployed: 'brand',
    maintenance: 'warning',
    retired: 'neutral',
    reported: 'warning',
    in_progress: 'brand',
    waiting_parts: 'orange',
    done: 'success',
    cancelled: 'neutral',
    active: 'success',
    resigned: 'danger',
    transferred: 'warning',
    inactive: 'neutral',
};

const labels: Record<string, string> = {
    pending: 'Menunggu',
    ready: 'Siap Guna',
    deployed: 'Digunakan',
    maintenance: 'Perbaikan',
    retired: 'Pensiun',
    reported: 'Dilaporkan',
    in_progress: 'Dikerjakan',
    waiting_parts: 'Menunggu Sparepart',
    done: 'Selesai',
    cancelled: 'Dibatalkan',
    active: 'Aktif',
    resigned: 'Resign',
    transferred: 'Mutasi',
    inactive: 'Nonaktif',
};

export type StatusValue = keyof typeof tones;

/** Soft pill with a leading dot; the dot carries the colour so the text stays readable. */
export function ToneBadge({ tone, className, children }: { tone: BadgeTone; className?: string; children: ReactNode }) {
    const [surface, dot] = badgeTones[tone];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset',
                surface,
                className,
            )}
        >
            <span className={cn('size-1.5 shrink-0 rounded-full', dot)} aria-hidden />
            {children}
        </span>
    );
}

export default function StatusBadge({
    status,
    label,
    className,
}: {
    status: StatusValue | string;
    label?: string;
    className?: string;
}) {
    return (
        <ToneBadge tone={tones[status] ?? 'neutral'} className={className}>
            {label ?? labels[status] ?? status}
        </ToneBadge>
    );
}
