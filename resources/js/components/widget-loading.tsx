import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { cn } from '@/lib/utils';

interface WidgetLoadingProps {
    className?: string;
    title?: string;
    description?: string;
}

export default function WidgetLoading({ className, title = 'Loading...', description = 'Please wait while we load your data.' }: WidgetLoadingProps) {
    return (
        <div className={cn('relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border', className)}>
            <PlaceholderPattern className="absolute inset-0 size-full animate-pulse stroke-neutral-900/20 dark:stroke-neutral-100/20" />
            <div className="relative flex items-center justify-center p-8">
                <div className="space-y-2 text-center">
                    <div className="flex items-center justify-center space-x-2">
                        <div className="size-2 animate-pulse rounded-full bg-foreground/40"></div>
                        <div className="size-2 animate-pulse rounded-full bg-foreground/40" style={{ animationDelay: '0.1s' }}></div>
                        <div className="size-2 animate-pulse rounded-full bg-foreground/40" style={{ animationDelay: '0.2s' }}></div>
                    </div>
                    <h3 className="ml-2 text-sm font-medium text-foreground/70">{title}</h3>
                    <p className="text-xs text-foreground/50">{description}</p>
                </div>
            </div>
        </div>
    );
}
