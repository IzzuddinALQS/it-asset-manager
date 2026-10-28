import { createContext, useContext } from 'react';

export interface ConfirmOptions {
    title: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
    destructive?: boolean;
}

export type ConfirmFn = (options: ConfirmOptions) => Promise<boolean>;

export const ConfirmContext = createContext<ConfirmFn | null>(null);

/**
 * Opens the shared confirmation dialog and resolves to true when the user confirms.
 */
export function useConfirm(): ConfirmFn {
    const confirm = useContext(ConfirmContext);

    if (confirm === null) {
        throw new Error('useConfirm harus dipakai di dalam ConfirmProvider.');
    }

    return confirm;
}
