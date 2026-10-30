import { cn } from '@/lib/utils';

/**
 * "iam" wordmark: the dot of the dotless "ı" is drawn as an asset tag,
 * echoing the tag dot in AppLogo.
 */
export default function AppWordmark({ className, tagClassName }: { className?: string; tagClassName?: string }) {
    return (
        <span
            aria-label="iam"
            className={cn('inline-flex items-baseline font-bold tracking-tight lowercase', className)}
        >
            <span aria-hidden className="relative">
                ı
                <span
                    className={cn(
                        'bg-primary absolute top-[0.02em] left-1/2 size-[0.24em] -translate-x-1/2 rounded-[0.06em]',
                        tagClassName,
                    )}
                />
            </span>
            <span aria-hidden>am</span>
        </span>
    );
}
