import { Head, router, useForm, usePage } from '@inertiajs/react';
import type { ColumnDef } from '@tanstack/react-table';
import { Plus, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import DataTable from '@/components/DataTable';
import DateDisplay from '@/components/DateDisplay';
import FormField from '@/components/FormField';
import OptionSelect from '@/components/OptionSelect';
import PageHeader from '@/components/PageHeader';
import SettingsNav from '@/components/settings/SettingsNav';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout';
import { useConfirm } from '@/lib/confirm';
import type { PageProps, Role } from '@/types';
import type { EmployeeOption, EnumOption } from '@/types/assets';

interface UserRow {
    id: number;
    role: Role;
    role_label: string;
    last_login_at: string | null;
    employee: { id: number; name: string; employee_number: string; department: string | null } | null;
}

interface UsersProps {
    users: UserRow[];
    roles: EnumOption[];
    employees?: EmployeeOption[];
}

export default function SettingsUsers(props: UsersProps) {
    return (
        <AppLayout>
            <UsersPage {...props} />
        </AppLayout>
    );
}

/** Rendered inside AppLayout so `useConfirm` has its provider. */
function UsersPage({ users, roles, employees }: UsersProps) {
    const confirm = useConfirm();
    const currentUserId = usePage<PageProps>().props.auth.user?.id;
    const [adding, setAdding] = useState(false);

    const changeRole = (user: UserRow, role: string): void => {
        router.put(`/settings/users/${user.id}`, { role }, { preserveScroll: true });
    };

    const revoke = async (user: UserRow): Promise<void> => {
        const confirmed = await confirm({
            title: `Cabut akses ${user.employee?.name ?? 'pengguna'}?`,
            description: 'Pengguna tidak bisa masuk lagi sampai diberi akses kembali.',
            confirmLabel: 'Cabut akses',
            destructive: true,
        });

        if (confirmed) {
            router.delete(`/settings/users/${user.id}`, { preserveScroll: true });
        }
    };

    const columns: ColumnDef<UserRow, unknown>[] = [
        {
            id: 'name',
            header: 'Pengguna',
            cell: ({ row }) => (
                <div className="min-w-0">
                    <p className="truncate font-medium text-slate-800">{row.original.employee?.name ?? '—'}</p>
                    <p className="text-muted-foreground truncate text-xs">
                        <span className="font-tag">{row.original.employee?.employee_number}</span>
                        {row.original.employee?.department && ` · ${row.original.employee.department}`}
                    </p>
                </div>
            ),
        },
        {
            id: 'role',
            header: 'Peran',
            cell: ({ row }) =>
                row.original.id === currentUserId ? (
                    <span className="text-sm text-slate-700">{row.original.role_label} (Anda)</span>
                ) : (
                    <OptionSelect
                        value={row.original.role}
                        onChange={(role) => role && changeRole(row.original, role)}
                        options={roles}
                        className="w-44 bg-white"
                    />
                ),
        },
        {
            id: 'last_login_at',
            header: 'Login terakhir',
            cell: ({ row }) => <DateDisplay value={row.original.last_login_at} withTime />,
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) =>
                row.original.id !== currentUserId && (
                    <div className="flex justify-end">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => revoke(row.original)}
                            aria-label={`Cabut akses ${row.original.employee?.name ?? ''}`}
                        >
                            <Trash2 />
                        </Button>
                    </div>
                ),
        },
    ];

    return (
        <>
            <Head title="Pengguna" />

            <PageHeader
                title="Pengaturan"
                breadcrumbs={[{ label: 'Dashboard', href: '/dashboard' }, { label: 'Pengguna' }]}
                actions={
                    <Button
                        onClick={() => {
                            setAdding(true);
                            router.reload({ only: ['employees'] });
                        }}
                    >
                        <Plus /> Tambah pengguna
                    </Button>
                }
            />
            <SettingsNav active="users" />

            <p className="text-muted-foreground mb-4 text-sm">
                Karyawan yang boleh masuk ke aplikasi beserta perannya. Administrator mengelola data master dan
                pengguna, Teknisi IT menjalankan transaksi, Viewer hanya melihat.
            </p>

            <div className="border-border overflow-hidden rounded-xl border bg-white shadow-sm">
                <DataTable columns={columns} data={users} emptyMessage="Belum ada pengguna." />
            </div>

            <AddUserDialog open={adding} onClose={() => setAdding(false)} roles={roles} employees={employees} />
        </>
    );
}

function AddUserDialog({
    open,
    onClose,
    roles,
    employees,
}: {
    open: boolean;
    onClose: () => void;
    roles: EnumOption[];
    employees?: EmployeeOption[];
}) {
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm<{
        employee_id: string | null;
        role: string;
    }>({ employee_id: null, role: 'viewer' });

    const close = (): void => {
        reset();
        clearErrors();
        onClose();
    };

    const submit = (event: FormEvent): void => {
        event.preventDefault();
        post('/settings/users', { preserveScroll: true, onSuccess: close });
    };

    return (
        <Dialog open={open} onOpenChange={(next) => !next && close()}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Tambah pengguna</DialogTitle>
                        <DialogDescription>Beri akses aplikasi kepada karyawan aktif.</DialogDescription>
                    </DialogHeader>

                    <FormField label="Karyawan" htmlFor="employee_id" error={errors.employee_id} required>
                        <OptionSelect
                            id="employee_id"
                            value={data.employee_id}
                            onChange={(value) => setData('employee_id', value)}
                            options={(employees ?? []).map((employee) => ({
                                value: String(employee.id),
                                label: employee.name,
                                hint: employee.department ?? employee.employee_number,
                            }))}
                            placeholder={employees === undefined ? 'Memuat karyawan…' : 'Pilih karyawan'}
                            invalid={errors.employee_id !== undefined}
                        />
                    </FormField>

                    <FormField label="Peran" htmlFor="role" error={errors.role} required>
                        <OptionSelect
                            id="role"
                            value={data.role}
                            onChange={(value) => setData('role', value ?? 'viewer')}
                            options={roles}
                        />
                    </FormField>

                    <DialogFooter>
                        <Button type="button" variant="ghost" onClick={close}>
                            Batal
                        </Button>
                        <Button type="submit" disabled={processing || data.employee_id === null}>
                            Simpan
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
