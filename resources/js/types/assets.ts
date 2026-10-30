export type AssetStatus = 'pending' | 'ready' | 'deployed' | 'maintenance' | 'retired';

export interface Option {
    id: number;
    name: string;
}

export interface EnumOption {
    value: string;
    label: string;
}

export interface AssetListItem {
    id: number;
    asset_tag: string;
    serial_number: string | null;
    status: AssetStatus;
    status_label: string;
    model: string | null;
    manufacturer: string | null;
    category: string | null;
    holder: string | null;
    holder_type: 'employee' | 'location' | null;
    location: string | null;
    purchase_date: string | null;
    purchase_cost: string | null;
    warranty_until: string | null;
}

export interface AssetLogEntry {
    id: number;
    action: string;
    action_label: string;
    from_status: AssetStatus | null;
    from_status_label: string | null;
    to_status: AssetStatus | null;
    to_status_label: string | null;
    note: string | null;
    employee: string | null;
    location: string | null;
    performed_by: string | null;
    created_at: string | null;
}

export interface AssetDetail {
    id: number;
    asset_tag: string;
    serial_number: string | null;
    imei: string | null;
    phone_number: string | null;
    qr_svg: string;
    status: AssetStatus;
    status_label: string;
    status_note: string;
    model: string | null;
    model_number: string | null;
    category: string | null;
    manufacturer: string | null;
    holder: {
        type: 'employee';
        id: number;
        name: string;
        employee_number: string;
        job_title: string | null;
        department: string | null;
    } | null;
    assigned_location: { type: 'location'; id: number; name: string } | null;
    assigned_at: string | null;
    expected_return_date: string | null;
    location: string | null;
    supplier: string | null;
    purchase_order: string | null;
    purchase_date: string | null;
    purchase_cost: string | null;
    warranty_until: string | null;
    last_audited_at: string | null;
    notes: string | null;
    retired_at: string | null;
    retire_reason: string | null;
    retire_reason_label: string | null;
    created_at: string | null;
    created_by: string | null;
    logs: AssetLogEntry[];
    components: {
        id: number;
        name: string | null;
        spec: string | null;
        category: string | null;
        qty: number;
        is_factory: boolean;
        installed_at: string | null;
    }[];
    licenses: { id: number; name: string | null; assigned_at: string | null; expires_at: string | null }[];
    maintenances: {
        id: number;
        ticket_number: string;
        title: string;
        status: string;
        status_label: string;
        type_label: string;
        created_at: string | null;
    }[];
    open_maintenance: { id: number; ticket_number: string } | null;
}

export interface EmployeeOption extends Option {
    employee_number: string;
    department: string | null;
}

export interface AssetModelOption extends Option {
    model_number: string | null;
    category: string | null;
    manufacturer: string | null;
}

export interface AssetFormData {
    asset_model_id: number | null;
    serial_number: string;
    imei: string;
    phone_number: string;
    location_id: number | null;
    supplier_id: number | null;
    purchase_order: string;
    purchase_date: string;
    purchase_cost: number | null;
    warranty_until: string;
    notes: string;
}
