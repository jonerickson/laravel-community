import { cn } from '@/lib/utils';

interface LoadingProps {
    className?: string;
    variant?: 'default' | 'grid' | 'masonry';
    cols?: number;
}

export default function Loading({ className, variant = 'default', cols = 4 }: LoadingProps) {
    if (variant === 'grid') {
        return (
            <div className={cn('overflow-hidden rounded-xl', className)}>
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-4">
                    {Array.from({ length: cols }).map((_, i) => (
                        <div key={i} className="aspect-square animate-pulse rounded-lg bg-muted" />
                    ))}
                </div>
            </div>
        );
    }

    if (variant === 'masonry') {
        const heights = ['h-48', 'h-64', 'h-56', 'h-72', 'h-60', 'h-52'];

        return (
            <div className={cn('overflow-hidden rounded-xl', className)}>
                <div className="columns-1 gap-6 sm:columns-2 lg:columns-3">
                    {heights.map((height, i) => (
                        <div key={i} className={cn('mb-6 w-full animate-pulse break-inside-avoid rounded-lg bg-muted', height)} />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className={cn('overflow-hidden rounded-xl border border-sidebar-border/50 dark:border-sidebar-border', className)}>
            <div className="space-y-4 p-6">
                <div className="h-6 w-1/3 animate-pulse rounded bg-muted" />
                <div className="space-y-2">
                    <div className="h-4 w-full animate-pulse rounded bg-muted" />
                    <div className="h-4 w-5/6 animate-pulse rounded bg-muted" />
                    <div className="h-4 w-4/6 animate-pulse rounded bg-muted" />
                </div>
            </div>
        </div>
    );
}
