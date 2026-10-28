import type { Option } from '@/types/assets';

export type MaintenanceStatus = 'reported' | 'in_progress' | 'waiting_parts' | 'done' | 'cancelled';

export type MaintenancePriority = 'low' | 'medium' | 'high';

export interface MaintenanceCard {
    id: number;
    ticket_number: string;
    title: string;
    status: MaintenanceStatus;
    type: string;
    type_label: string;
    priority: MaintenancePriority;
    priority_label: string;
    handled_by_label: string;
    supplier: string | null;
    asset: { id: number; asset_tag: string | null; model: string | null; holder: string | null };
    target_date: string | null;
    is_overdue: boolean;
    completed_at: string | null;
}

export interface MaintenanceDetail {
    id: number;
    ticket_number: string;
    title: string;
    description: string | null;
    status: MaintenanceStatus;
    status_label: string;
    type_label: string;
    priority: MaintenancePriority;
    priority_label: string;
    handled_by_label: string;
    supplier: string | null;
    plan: string | null;
    reported_by: string | null;
    created_by: string | null;
    estimated_cost: string | null;
    actual_cost: string | null;
    start_date: string | null;
    target_date: string | null;
    completed_at: string | null;
    created_at: string | null;
    asset_status_before: string | null;
    asset_status_before_label: string | null;
    asset: {
        id: number;
        asset_tag: string;
        status: string;
        status_label: string;
        model: string | null;
        category: string | null;
        warranty_until: string | null;
        holder: string | null;
    };
    components: { id: number; name: string | null; spec: string | null; qty: number; installed_at: string | null }[];
}

export interface MaintenanceAssetOption {
    id: number;
    asset_tag: string;
    model: string | null;
    status_label: string;
}

export interface SupplierOption extends Option {
    is_service_vendor: boolean;
}

export interface MaintenancePlanRow {
    id: number;
    name: string;
    category_id: number | null;
    category: string | null;
    location_id: number | null;
    location: string | null;
    interval_months: number;
    next_due_date: string;
    pic: string | null;
    supplier_id: number | null;
    supplier: string | null;
    is_active: boolean;
    open_tickets: number;
    covered_assets: number;
}
