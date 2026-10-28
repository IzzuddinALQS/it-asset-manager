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
    role_label: string;
    employee: Employee | null;
}

export type NavigationIcon =
    'dashboard' | 'assets' | 'licenses' | 'maintenance' | 'inventory' | 'directory' | 'reports' | 'alerts' | 'settings';

export interface NavigationItem {
    label: string;
    href: string;
    icon: NavigationIcon;
}

export interface NavigationSection {
    group: string;
    items: NavigationItem[];
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
    alerts: {
        open: number;
    };
    permissions: {
        manage: boolean;
        operate: boolean;
    };
    navigation: NavigationSection[];
    [key: string]: unknown;
}
