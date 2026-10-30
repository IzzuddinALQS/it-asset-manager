import type { Option } from '@/types/assets';

export type InventoryTab = 'components' | 'accessories';

export interface StockListItem {
    id: number;
    name: string;
    /** Spec for components, model for accessories. */
    detail: string | null;
    category: string | null;
    manufacturer: string | null;
    location: string | null;
    qty_total: number;
    /** Installed in assets (components) or on loan (accessories). */
    qty_used: number;
    qty_available: number;
    min_qty: number;
    unit_cost: string | null;
}

export interface StockFormData {
    name: string;
    detail: string;
    category_id: number | null;
    manufacturer_id: number | null;
    qty_total: number | null;
    min_qty: number | null;
    unit_cost: number | null;
    location_id: number | null;
}

export interface AccessoryLoan {
    id: number;
    employee: string | null;
    employee_number: string | null;
    department: string | null;
    qty: number;
    checked_out_at: string | null;
    checked_in_at: string | null;
    note: string | null;
}

export interface AccessoryDetail {
    id: number;
    name: string;
    model: string | null;
    category: string | null;
    manufacturer: string | null;
    location: string | null;
    unit_cost: string | null;
    qty_total: number;
    qty_out: number;
    qty_available: number;
    min_qty: number;
    checkouts: AccessoryLoan[];
}

export interface ComponentOption extends Option {
    spec: string | null;
    qty_available: number;
}

export function isLowStock(item: { qty_available: number; min_qty: number }): boolean {
    return item.qty_available <= item.min_qty;
}
