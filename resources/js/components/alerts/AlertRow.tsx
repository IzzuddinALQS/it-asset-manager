import { Link } from '@inertiajs/react';
import { ArrowUpRight, CheckCircle2 } from 'lucide-react';
import SeverityBadge from '@/components/alerts/SeverityBadge';
import DateDisplay from '@/components/DateDisplay';
import { Button } from '@/components/ui/button';
import type { AlertItem } from '@/types/alerts';

export default function AlertRow({ alert, onResolve }: { alert: AlertItem; onResolve?: () => void }) {
    return (
        <li className="flex flex-col gap-2 px-4 py-3 sm:flex-row sm:items-start sm:gap-4">
            <div className="min-w-0 flex-1">
                <div className="flex flex-wrap items-center gap-2">
                    <SeverityBadge severity={alert.severity} label={alert.severity_label} />
                    <span className="text-xs font-medium text-slate-500">{alert.type_label}</span>
                    {alert.subject_label &&
                        (alert.subject_url ? (
                            <Link
                                href={alert.subject_url}
                                className="text-primary inline-flex items-center gap-0.5 text-xs font-semibold hover:underline"
                            >
                                {alert.subject_label}
                                <ArrowUpRight className="size-3" />
                            </Link>
                        ) : (
                            <span className="text-xs font-semibold text-slate-700">{alert.subject_label}</span>
                        ))}
                </div>
                <p className="mt-1 text-sm text-slate-800">{alert.message}</p>
                <p className="text-muted-foreground mt-1 text-[11px]">
                    {alert.due_date && (
                        <>
                            Tenggat <DateDisplay value={alert.due_date} relative /> ·{' '}
                        </>
                    )}
                    Dibuat <DateDisplay value={alert.created_at} />
                    {alert.resolved_at && (
                        <>
                            {' '}
                            · Selesai <DateDisplay value={alert.resolved_at} withTime />
                            {alert.resolved_by ? ` oleh ${alert.resolved_by}` : ' (otomatis)'}
                        </>
                    )}
                </p>
            </div>
            {onResolve && (
                <Button size="sm" variant="outline" onClick={onResolve} className="shrink-0">
                    <CheckCircle2 /> Tandai selesai
                </Button>
            )}
        </li>
    );
}
