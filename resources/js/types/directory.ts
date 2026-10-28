import type { AssetStatus } from '@/types/assets';

export type EmploymentStatus = 'active' | 'resigned' | 'transferred' | 'inactive';

export interface DirectoryEmployee {
    id: number;
    name: string;
    employee_number: string;
    job_title: string | null;
    department: string | null;
    location: string | null;
    employment_status: EmploymentStatus;
    assets_count: number;
    accessories_count: number;
    licenses_count: number;
}

export interface EmployeeDetail {
    id: number;
    name: string;
    employee_number: string;
    email: string | null;
    job_title: string | null;
    department: string | null;
    location: string | null;
    manager: { id: number; name: string } | null;
    employment_status: EmploymentStatus;
    status_effective_date: string | null;
    synced_at: string | null;
}

export interface HeldAsset {
    id: number;
    asset_tag: string;
    model: string | null;
    category: string | null;
    serial_number: string | null;
    status: AssetStatus;
    assigned_at: string | null;
}

export interface HeldAccessory {
    id: number;
    accessory_id: number;
    name: string | null;
    model: string | null;
    qty: number;
    checked_out_at: string;
}

export interface HeldLicense {
    id: number;
    license_id: number;
    name: string | null;
    expires_at: string | null;
    assigned_at: string;
}
