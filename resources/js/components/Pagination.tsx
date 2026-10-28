import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface PaginatorLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
    links: PaginatorLink[];
}

export default function Pagination({
    paginator,
}: {
    paginator: Pick<Paginator<unknown>, 'links' | 'from' | 'to' | 'total'>;
}) {
    const previous = paginator.links[0];
    const next = paginator.links[paginator.links.length - 1];

    return (
        <div className="border-border text-muted-foreground flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3 text-xs">
            <p>
                Menampilkan <span className="font-semibold text-slate-700">{paginator.from ?? 0}</span>–
                <span className="font-semibold text-slate-700">{paginator.to ?? 0}</span> dari{' '}
                <span className="font-semibold text-slate-700">{paginator.total}</span> data
            </p>

            <div className="flex items-center gap-1">
                <PageLink href={previous?.url ?? null} label="Sebelumnya">
                    <ChevronLeft className="size-3.5" />
                </PageLink>

                {paginator.links.slice(1, -1).map((link, index) => {
                    if (link.label.includes('...')) {
                        return (
                            <span key={`ellipsis-${index}`} className="px-1.5">
                                …
                            </span>
                        );
                    }

                    return (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url ?? '#'}
                            preserveScroll
                            className={cn(
                                'flex h-8 min-w-8 items-center justify-center rounded-md border px-1.5 text-[11px] font-medium',
                                link.active
                                    ? 'border-primary bg-primary text-white'
                                    : 'border-border bg-white text-slate-600 hover:bg-slate-50',
                                link.url === null && 'pointer-events-none opacity-40',
                            )}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    );
                })}

                <PageLink href={next?.url ?? null} label="Berikutnya">
                    <ChevronRight className="size-3.5" />
                </PageLink>
            </div>
        </div>
    );
}

function PageLink({ href, label, children }: { href: string | null; label: string; children: React.ReactNode }) {
    return (
        <Link
            href={href ?? '#'}
            preserveScroll
            aria-label={label}
            className={cn(
                'border-border flex size-8 items-center justify-center rounded-md border bg-white text-slate-600 hover:bg-slate-50',
                href === null && 'pointer-events-none opacity-40',
            )}
        >
            {children}
        </Link>
    );
}
