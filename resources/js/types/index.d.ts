export type Role = 'admin' | 'technician' | 'viewer';

export interface Employee {
    id: number;
    herd_id: string;
    employee_number: string;
    name: string;
    email: string | null;
    job_title: string | null;
    department?: { id: number; name: string } | null;
    location?: { id: number; name: string } | null;
}

export interface AuthUser {
    id: number;
    role: Role;
    employee: Employee | null;
}

export interface Flash {
    success?: string;
    error?: string;
}

export interface PageProps {
    auth: {
        user: AuthUser | null;
    };
    flash: Flash;
    appName: string;
    [key: string]: unknown;
}
