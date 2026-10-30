import type { EnumOption } from '@/types/assets';

export type LicenseStatusFilter = '' | 'expiring' | 'expired' | 'full';

export interface LicenseListItem {
    id: number;
    name: string;
    manufacturer: string | null;
    category: string | null;
    license_type: string;
    license_type_label: string;
    seats_total: number;
    seats_used: number;
    annual_cost: string | null;
    expires_at: string | null;
}

export interface LicenseSeatRow {
    id: number;
    holder_type: 'employee' | 'asset';
    employee: { id: number; name: string; employee_number: string; department: string | null } | null;
    asset: { id: number; asset_tag: string; model: string | null } | null;
    assigned_at: string;
    released_at: string | null;
}

export interface LicenseDetail extends Omit<LicenseListItem, 'seats_used'> {
    supplier: string | null;
    product_key_masked: string | null;
    seats_used: number;
    purchase_order: string | null;
    purchase_date: string | null;
    notes: string | null;
    seats: LicenseSeatRow[];
}

export interface LicenseFormOptions {
    licenseTypes: EnumOption[];
}

/**
 * Whole calendar days from today until a `YYYY-MM-DD` date; negative once it has passed.
 */
export function daysUntil(date: string, today: Date = new Date()): number {
    const [year, month, day] = date.split('-').map(Number);
    const target = Date.UTC(year, month - 1, day);
    const now = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());

    return Math.round((target - now) / 86_400_000);
}

export type ExpiryTone = 'none' | 'ok' | 'warning' | 'critical' | 'expired';

export function expiryTone(expiresAt: string | null, warningDays: number): ExpiryTone {
    if (expiresAt === null) {
        return 'none';
    }

    const days = daysUntil(expiresAt);

    if (days < 0) {
        return 'expired';
    }

    if (days <= 7) {
        return 'critical';
    }

    return days <= warningDays ? 'warning' : 'ok';
}
