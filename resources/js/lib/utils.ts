import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function formatCurrency(value: number | string | null | undefined): string {
    const amount = typeof value === 'string' ? Number.parseFloat(value) : (value ?? 0);

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number.isFinite(amount) ? amount : 0);
}

export function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    }).format(new Date(value));
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

/**
 * Calendar-day distance from today in Indonesian, e.g. "besok", "dalam 3 hari", "2 hari yang lalu".
 */
export function relativeDays(value: string, today: Date = new Date()): string {
    const dateOnly = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    const target = dateOnly
        ? new Date(Number(dateOnly[1]), Number(dateOnly[2]) - 1, Number(dateOnly[3]))
        : new Date(value);
    const dayNumber = (date: Date): number =>
        Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()) / 86_400_000;

    return new Intl.RelativeTimeFormat('id-ID', { numeric: 'auto' }).format(
        Math.round(dayNumber(target) - dayNumber(today)),
        'day',
    );
}
