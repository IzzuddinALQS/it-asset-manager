import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import DateDisplay from '@/components/DateDisplay';

describe('DateDisplay', () => {
    it('renders a dash when there is no date', () => {
        render(<DateDisplay value={null} />);

        expect(screen.getByText('—')).toBeInTheDocument();
    });

    it('formats the date in Indonesian with a machine-readable datetime', () => {
        render(<DateDisplay value="2026-11-14" />);

        const time = screen.getByText('14 Nov 2026');
        expect(time.tagName).toBe('TIME');
        expect(time).toHaveAttribute('datetime', '2026-11-14');
    });

    it('adds a relative hint measured in calendar days', () => {
        render(<DateDisplay value="2026-10-31" relative today={new Date(2026, 9, 29, 23, 30)} />);

        expect(screen.getByText('(lusa)')).toBeInTheDocument();
    });
});
