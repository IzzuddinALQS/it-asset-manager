import { useState } from 'react';
import {
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    useReactTable,
    type ColumnDef,
    type SortingState,
} from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ArrowUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';

interface DataTableProps<TData> {
    columns: ColumnDef<TData, unknown>[];
    data: TData[];
    emptyMessage?: string;
    className?: string;
    /** Server-side sorting: pass the current sort and a handler; rows are then rendered in the given order. */
    sorting?: SortingState;
    onSortingChange?: (sorting: SortingState) => void;
}

export default function DataTable<TData>({
    columns,
    data,
    emptyMessage = 'Belum ada data.',
    className,
    sorting: controlledSorting,
    onSortingChange,
}: DataTableProps<TData>) {
    const [localSorting, setLocalSorting] = useState<SortingState>([]);
    const manualSorting = onSortingChange !== undefined;
    const sorting = manualSorting ? (controlledSorting ?? []) : localSorting;

    // TanStack Table returns non-memoizable functions; React Compiler is not enabled in this build.
    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data,
        columns,
        state: { sorting },
        manualSorting,
        enableSortingRemoval: !manualSorting,
        onSortingChange: (updater) => {
            const next = typeof updater === 'function' ? updater(sorting) : updater;

            if (manualSorting) {
                onSortingChange(next);
            } else {
                setLocalSorting(next);
            }
        },
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
    });

    return (
        <div className={cn('overflow-x-auto', className)}>
            <table className="w-full min-w-[720px] border-collapse text-sm">
                <thead>
                    {table.getHeaderGroups().map((headerGroup) => (
                        <tr key={headerGroup.id} className="border-border border-b bg-slate-50/80">
                            {headerGroup.headers.map((header) => {
                                const canSort = header.column.getCanSort();
                                const sorted = header.column.getIsSorted();

                                return (
                                    <th
                                        key={header.id}
                                        className="px-4 py-2.5 text-left text-[10px] font-semibold tracking-wider text-slate-500 uppercase"
                                    >
                                        {header.isPlaceholder ? null : canSort ? (
                                            <button
                                                type="button"
                                                onClick={header.column.getToggleSortingHandler()}
                                                className="flex items-center gap-1 hover:text-slate-800"
                                            >
                                                {flexRender(header.column.columnDef.header, header.getContext())}
                                                {sorted === 'asc' ? (
                                                    <ArrowUp className="size-3" />
                                                ) : sorted === 'desc' ? (
                                                    <ArrowDown className="size-3" />
                                                ) : (
                                                    <ArrowUpDown className="size-3 opacity-40" />
                                                )}
                                            </button>
                                        ) : (
                                            flexRender(header.column.columnDef.header, header.getContext())
                                        )}
                                    </th>
                                );
                            })}
                        </tr>
                    ))}
                </thead>
                <tbody>
                    {table.getRowModel().rows.length === 0 ? (
                        <tr>
                            <td
                                colSpan={columns.length}
                                className="text-muted-foreground px-4 py-12 text-center text-xs"
                            >
                                {emptyMessage}
                            </td>
                        </tr>
                    ) : (
                        table.getRowModel().rows.map((row) => (
                            <tr key={row.id} className="border-border/70 border-b last:border-0 hover:bg-slate-50/60">
                                {row.getVisibleCells().map((cell) => (
                                    <td key={cell.id} className="px-4 py-3 align-middle text-slate-700">
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </td>
                                ))}
                            </tr>
                        ))
                    )}
                </tbody>
            </table>
        </div>
    );
}
