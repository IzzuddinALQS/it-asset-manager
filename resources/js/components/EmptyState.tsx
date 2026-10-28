import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export default function EmptyState({
    icon: Icon,
    title,
    description,
    action,
}: {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <div className="border-border flex flex-col items-center justify-center rounded-xl border border-dashed bg-white px-6 py-14 text-center">
            {Icon && (
                <div className="mb-3 flex size-11 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    <Icon className="size-5" />
                </div>
            )}
            <p className="text-sm font-semibold text-slate-800">{title}</p>
            {description && <p className="text-muted-foreground mt-1 max-w-md text-xs">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
