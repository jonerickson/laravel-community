import { cn } from '@/lib/utils';

interface WidgetLoadingProps {
    className?: string;
}

export default function WidgetLoading({ className }: WidgetLoadingProps) {
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
