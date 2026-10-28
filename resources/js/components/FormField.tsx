import type { ReactNode } from 'react';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

/**
 * Label, control and validation message laid out consistently for every form.
 */
export default function FormField({
    label,
    htmlFor,
    error,
    hint,
    required = false,
    className,
    children,
}: {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    required?: boolean;
    className?: string;
    children: ReactNode;
}) {
    return (
        <div className={cn('space-y-1.5', className)}>
            <Label htmlFor={htmlFor} className="text-sm font-medium text-slate-700">
                {label}
                {required && <span className="text-destructive ml-0.5">*</span>}
            </Label>
            {children}
            {error ? (
                <p className="text-destructive text-xs" role="alert">
                    {error}
                </p>
            ) : (
                hint && <p className="text-muted-foreground text-xs">{hint}</p>
            )}
        </div>
    );
}
