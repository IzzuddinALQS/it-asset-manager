import AppLogo from '@/components/AppLogo';
import AppWordmark from '@/components/AppWordmark';
import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import {
    Bell,
    Boxes,
    ChevronDown,
    Database,
    HardDrive,
    LayoutDashboard,
    LogOut,
    Menu,
    PackageSearch,
    PanelLeftClose,
    PanelLeftOpen,
    ScrollText,
    UserRound,
    Users,
    Wrench,
    X,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { ConfirmProvider } from '@/components/ConfirmDialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';
import type { NavigationIcon, NavigationItem, PageProps } from '@/types';
import { toast } from 'sonner';

const icons: Record<NavigationIcon, LucideIcon> = {
    dashboard: LayoutDashboard,
    assets: HardDrive,
    licenses: ScrollText,
    maintenance: Wrench,
    inventory: Boxes,
    directory: Users,
    reports: PackageSearch,
    alerts: Bell,
    settings: Database,
};

const COLLAPSED_STORAGE_KEY = 'sidebar-collapsed';

function readCollapsed(): boolean {
    try {
        return localStorage.getItem(COLLAPSED_STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function initials(name?: string | null): string {
    if (!name) {
        return 'IT';
    }

    return name
        .split(' ')
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

export default function AppLayout({ children }: { children: ReactNode }) {
    const { auth, alerts, flash, navigation } = usePage<PageProps>().props;
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(readCollapsed);
    const openAlerts = alerts?.open ?? 0;

    const toggleCollapsed = (): void => {
        setCollapsed((current) => {
            try {
                localStorage.setItem(COLLAPSED_STORAGE_KEY, current ? '0' : '1');
            } catch {
                // Preference is a convenience; ignore blocked storage.
            }

            return !current;
        });
    };

    const isActive = (item: NavigationItem): boolean =>
        url === item.href || url.startsWith(`${item.href}/`) || url.startsWith(`${item.href}?`);

    useEffect(() => router.on('navigate', () => setMobileOpen(false)), []);

    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }

        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash?.success, flash?.error]);

    return (
        <ConfirmProvider>
            <div className="bg-background min-h-screen">
                <Toaster position="top-right" richColors closeButton />

                {mobileOpen && (
                    <button
                        type="button"
                        aria-label="Tutup menu"
                        className="fixed inset-0 z-30 bg-zinc-950/30 backdrop-blur-[2px] lg:hidden"
                        onClick={() => setMobileOpen(false)}
                    />
                )}

                <aside
                    className={cn(
                        'bg-sidebar text-sidebar-foreground border-border fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r transition-[transform,width] duration-200 lg:w-60 lg:translate-x-0',
                        mobileOpen ? 'translate-x-0 shadow-xl' : '-translate-x-full',
                        collapsed && 'lg:w-[60px]',
                    )}
                >
                    <div
                        className={cn('flex h-14 items-center gap-2.5 px-4', collapsed && 'lg:justify-center lg:px-0')}
                    >
                        <Link
                            href="/dashboard"
                            className="flex min-w-0 items-center gap-2.5"
                            aria-label="iam — Dashboard"
                        >
                            <AppLogo className="size-7 shrink-0" />
                            <span className={cn('min-w-0 leading-none', collapsed && 'lg:hidden')}>
                                <AppWordmark className="text-[19px] text-zinc-950" tagClassName="bg-primary" />
                                <span className="text-muted-foreground mt-0.5 block truncate text-[11px] font-medium">
                                    IT Asset Manager
                                </span>
                            </span>
                        </Link>
                        <button
                            type="button"
                            className="ml-auto rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-900 lg:hidden"
                            onClick={() => setMobileOpen(false)}
                            aria-label="Tutup menu"
                        >
                            <X className="size-5" />
                        </button>
                    </div>

                    <nav
                        aria-label="Navigasi utama"
                        className={cn(
                            'flex-1 scrollbar-thin space-y-5 overflow-y-auto px-3 py-4',
                            collapsed && 'lg:px-2.5',
                        )}
                    >
                        {navigation.map((section) => (
                            <div key={section.group}>
                                <p
                                    className={cn(
                                        'px-2.5 pb-1.5 text-xs font-medium text-zinc-500',
                                        collapsed && 'lg:hidden',
                                    )}
                                >
                                    {section.group}
                                </p>
                                <ul className="space-y-px">
                                    {section.items.map((item) => {
                                        const Icon = icons[item.icon];
                                        const active = isActive(item);

                                        return (
                                            <li key={item.href}>
                                                <Link
                                                    href={item.href}
                                                    title={collapsed ? item.label : undefined}
                                                    aria-current={active ? 'page' : undefined}
                                                    className={cn(
                                                        'group relative flex h-9 items-center gap-2.5 rounded-md px-2.5 text-sm font-medium transition-colors',
                                                        active
                                                            ? 'bg-sidebar-active text-zinc-950 shadow-xs ring-1 ring-zinc-200'
                                                            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950',
                                                        collapsed && 'lg:justify-center lg:px-0',
                                                    )}
                                                >
                                                    <Icon
                                                        className={cn(
                                                            'size-[18px] shrink-0 transition-colors',
                                                            active
                                                                ? 'text-primary'
                                                                : 'text-zinc-400 group-hover:text-zinc-600',
                                                        )}
                                                    />
                                                    <span className={cn('truncate', collapsed && 'lg:hidden')}>
                                                        {item.label}
                                                    </span>
                                                    {item.href === '/alerts' && openAlerts > 0 && (
                                                        <>
                                                            <span
                                                                className={cn(
                                                                    'ml-auto flex h-5 min-w-5 items-center justify-center rounded-full bg-red-50 px-1.5 text-xs font-semibold text-red-700 tabular-nums ring-1 ring-red-200 ring-inset',
                                                                    collapsed && 'lg:hidden',
                                                                )}
                                                            >
                                                                {openAlerts > 99 ? '99+' : openAlerts}
                                                            </span>
                                                            {collapsed && (
                                                                <span className="absolute top-1.5 right-1.5 hidden size-2 rounded-full bg-red-500 lg:block" />
                                                            )}
                                                        </>
                                                    )}
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        ))}
                    </nav>

                    <div className="border-border hidden border-t p-2.5 lg:block">
                        <button
                            type="button"
                            onClick={toggleCollapsed}
                            className={cn(
                                'flex h-9 w-full items-center gap-2.5 rounded-md px-2.5 text-sm text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900',
                                collapsed && 'justify-center px-0',
                            )}
                            aria-label={collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar'}
                            title={collapsed ? 'Lebarkan sidebar' : 'Ciutkan sidebar'}
                        >
                            {collapsed ? (
                                <PanelLeftOpen className="size-[18px]" />
                            ) : (
                                <>
                                    <PanelLeftClose className="size-[18px]" /> Ciutkan
                                </>
                            )}
                        </button>
                    </div>
                </aside>

                <div className={cn('transition-[padding] duration-200', collapsed ? 'lg:pl-[60px]' : 'lg:pl-60')}>
                    <header className="border-border bg-background/85 sticky top-0 z-20 flex h-14 items-center gap-2 border-b px-4 backdrop-blur-md sm:px-6 lg:px-8">
                        <button
                            type="button"
                            className="-ml-1.5 rounded-md p-2 text-zinc-600 hover:bg-zinc-100 lg:hidden"
                            onClick={() => setMobileOpen(true)}
                            aria-label="Buka menu"
                        >
                            <Menu className="size-5" />
                        </button>
                        <Link
                            href="/dashboard"
                            className="flex items-center gap-2 lg:hidden"
                            aria-label="iam — Dashboard"
                        >
                            <AppLogo className="size-6" />
                            <AppWordmark className="text-[17px] text-zinc-950" tagClassName="bg-primary" />
                        </Link>

                        <div className="flex-1" />

                        <Link
                            href="/alerts"
                            className="relative rounded-md p-2 text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900"
                            aria-label={openAlerts > 0 ? `Peringatan (${openAlerts} terbuka)` : 'Peringatan'}
                        >
                            <Bell className="size-[18px]" />
                            {openAlerts > 0 && (
                                <span className="ring-background absolute top-1.5 right-1.5 size-2 rounded-full bg-red-500 ring-2" />
                            )}
                        </Link>

                        <span className="bg-border mx-1 hidden h-5 w-px sm:block" aria-hidden />

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button
                                    type="button"
                                    className="focus-visible:ring-ring/40 flex items-center gap-2 rounded-md py-1 pr-1.5 pl-1 transition-colors outline-none hover:bg-zinc-100 focus-visible:ring-[3px]"
                                >
                                    <span className="flex size-7 items-center justify-center rounded-full bg-indigo-50 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                                        {initials(auth.user?.employee?.name)}
                                    </span>
                                    <span className="hidden text-left leading-tight sm:block">
                                        <span className="block max-w-[160px] truncate text-[13px] font-medium text-zinc-900">
                                            {auth.user?.employee?.name ?? 'Pengguna'}
                                        </span>
                                        <span className="text-muted-foreground block text-xs">
                                            {auth.user?.role_label ?? 'Belum masuk'}
                                        </span>
                                    </span>
                                    <ChevronDown className="size-4 text-zinc-400" />
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-64">
                                <DropdownMenuLabel className="font-normal">
                                    <p className="text-sm font-semibold text-zinc-900">
                                        {auth.user?.employee?.name ?? 'Pengguna'}
                                    </p>
                                    <p className="text-muted-foreground mt-0.5 text-xs">
                                        <span className="font-tag">{auth.user?.employee?.employee_number ?? '—'}</span>
                                        {auth.user?.employee?.department?.name
                                            ? ` · ${auth.user.employee.department.name}`
                                            : ''}
                                    </p>
                                </DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/profile">
                                        <UserRound /> Profil saya
                                    </Link>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href="/logout" method="post" as="button" className="w-full">
                                        <LogOut /> Keluar
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </header>

                    <main className="mx-auto w-full max-w-[1400px] px-4 py-6 sm:px-6 sm:py-8 lg:px-8">{children}</main>
                </div>
            </div>
        </ConfirmProvider>
    );
}
