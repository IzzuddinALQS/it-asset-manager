import { cn } from '@/lib/utils';

const tones = {
    pending: 'bg-[#F3E8FF] text-[#6B21A8]',
    ready: 'bg-[#DCFCE7] text-[#166534]',
    deployed: 'bg-[#DBEAFE] text-[#1E40AF]',
    maintenance: 'bg-[#FEF3C7] text-[#92400E]',
    retired: 'bg-[#F3F4F6] text-[#374151]',
    reported: 'bg-[#FEF3C7] text-[#92400E]',
    in_progress: 'bg-[#DBEAFE] text-[#1E40AF]',
    waiting_parts: 'bg-orange-100 text-orange-800',
    done: 'bg-[#DCFCE7] text-[#166534]',
    cancelled: 'bg-[#F3F4F6] text-[#374151]',
    active: 'bg-[#DCFCE7] text-[#166534]',
    resigned: 'bg-red-100 text-red-800',
    transferred: 'bg-amber-100 text-amber-800',
    inactive: 'bg-[#F3F4F6] text-[#374151]',
} as const;

const labels: Record<string, string> = {
    pending: 'Menunggu',
    ready: 'Siap Pakai',
    deployed: 'Dipakaikan',
    maintenance: 'Perbaikan',
    retired: 'Dipensiunkan',
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

export default function StatusBadge({
    status,
    label,
    className,
}: {
    status: StatusValue | string;
    label?: string;
    className?: string;
}) {
    const tone = tones[status as StatusValue] ?? 'bg-slate-100 text-slate-700';

    return (
        <span
            className={cn(
                'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap',
                tone,
                className,
            )}
        >
            {label ?? labels[status] ?? status}
        </span>
    );
}
