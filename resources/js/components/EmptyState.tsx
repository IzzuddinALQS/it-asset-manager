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
        <div className="border-border flex flex-col items-center justify-center rounded-xl border border-dashed bg-zinc-50/50 px-6 py-14 text-center">
            {Icon && (
                <div className="mb-4 flex size-11 items-center justify-center rounded-lg bg-white text-zinc-500 ring-1 ring-zinc-200">
                    <Icon className="size-5" />
                </div>
            )}
            <p className="text-[15px] font-semibold text-zinc-900">{title}</p>
            {description && <p className="text-muted-foreground mt-1 max-w-md text-sm">{description}</p>}
            {action && <div className="mt-4">{action}</div>}
        </div>
    );
}
