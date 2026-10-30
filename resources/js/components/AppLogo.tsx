import { useId } from 'react';
import { cn } from '@/lib/utils';

/**
 * App mark: an isometric asset box with a tag dot, on a blue→indigo tile.
 * Keep in sync with public/favicon.svg.
 */
export default function AppLogo({ className }: { className?: string }) {
    const gradientId = useId();

    return (
        <svg viewBox="0 0 48 48" role="img" aria-label="IT Asset Manager" className={cn('size-10', className)}>
            <defs>
                <linearGradient id={gradientId} x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stopColor="#3b82f6" />
                    <stop offset="1" stopColor="#4f46e5" />
                </linearGradient>
            </defs>
            <rect width="48" height="48" rx="12" fill={`url(#${gradientId})`} />
            <path d="M24 10 37 17.5v13L24 38 11 30.5v-13Z" fill="#fff" fillOpacity=".18" />
            <path
                d="M24 10 37 17.5v13L24 38 11 30.5v-13ZM11 17.5 24 25l13-7.5M24 25v13"
                fill="none"
                stroke="#fff"
                strokeWidth="2.5"
                strokeLinejoin="round"
            />
            <circle cx="30.5" cy="29" r="2.6" fill="#fff" />
        </svg>
    );
}
