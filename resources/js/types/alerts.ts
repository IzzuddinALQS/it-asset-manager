export type AlertSeverity = 'info' | 'warning' | 'critical';

export interface AlertItem {
    id: number;
    type: string;
    type_label: string;
    severity: AlertSeverity;
    severity_label: string;
    message: string;
    due_date: string | null;
    subject_label: string | null;
    subject_url: string | null;
    created_at: string | null;
    resolved_at: string | null;
    resolved_by: string | null;
}
