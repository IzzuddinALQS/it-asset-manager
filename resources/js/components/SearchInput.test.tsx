import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { act, fireEvent, render, screen } from '@testing-library/react';
import SearchInput from '@/components/SearchInput';

describe('SearchInput', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('searches once after the user stops typing', () => {
        const onSearch = vi.fn();
        render(<SearchInput placeholder="Cari aset" onSearch={onSearch} />);
        const input = screen.getByLabelText('Cari aset');

        fireEvent.change(input, { target: { value: 'LT' } });
        fireEvent.change(input, { target: { value: 'LT-0001 ' } });
        expect(onSearch).not.toHaveBeenCalled();

        act(() => vi.advanceTimersByTime(300));

        expect(onSearch).toHaveBeenCalledTimes(1);
        expect(onSearch).toHaveBeenCalledWith('LT-0001');
    });

    it('searches immediately on Enter and when cleared', () => {
        const onSearch = vi.fn();
        render(<SearchInput placeholder="Cari aset" defaultValue="dell" onSearch={onSearch} />);

        fireEvent.keyDown(screen.getByLabelText('Cari aset'), { key: 'Enter' });
        expect(onSearch).toHaveBeenLastCalledWith('dell');

        fireEvent.click(screen.getByLabelText('Hapus pencarian'));
        expect(onSearch).toHaveBeenLastCalledWith('');
        expect(screen.getByLabelText('Cari aset')).toHaveValue('');
    });
});
