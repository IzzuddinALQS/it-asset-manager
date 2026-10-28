import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

const masterTypes = [
    { value: 'categories', label: 'Kategori' },
    { value: 'manufacturers', label: 'Manufaktur' },
    { value: 'suppliers', label: 'Supplier' },
    { value: 'locations', label: 'Lokasi' },
    { value: 'models', label: 'Model aset' },
];

/**
 * Sub-navigation shared by the settings pages; admin-only sections are hidden from other roles.
 */
export default function SettingsNav({ active }: { active: string }) {
    const { permissions } = usePage<PageProps>().props;

    const items = [
        { key: 'profile', label: 'Profil', href: '/profile' },
        ...(permissions.manage
            ? [
                  { key: 'users', label: 'Pengguna', href: '/settings' },
                  ...masterTypes.map((type) => ({
                      key: `master:${type.value}`,
                      label: type.label,
                      href: `/settings/master/${type.value}`,
                  })),
              ]
            : []),
    ];

    return (
        <nav className="mb-6 flex flex-wrap gap-1 border-b border-slate-200" aria-label="Pengaturan">
            {items.map((item) => (
                <Link
                    key={item.key}
                    href={item.href}
                    className={cn(
                        '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                        item.key === active
                            ? 'border-primary text-slate-900'
                            : 'border-transparent text-slate-500 hover:text-slate-800',
                    )}
                    aria-current={item.key === active ? 'page' : undefined}
                >
                    {item.label}
                </Link>
            ))}
        </nav>
    );
}
