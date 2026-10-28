import { cn, formatDate, formatDateTime, relativeDays } from '@/lib/utils';

/**
 * Formats an ISO date for display in Indonesian, optionally with a relative hint ("3 hari lagi").
 */
export default function DateDisplay({
    value,
    withTime = false,
    relative = false,
    today,
    className,
}: {
    value: string | null | undefined;
    withTime?: boolean;
    relative?: boolean;
    today?: Date;
    className?: string;
}) {
    if (!value) {
        return <span className={cn('text-muted-foreground', className)}>—</span>;
    }

    return (
        <time dateTime={value} title={formatDateTime(value)} className={cn('whitespace-nowrap', className)}>
            {withTime ? formatDateTime(value) : formatDate(value)}
            {relative && <span className="text-muted-foreground ml-1">({relativeDays(value, today)})</span>}
        </time>
    );
}
