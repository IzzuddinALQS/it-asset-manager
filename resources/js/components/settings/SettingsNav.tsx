import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

const masterTypes = [
    { value: 'categories', label: 'Kategori' },
    { value: 'manufacturers', label: 'Manufaktur' },
    { value: 'suppliers', label: 'Supplier' },
    { value: 'locations', label: 'Lokasi' },
    { value: 'models', label: 'Model inventaris' },
];

/**
 * Sub-navigation shared by the Master Data pages (admin only).
 */
export default function SettingsNav({ active }: { active: string }) {
    const { permissions } = usePage<PageProps>().props;

    const items = permissions.manage
        ? [
              { key: 'users', label: 'Pengguna', href: '/settings' },
              ...masterTypes.map((type) => ({
                  key: `master:${type.value}`,
                  label: type.label,
                  href: `/settings/master/${type.value}`,
              })),
          ]
        : [];

    return (
        <nav className="border-border mb-6 flex scrollbar-thin gap-1 overflow-x-auto border-b" aria-label="Master Data">
            {items.map((item) => (
                <Link
                    key={item.key}
                    href={item.href}
                    className={cn(
                        '-mb-px shrink-0 border-b-2 px-3 pt-1 pb-2.5 text-sm font-medium whitespace-nowrap transition-colors',
                        item.key === active
                            ? 'border-primary text-zinc-950'
                            : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-900',
                    )}
                    aria-current={item.key === active ? 'page' : undefined}
                >
                    {item.label}
                </Link>
            ))}
        </nav>
    );
}
