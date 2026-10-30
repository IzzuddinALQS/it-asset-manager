import type { ReactNode } from 'react';

/**
 * One group of related fields: title and short guidance on the left, inputs on the
 * right (stacked on small screens). Stack several inside a `divide-y` form card.
 */
export default function FormSection({
    title,
    description,
    children,
}: {
    title: string;
    description?: string;
    children: ReactNode;
}) {
    return (
        <section className="grid gap-x-10 gap-y-4 px-5 py-6 sm:px-6 lg:grid-cols-[220px_minmax(0,1fr)]">
            <div>
                <h2 className="text-[15px] font-semibold text-zinc-950">{title}</h2>
                {description && <p className="text-muted-foreground mt-1 text-[13px] leading-relaxed">{description}</p>}
            </div>
            <div className="min-w-0 space-y-4">{children}</div>
        </section>
    );
}
