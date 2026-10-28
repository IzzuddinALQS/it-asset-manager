import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface Breadcrumb {
    label: string;
    href?: string;
}

export default function PageHeader({
    title,
    description,
    breadcrumbs = [],
    actions,
    className,
}: {
    title: string;
    description?: string;
    breadcrumbs?: Breadcrumb[];
    actions?: ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('mb-6', className)}>
            {breadcrumbs.length > 0 && (
                <nav className="text-muted-foreground mb-2 flex items-center gap-1 text-[11px]">
                    {breadcrumbs.map((crumb, index) => (
                        <span key={`${crumb.label}-${index}`} className="flex items-center gap-1">
                            {index > 0 && <ChevronRight className="size-3" />}
                            {crumb.href ? (
                                <Link href={crumb.href} className="hover:text-slate-700">
                                    {crumb.label}
                                </Link>
                            ) : (
                                <span className="text-slate-700">{crumb.label}</span>
                            )}
                        </span>
                    ))}
                </nav>
            )}

            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">{title}</h1>
                    {description && <p className="text-muted-foreground mt-1 max-w-2xl text-sm">{description}</p>}
                </div>
                {actions && <div className="flex items-center gap-2">{actions}</div>}
            </div>
        </div>
    );
}
