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
import type { Table } from '@tanstack/react-table';

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
        <div className={className}>
            <MobileRows table={table} emptyMessage={emptyMessage} />
            <div className="hidden overflow-x-auto md:block">
                <table className="w-full min-w-[720px] border-collapse text-sm">
                    <thead>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <tr key={headerGroup.id} className="border-border border-b bg-zinc-50/70">
                                {headerGroup.headers.map((header) => {
                                    const canSort = header.column.getCanSort();
                                    const sorted = header.column.getIsSorted();

                                    return (
                                        <th
                                            key={header.id}
                                            className="h-10 px-4 text-left text-xs font-medium whitespace-nowrap text-zinc-500"
                                        >
                                            {header.isPlaceholder ? null : canSort ? (
                                                <button
                                                    type="button"
                                                    onClick={header.column.getToggleSortingHandler()}
                                                    className="-mx-1 flex items-center gap-1 rounded px-1 py-0.5 transition-colors hover:bg-zinc-100 hover:text-zinc-900"
                                                >
                                                    {flexRender(header.column.columnDef.header, header.getContext())}
                                                    {sorted === 'asc' ? (
                                                        <ArrowUp className="size-3" />
                                                    ) : sorted === 'desc' ? (
                                                        <ArrowDown className="size-3" />
                                                    ) : (
                                                        <ArrowUpDown className="size-3 opacity-30" />
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
                                    className="text-muted-foreground px-4 py-12 text-center text-sm"
                                >
                                    {emptyMessage}
                                </td>
                            </tr>
                        ) : (
                            table.getRowModel().rows.map((row) => (
                                <tr
                                    key={row.id}
                                    className="border-border/70 border-b transition-colors last:border-0 hover:bg-zinc-50"
                                >
                                    {row.getVisibleCells().map((cell) => (
                                        <td key={cell.id} className="px-4 py-3 align-middle text-zinc-700 tabular-nums">
                                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                        </td>
                                    ))}
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

/**
 * Below `md`, each row becomes a card: the first labelled column is the title, the
 * rest are label/value pairs. Unlabelled columns (row checkboxes, actions) sit on the right.
 */
function MobileRows<TData>({ table, emptyMessage }: { table: Table<TData>; emptyMessage: string }) {
    const rows = table.getRowModel().rows;

    if (rows.length === 0) {
        return <p className="text-muted-foreground px-4 py-12 text-center text-sm md:hidden">{emptyMessage}</p>;
    }

    return (
        <ul className="divide-border divide-y md:hidden">
            {rows.map((row) => {
                const cells = row.getVisibleCells();
                const hasLabel = (cell: (typeof cells)[number]): boolean => {
                    const header = cell.column.columnDef.header;

                    return typeof header === 'string' && header !== '';
                };
                const labelled = cells.filter(hasLabel);
                const unlabelled = cells.filter((cell) => !hasLabel(cell));
                const [title, ...details] = labelled;

                return (
                    <li key={row.id} className="flex gap-3 px-4 py-3.5">
                        <div className="min-w-0 flex-1">
                            {title && (
                                <div className="text-[15px] font-medium text-zinc-900">
                                    {flexRender(title.column.columnDef.cell, title.getContext())}
                                </div>
                            )}
                            {details.length > 0 && (
                                <dl className="mt-2 grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1.5 text-sm">
                                    {details.map((cell) => (
                                        <div key={cell.id} className="contents">
                                            <dt className="text-muted-foreground text-[13px]">
                                                {cell.column.columnDef.header as string}
                                            </dt>
                                            <dd className="min-w-0 [overflow-wrap:anywhere] text-zinc-700">
                                                {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            )}
                        </div>
                        {unlabelled.length > 0 && (
                            <div className="flex shrink-0 flex-col items-end gap-2">
                                {unlabelled.map((cell) => (
                                    <div key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</div>
                                ))}
                            </div>
                        )}
                    </li>
                );
            })}
        </ul>
    );
}
