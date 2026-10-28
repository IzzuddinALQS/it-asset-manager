import type { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Boxes,
    ChevronDown,
    HardDrive,
    LayoutDashboard,
    LifeBuoy,
    Menu,
    PackageSearch,
    ScrollText,
    Settings,
    Users,
    Wrench,
    X,
} from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import type { PageProps } from '@/types';

interface NavItem {
    label: string;
    href: string;
    icon: typeof LayoutDashboard;
    match?: string;
}

const navigation: { group: string; items: NavItem[] }[] = [
    {
        group: 'Utama',
        items: [
            { label: 'Dashboard', href: '/', icon: LayoutDashboard, match: '/' },
            { label: 'Aset', href: '/assets', icon: HardDrive, match: '/assets' },
            { label: 'Lisensi', href: '/licenses', icon: ScrollText, match: '/licenses' },
            { label: 'Maintenance', href: '/maintenance', icon: Wrench, match: '/maintenance' },
        ],
    },
    {
        group: 'Inventaris',
        items: [
            { label: 'Komponen & Aksesori', href: '/inventory', icon: Boxes, match: '/inventory' },
            { label: 'Direktori Karyawan', href: '/directory', icon: Users, match: '/directory' },
            { label: 'Laporan', href: '/reports', icon: PackageSearch, match: '/reports' },
        ],
    },
    {
        group: 'Sistem',
        items: [
            { label: 'Peringatan', href: '/alerts', icon: Bell, match: '/alerts' },
            { label: 'Pengaturan', href: '/settings', icon: Settings, match: '/settings' },
        ],
    },
];

const roleLabel: Record<string, string> = {
    admin: 'Administrator',
    technician: 'Teknisi IT',
    viewer: 'Viewer',
};

export default function AppLayout({ children }: { children: ReactNode }) {
    const { auth } = usePage<PageProps>().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const [userMenuOpen, setUserMenuOpen] = useState(false);
    const currentPath = window.location.pathname;

    const isActive = (item: NavItem): boolean =>
        item.match === '/' ? currentPath === '/' : currentPath.startsWith(item.match ?? item.href);

    return (
        <div className="bg-background min-h-screen">
            {mobileOpen && (
                <button
                    type="button"
                    aria-label="Tutup menu"
                    className="fixed inset-0 z-30 bg-black/40 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            <aside
                className={cn(
                    'bg-sidebar text-sidebar-foreground fixed inset-y-0 left-0 z-40 flex w-64 flex-col transition-transform lg:translate-x-0',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full',
                )}
            >
                <div className="flex h-16 items-center gap-3 border-b border-white/10 px-5">
                    <div className="bg-primary text-primary-foreground flex size-9 items-center justify-center rounded-lg text-sm font-bold">
                        AM
                    </div>
                    <div className="min-w-0">
                        <p className="truncate text-sm font-semibold text-white">Asset Manager</p>
                        <p className="text-sidebar-muted truncate text-[11px]">IT Operations</p>
                    </div>
                    <button
                        type="button"
                        className="text-sidebar-muted ml-auto lg:hidden"
                        onClick={() => setMobileOpen(false)}
                        aria-label="Tutup"
                    >
                        <X className="size-5" />
                    </button>
                </div>

                <nav className="flex-1 scrollbar-thin space-y-6 overflow-y-auto px-3 py-5">
                    {navigation.map((section) => (
                        <div key={section.group}>
                            <p className="px-3 pb-2 text-[10px] font-semibold tracking-widest text-slate-500 uppercase">
                                {section.group}
                            </p>
                            <ul className="space-y-0.5">
                                {section.items.map((item) => {
                                    const Icon = item.icon;
                                    const active = isActive(item);

                                    return (
                                        <li key={item.href}>
                                            <Link
                                                href={item.href}
                                                onClick={() => setMobileOpen(false)}
                                                className={cn(
                                                    'flex items-center gap-3 rounded-md px-3 py-2 text-[13px] font-medium transition-colors',
                                                    active
                                                        ? 'bg-sidebar-active text-white'
                                                        : 'text-sidebar-foreground hover:bg-white/5 hover:text-white',
                                                )}
                                            >
                                                <Icon
                                                    className={cn(
                                                        'size-4',
                                                        active ? 'text-blue-400' : 'text-slate-500',
                                                    )}
                                                />
                                                {item.label}
                                            </Link>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    ))}
                </nav>

                <div className="border-t border-white/10 p-3">
                    <div className="text-sidebar-muted rounded-lg bg-white/5 p-3 text-[11px] leading-relaxed">
                        <p className="flex items-center gap-1.5 font-semibold text-slate-200">
                            <LifeBuoy className="size-3.5" /> Bantuan
                        </p>
                        <p className="mt-1">Panduan pemakaian &amp; alur aset ada di menu Laporan.</p>
                    </div>
                </div>
            </aside>

            <div className="lg:pl-64">
                <header className="border-border sticky top-0 z-20 flex h-16 items-center gap-4 border-b bg-white/90 px-4 backdrop-blur sm:px-6">
                    <button
                        type="button"
                        className="rounded-md p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                        onClick={() => setMobileOpen(true)}
                        aria-label="Buka menu"
                    >
                        <Menu className="size-5" />
                    </button>

                    <div className="flex-1">
                        <p className="text-muted-foreground text-[11px] font-medium tracking-wide uppercase">
                            IT Asset Manager
                        </p>
                    </div>

                    <Link
                        href="/alerts"
                        className="relative rounded-md p-2 text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-900"
                        aria-label="Peringatan"
                    >
                        <Bell className="size-5" />
                        <span className="absolute top-1.5 right-1.5 size-2 rounded-full bg-red-500 ring-2 ring-white" />
                    </Link>

                    <div className="relative">
                        <button
                            type="button"
                            onClick={() => setUserMenuOpen((open) => !open)}
                            className="flex items-center gap-2 rounded-full py-1 pr-2 pl-1 transition-colors hover:bg-slate-100"
                        >
                            <span className="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-full text-xs font-bold">
                                {auth.user?.employee?.name?.slice(0, 2).toUpperCase() ?? 'IT'}
                            </span>
                            <span className="hidden text-left sm:block">
                                <span className="block max-w-[160px] truncate text-xs font-semibold text-slate-800">
                                    {auth.user?.employee?.name ?? 'Pengguna'}
                                </span>
                                <span className="text-muted-foreground block text-[10px]">
                                    {auth.user ? roleLabel[auth.user.role] : 'Belum masuk'}
                                </span>
                            </span>
                            <ChevronDown className="size-4 text-slate-400" />
                        </button>

                        {userMenuOpen && (
                            <div className="border-border absolute right-0 mt-2 w-56 overflow-hidden rounded-lg border bg-white shadow-lg">
                                <div className="border-border border-b px-4 py-3">
                                    <p className="text-sm font-semibold text-slate-800">
                                        {auth.user?.employee?.name ?? 'Pengguna'}
                                    </p>
                                    <p className="font-tag text-muted-foreground text-[11px]">
                                        {auth.user?.employee?.employee_number ?? '—'}
                                    </p>
                                </div>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="block w-full px-4 py-2.5 text-left text-sm text-slate-600 hover:bg-slate-50"
                                >
                                    Keluar
                                </Link>
                            </div>
                        )}
                    </div>
                </header>

                <main className="mx-auto w-full max-w-[1400px] px-4 py-6 sm:px-6 lg:px-8">{children}</main>
            </div>
        </div>
    );
}
