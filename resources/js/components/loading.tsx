import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { LoaderCircle } from 'lucide-react';

interface LoadingProps {
    className?: string;
    variant?: 'default' | 'grid' | 'masonry' | 'forum-post' | 'table' | 'button';
    cols?: number;
    count?: number;
    rows?: number;
}

export default function Loading({ className, variant = 'default', cols = 4, count = 1, rows = 5 }: LoadingProps) {
    if (variant === 'button') {
        return (
            <Button variant="secondary">
                <LoaderCircle className="animate-spin" />
                <div className="h-[1rem] w-[4rem] rounded-md bg-muted-foreground/10" />
            </Button>
        );
    }

    if (variant === 'table') {
        return (
            <div className={cn('overflow-hidden rounded-xl bg-muted/50 dark:bg-muted/30', className)}>
                <div className="w-full">
                    <div className="bg-muted px-4 py-3 dark:bg-muted/30">
                        <div className="flex gap-4">
                            {Array.from({ length: cols }).map((_, i) => (
                                <div
                                    key={i}
                                    className={cn('h-8 animate-pulse rounded bg-muted dark:bg-muted/40', {
                                        'w-1/4': cols === 4,
                                        'w-1/3': cols === 3,
                                        'w-1/2': cols === 2,
                                        'flex-1': cols > 4,
                                    })}
                                />
                            ))}
                        </div>
                    </div>

                    <div className="flex flex-col space-y-4 py-4">
                        {Array.from({ length: rows }).map((_, rowIndex) => (
                            <div key={rowIndex} className="px-4">
                                <div className="flex gap-4">
                                    {Array.from({ length: cols }).map((_, colIndex) => (
                                        <div
                                            key={colIndex}
                                            className={cn('animate-pulse rounded bg-muted p-4 dark:bg-muted/30', {
                                                'w-1/4': cols === 4,
                                                'w-1/3': cols === 3,
                                                'w-1/2': cols === 2,
                                                'flex-1': cols > 4,
                                            })}
                                            style={{
                                                animationDelay: `${(rowIndex * cols + colIndex) * 50}ms`,
                                            }}
                                        />
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        );
    }

    if (variant === 'forum-post') {
        return (
            <div className={cn('space-y-4', className)}>
                {Array.from({ length: count }).map((_, i) => (
                    <div key={i} className="overflow-hidden rounded-xl bg-muted/50 dark:bg-muted/30">
                        <div className="p-6">
                            <div className="flex flex-col gap-4 md:flex-row">
                                <div className="flex min-w-0 flex-row items-start justify-between gap-2 md:w-1/4 md:flex-col md:items-center md:justify-start lg:w-1/6">
                                    <div className="flex flex-row items-center gap-4 md:flex-col md:items-center md:gap-2 md:px-8">
                                        <div className="size-12 animate-pulse rounded-full bg-muted dark:bg-muted/50" />

                                        <div className="flex flex-col items-start gap-2 md:items-center">
                                            <div className="h-4 w-24 animate-pulse rounded bg-muted dark:bg-muted/50" />
                                            <div className="h-3 w-16 animate-pulse rounded bg-muted dark:bg-muted/50" />
                                        </div>
                                    </div>
                                </div>

                                <div className="min-w-0 flex-1">
                                    <div className="mb-4 hidden items-center justify-between md:flex">
                                        <div className="h-4 w-32 animate-pulse rounded bg-muted dark:bg-muted/50" />
                                    </div>

                                    <div className="space-y-2">
                                        <div className="h-4 w-full animate-pulse rounded bg-muted dark:bg-muted/50" />
                                        <div className="h-4 w-5/6 animate-pulse rounded bg-muted dark:bg-muted/50" />
                                        <div className="h-4 w-4/6 animate-pulse rounded bg-muted dark:bg-muted/50" />
                                        <div className="h-4 w-3/6 animate-pulse rounded bg-muted dark:bg-muted/50" />
                                    </div>

                                    <div className="mt-4 flex items-start justify-between rounded-sm bg-muted p-2 dark:bg-muted/30">
                                        <div className="flex gap-2">
                                            <div className="h-8 w-20 animate-pulse rounded bg-muted dark:bg-muted/40" />
                                            <div className="h-8 w-20 animate-pulse rounded bg-muted dark:bg-muted/40" />
                                        </div>
                                        <div className="h-8 w-24 animate-pulse rounded bg-muted dark:bg-muted/40" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        );
    }

    if (variant === 'grid') {
        return (
            <div className={cn('overflow-hidden rounded-xl', className)}>
                <div
                    className={cn('grid grid-cols-1 gap-6', {
                        'sm:grid-cols-1': cols === 1,
                        'sm:grid-cols-2': cols === 2,
                        'sm:grid-cols-3': cols === 3,
                        'sm:grid-cols-4': cols === 4,
                    })}
                >
                    {Array.from({ length: cols }).map((_, i) => (
                        <div key={i} className="relative aspect-square animate-pulse rounded-lg bg-muted/50 dark:bg-muted/30" />
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
                        <div
                            key={i}
                            className={cn('relative mb-6 w-full animate-pulse break-inside-avoid rounded-lg bg-muted/50 dark:bg-muted/30', height)}
                        />
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className={cn('overflow-hidden rounded-xl bg-muted/50 dark:bg-muted/30', className)}>
            <div className="space-y-4 p-6">
                <div className="h-6 w-1/3 animate-pulse rounded bg-muted dark:bg-muted/50" />
                <div className="space-y-2">
                    <div className="h-4 w-full animate-pulse rounded bg-muted dark:bg-muted/50" />
                    <div className="h-4 w-5/6 animate-pulse rounded bg-muted dark:bg-muted/50" />
                    <div className="h-4 w-4/6 animate-pulse rounded bg-muted dark:bg-muted/50" />
                </div>
            </div>
        </div>
    );
}
