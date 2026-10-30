import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import { Button } from '@/components/ui/button';

describe('Button', () => {
    it('disables itself and shows a spinner while loading', () => {
        render(<Button loading>Simpan</Button>);

        const button = screen.getByRole('button', { name: 'Simpan' });
        expect(button).toBeDisabled();
        expect(button).toHaveAttribute('aria-busy', 'true');
        expect(button.querySelector('svg.animate-spin')).not.toBeNull();
    });

    it('renders its single child as the element when asChild is set', () => {
        render(
            <Button asChild>
                <a href="/assets/create">Tambah</a>
            </Button>,
        );

        const link = screen.getByRole('link', { name: 'Tambah' });
        expect(link).toHaveAttribute('href', '/assets/create');
        expect(link).toHaveAttribute('data-slot', 'button');
    });
});
