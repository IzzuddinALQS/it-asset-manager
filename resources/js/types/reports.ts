export type ReportColumnType = 'text' | 'number' | 'currency' | 'percent' | 'date';

export interface ReportColumn {
    key: string;
    label: string;
    type: ReportColumnType;
}

export type ReportRow = Record<string, string | number | null>;

export interface ReportData {
    columns: ReportColumn[];
    rows: ReportRow[];
    totals: ReportRow | null;
}

export interface ReportOption {
    value: string;
    label: string;
    /** The date the period applies to; null for snapshot reports. */
    date_label: string | null;
}
