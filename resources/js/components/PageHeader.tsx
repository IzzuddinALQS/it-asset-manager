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
        <div className={cn('mb-6 sm:mb-8', className)}>
            {breadcrumbs.length > 0 && (
                <nav
                    aria-label="Breadcrumb"
                    className="text-muted-foreground mb-3 flex flex-wrap items-center gap-1 text-[13px]"
                >
                    {breadcrumbs.map((crumb, index) => (
                        <span key={`${crumb.label}-${index}`} className="flex items-center gap-1">
                            {index > 0 && <ChevronRight className="size-3.5 text-zinc-300" />}
                            {crumb.href ? (
                                <Link href={crumb.href} className="transition-colors hover:text-zinc-900">
                                    {crumb.label}
                                </Link>
                            ) : (
                                <span className="font-medium text-zinc-900">{crumb.label}</span>
                            )}
                        </span>
                    ))}
                </nav>
            )}

            <div className="flex flex-wrap items-end justify-between gap-x-6 gap-y-4">
                <div className="min-w-0">
                    <h1 className="text-2xl font-semibold tracking-tight text-zinc-950 sm:text-[28px]">{title}</h1>
                    {description && <p className="text-muted-foreground mt-1.5 max-w-2xl text-[15px]">{description}</p>}
                </div>
                {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
            </div>
        </div>
    );
}
