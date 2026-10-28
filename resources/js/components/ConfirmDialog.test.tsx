import { describe, it, expect, vi } from 'vitest';
import { render, screen, within } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { useState } from 'react';
import { ConfirmProvider } from '@/components/ConfirmDialog';
import { useConfirm } from '@/lib/confirm';

function RetireButton() {
    const confirm = useConfirm();
    const [result, setResult] = useState('belum');

    return (
        <>
            <button
                type="button"
                onClick={async () => {
                    const confirmed = await confirm({
                        title: 'Pensiunkan aset?',
                        description: 'Aset tidak bisa dipakaikan lagi.',
                        confirmLabel: 'Pensiunkan',
                        destructive: true,
                    });
                    setResult(confirmed ? 'ya' : 'tidak');
                }}
            >
                Pensiunkan
            </button>
            <p>Hasil: {result}</p>
        </>
    );
}

describe('ConfirmProvider', () => {
    it('resolves true when the user confirms', async () => {
        const user = userEvent.setup();
        render(
            <ConfirmProvider>
                <RetireButton />
            </ConfirmProvider>,
        );

        await user.click(screen.getByRole('button', { name: 'Pensiunkan' }));
        const dialog = await screen.findByRole('dialog', { name: 'Pensiunkan aset?' });

        await user.click(within(dialog).getByRole('button', { name: 'Pensiunkan' }));

        expect(await screen.findByText('Hasil: ya')).toBeInTheDocument();
    });

    it('resolves false when the user cancels', async () => {
        const user = userEvent.setup();
        render(
            <ConfirmProvider>
                <RetireButton />
            </ConfirmProvider>,
        );

        await user.click(screen.getByRole('button', { name: 'Pensiunkan' }));
        await user.click(await screen.findByRole('button', { name: 'Batal' }));

        expect(await screen.findByText('Hasil: tidak')).toBeInTheDocument();
    });

    it('requires a provider', () => {
        function Orphan() {
            useConfirm();

            return null;
        }

        vi.spyOn(console, 'error').mockImplementation(() => {});

        expect(() => render(<Orphan />)).toThrow('ConfirmProvider');
    });
});
