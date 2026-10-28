import { Head, router } from '@inertiajs/react';
import { Download, FileBarChart } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import EmptyState from '@/components/EmptyState';
import FormField from '@/components/FormField';
import PageHeader from '@/components/PageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout';
import { cn, formatCurrency, formatDate } from '@/lib/utils';
import type { ReportColumn, ReportData, ReportOption, ReportRow } from '@/types/reports';

interface Filters {
    report: string;
    from: string;
    to: string;
}

function queryString(filters: Filters): string {
    return new URLSearchParams(Object.entries(filters).filter(([, value]) => value !== '')).toString();
}

function formatCell(column: ReportColumn, value: ReportRow[string]): string {
    if (value === null || value === '') {
        return '—';
    }

    switch (column.type) {
        case 'currency':
            return formatCurrency(value);
        case 'percent':
            return `${new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(Number(value))}%`;
        case 'number':
            return new Intl.NumberFormat('id-ID').format(Number(value));
        case 'date':
            return formatDate(String(value));
        default:
            return String(value);
    }
}

export default function ReportsIndex({
    filters,
    reports,
    report,
}: {
    filters: Filters;
    reports: ReportOption[];
    report: ReportData;
}) {
    const [period, setPeriod] = useState({ from: filters.from, to: filters.to });
    const current = reports.find((option) => option.value === filters.report) ?? reports[0];
    const totals = report.totals;

    const visit = (changes: Partial<Filters>): void => {
        router.get(
            '/reports',
            Object.fromEntries(Object.entries({ ...filters, ...changes }).filter(([, value]) => value !== '')),
            { preserveScroll: true, replace: true },
        );
    };

    const applyPeriod = (event: FormEvent): void => {
        event.preventDefault();
        visit(period);
    };

    const resetPeriod = (): void => {
        setPeriod({ from: '', to: '' });
        visit({ from: '', to: '' });
    };

    const numeric = (column: ReportColumn): boolean => ['number', 'currency', 'percent'].includes(column.type);

    return (
        <AppLayout>
            <Head title="Laporan" />

            <PageHeader
                title="Laporan"
                description="Ringkasan aset, lisensi, maintenance, dan stok. Ekspor CSV berisi persis data yang ditampilkan."
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Laporan' }]}
                actions={
                    <Button asChild>
                        <a href={`/reports/export?${queryString(filters)}`}>
                            <Download /> Ekspor CSV
                        </a>
                    </Button>
                }
            />

            <div className="mb-4 flex flex-wrap gap-2" role="tablist" aria-label="Jenis laporan">
                {reports.map((option) => {
                    const active = option.value === current.value;

                    return (
                        <button
                            key={option.value}
                            type="button"
                            role="tab"
                            aria-selected={active}
                            onClick={() => visit({ report: option.value })}
                            className={cn(
                                'rounded-full border px-3 py-1.5 text-sm font-medium transition-colors',
                                active
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-border bg-white text-slate-600 hover:bg-slate-50',
                            )}
                        >
                            {option.label}
                        </button>
                    );
                })}
            </div>

            {current.date_label === null ? (
                <p className="text-muted-foreground mb-4 text-sm">
                    Laporan ini adalah posisi saat ini, tanpa filter tanggal.
                </p>
            ) : (
                <form
                    onSubmit={applyPeriod}
                    className="border-border mb-4 flex flex-wrap items-end gap-3 rounded-xl border bg-white p-4 shadow-sm"
                >
                    <FormField label={`${current.date_label} dari`} htmlFor="from">
                        <Input
                            id="from"
                            type="date"
                            value={period.from}
                            onChange={(event) => setPeriod({ ...period, from: event.target.value })}
                            className="w-44"
                        />
                    </FormField>
                    <FormField label="sampai" htmlFor="to">
                        <Input
                            id="to"
                            type="date"
                            value={period.to}
                            min={period.from || undefined}
                            onChange={(event) => setPeriod({ ...period, to: event.target.value })}
                            className="w-44"
                        />
                    </FormField>
                    <Button type="submit" variant="secondary">
                        Terapkan
                    </Button>
                    {(filters.from !== '' || filters.to !== '') && (
                        <Button type="button" variant="ghost" onClick={resetPeriod}>
                            Semua periode
                        </Button>
                    )}
                </form>
            )}

            {report.rows.length === 0 ? (
                <EmptyState
                    icon={FileBarChart}
                    title="Tidak ada data untuk periode ini"
                    description="Ubah rentang tanggal atau pilih laporan lain."
                />
            ) : (
                <div className="border-border overflow-x-auto rounded-xl border bg-white shadow-sm">
                    <table className="w-full min-w-[720px] border-collapse text-sm">
                        <thead>
                            <tr className="border-border border-b bg-slate-50/80">
                                {report.columns.map((column) => (
                                    <th
                                        key={column.key}
                                        className={cn(
                                            'px-4 py-2.5 text-[10px] font-semibold tracking-wider whitespace-nowrap text-slate-500 uppercase',
                                            numeric(column) ? 'text-right' : 'text-left',
                                        )}
                                    >
                                        {column.label}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {report.rows.map((row, index) => (
                                <tr
                                    key={index}
                                    className="border-border/70 border-b last:border-0 hover:bg-slate-50/60"
                                >
                                    {report.columns.map((column) => (
                                        <td
                                            key={column.key}
                                            className={cn(
                                                'px-4 py-2.5 text-slate-700',
                                                numeric(column) && 'text-right tabular-nums',
                                            )}
                                        >
                                            {formatCell(column, row[column.key])}
                                        </td>
                                    ))}
                                </tr>
                            ))}
                        </tbody>
                        {totals && (
                            <tfoot>
                                <tr className="border-border border-t-2 bg-slate-50 font-semibold text-slate-900">
                                    {report.columns.map((column) => (
                                        <td
                                            key={column.key}
                                            className={cn('px-4 py-2.5', numeric(column) && 'text-right tabular-nums')}
                                        >
                                            {totals[column.key] === null ? '' : formatCell(column, totals[column.key])}
                                        </td>
                                    ))}
                                </tr>
                            </tfoot>
                        )}
                    </table>
                </div>
            )}

            <p className="text-muted-foreground mt-3 text-xs">{report.rows.length} baris</p>
        </AppLayout>
    );
}
