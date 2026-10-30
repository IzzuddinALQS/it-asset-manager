import { router, useForm } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { ChevronLeft, ChevronRight, UserPlus } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/components/DataTable';
import DateDisplay from '@/components/DateDisplay';
import FormField from '@/components/FormField';
import OptionSelect from '@/components/OptionSelect';
import SearchInput from '@/components/SearchInput';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { EnumOption } from '@/types/assets';

export interface HerdUserRow {
    nik: string;
    name: string;
    email: string | null;
    department: string | null;
    job_title: string | null;
    nik_functional: string | null;
    is_active: boolean;
    granted_at: string | null;
    local_role_label: string | null;
}

export interface HerdUsers {
    configured: boolean;
    error: string | null;
    data: HerdUserRow[];
    meta: { total: number; page: number; perPage: number; lastPage: number } | null;
}

export interface HerdFilters {
    herd_search: string;
    herd_page: number;
}

/** Employees granted SSO access to this app in HERD; roles themselves stay local. */
export default function HerdAccessSection({
    herdUsers,
    filters,
    roles,
}: {
    herdUsers?: HerdUsers;
    filters: HerdFilters;
    roles: EnumOption[];
}) {
    const [granting, setGranting] = useState<HerdUserRow | null>(null);

    const visit = (params: Partial<HerdFilters>): void => {
        router.get(
            '/settings',
            { ...filters, ...params },
            { only: ['herdUsers', 'herdFilters'], preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const columns: ColumnDef<HerdUserRow, unknown>[] = [
        {
            id: 'name',
            header: 'Karyawan',
            cell: ({ row }) => (
                <div className="min-w-0">
                    <p className="truncate font-medium text-zinc-800">{row.original.name}</p>
                    <p className="text-muted-foreground truncate text-xs">
                        <span className="font-tag">{row.original.nik}</span>
                        {row.original.email && ` · ${row.original.email}`}
                    </p>
                </div>
            ),
        },
        {
            id: 'job_title',
            header: 'Jabatan',
            cell: ({ row }) => (
                <div className="min-w-0 text-sm">
                    <p className="truncate text-zinc-700">{row.original.job_title ?? '—'}</p>
                    {row.original.department && (
                        <p className="text-muted-foreground truncate text-xs">{row.original.department}</p>
                    )}
                </div>
            ),
        },
        {
            id: 'granted_at',
            header: 'Di-grant',
            cell: ({ row }) => <DateDisplay value={row.original.granted_at} />,
        },
        {
            id: 'local_role',
            header: 'Peran di aplikasi',
            cell: ({ row }) =>
                row.original.local_role_label ? (
                    <span className="text-sm text-zinc-700">{row.original.local_role_label}</span>
                ) : row.original.is_active ? (
                    <Button variant="outline" size="sm" onClick={() => setGranting(row.original)}>
                        <UserPlus /> Beri peran
                    </Button>
                ) : (
                    <span className="text-muted-foreground text-xs">Nonaktif di HERD</span>
                ),
        },
    ];

    const meta = herdUsers?.meta ?? null;

    return (
        <section className="mt-8">
            <div className="mb-3 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 className="text-[15px] font-semibold text-zinc-950">Akses dari HERD</h2>
                    <p className="text-muted-foreground text-sm">
                        Karyawan yang di-grant SSO ke IT Asset Management di HERD. Beri peran agar mereka bisa masuk.
                    </p>
                </div>
                <SearchInput
                    defaultValue={filters.herd_search}
                    onSearch={(query) => visit({ herd_search: query, herd_page: 1 })}
                    placeholder="Cari nama, NIK, jabatan"
                />
            </div>

            <div className="border-border bg-card overflow-hidden rounded-xl border">
                {herdUsers === undefined ? (
                    <div className="animate-pulse space-y-3 p-4">
                        {[0, 1, 2].map((index) => (
                            <div key={index} className="h-8 rounded bg-zinc-100" />
                        ))}
                    </div>
                ) : !herdUsers.configured ? (
                    <p className="text-muted-foreground p-4 text-sm">Token API HERD belum dikonfigurasi.</p>
                ) : herdUsers.error ? (
                    <p className="p-4 text-sm text-red-700">{herdUsers.error}</p>
                ) : (
                    <>
                        <DataTable
                            columns={columns}
                            data={herdUsers.data}
                            emptyMessage="Belum ada karyawan yang di-grant di HERD."
                        />
                        {meta && meta.lastPage > 1 && (
                            <div className="border-border text-muted-foreground flex items-center justify-between border-t px-4 py-3 text-xs">
                                <p>
                                    Halaman {meta.page} dari {meta.lastPage} · {meta.total} karyawan
                                </p>
                                <div className="flex gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        disabled={meta.page <= 1}
                                        onClick={() => visit({ herd_page: meta.page - 1 })}
                                        aria-label="Sebelumnya"
                                    >
                                        <ChevronLeft />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        disabled={meta.page >= meta.lastPage}
                                        onClick={() => visit({ herd_page: meta.page + 1 })}
                                        aria-label="Berikutnya"
                                    >
                                        <ChevronRight />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </div>

            <GrantHerdUserDialog employee={granting} onClose={() => setGranting(null)} roles={roles} />
        </section>
    );
}

function GrantHerdUserDialog({
    employee,
    onClose,
    roles,
}: {
    employee: HerdUserRow | null;
    onClose: () => void;
    roles: EnumOption[];
}) {
    const { data, setData, transform, post, processing, errors, reset, clearErrors } = useForm<{ role: string }>({
        role: 'viewer',
    });

    const close = (): void => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = (event: FormEvent): void => {
        event.preventDefault();

        if (employee === null) {
            return;
        }

        transform((form) => ({ ...form, nik: employee.nik }));
        post('/settings/herd-users', { preserveScroll: true, onSuccess: close });
    };

    return (
        <Dialog open={employee !== null} onOpenChange={(next) => !next && close()}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Beri peran</DialogTitle>
                        <DialogDescription>
                            {employee?.name} ({employee?.nik}) akan bisa masuk lewat SSO HERD dengan peran ini.
                        </DialogDescription>
                    </DialogHeader>

                    <FormField label="Peran" htmlFor="herd_role" error={errors.role} required>
                        <OptionSelect
                            id="herd_role"
                            value={data.role}
                            onChange={(value) => setData('role', value ?? 'viewer')}
                            options={roles}
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={close}>
                            Batal
                        </Button>
                        <Button type="submit" loading={processing}>
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
