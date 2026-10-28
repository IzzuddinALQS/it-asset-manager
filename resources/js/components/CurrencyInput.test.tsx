import { describe, it, expect, vi } from 'vitest';
import { fireEvent, render, screen } from '@testing-library/react';
import CurrencyInput from '@/components/CurrencyInput';

describe('CurrencyInput', () => {
    it('shows the value with Indonesian thousand separators', () => {
        render(<CurrencyInput aria-label="Harga" value={12500000} onChange={() => {}} />);

        expect(screen.getByLabelText('Harga')).toHaveValue('12.500.000');
    });

    it('emits a plain number and ignores non-digits', () => {
        const onChange = vi.fn();
        render(<CurrencyInput aria-label="Harga" value={null} onChange={onChange} />);

        fireEvent.change(screen.getByLabelText('Harga'), { target: { value: 'Rp 1.250.000' } });

        expect(onChange).toHaveBeenCalledWith(1250000);
    });

    it('emits null when cleared', () => {
        const onChange = vi.fn();
        render(<CurrencyInput aria-label="Harga" value={5000} onChange={onChange} />);

        fireEvent.change(screen.getByLabelText('Harga'), { target: { value: '' } });

        expect(onChange).toHaveBeenCalledWith(null);
    });
});
