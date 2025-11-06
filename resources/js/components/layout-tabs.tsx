import { LayoutType, useLayout } from '@/hooks/use-layout';
import { cn } from '@/lib/utils';
import { LucideIcon, Navigation, PanelLeft } from 'lucide-react';
import { HTMLAttributes } from 'react';

export default function LayoutToggleTab({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    const { layout, updateLayout } = useLayout();

    const tabs: { value: LayoutType; icon: LucideIcon; label: string }[] = [
        { value: 'sidebar', icon: PanelLeft, label: 'Sidebar' },
        { value: 'header', icon: Navigation, label: 'Header' },
    ];

    return (
        <div className={cn('relative inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800', className)} {...props}>
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    onClick={() => updateLayout(value)}
                    className={cn(
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                        layout === value
                            ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                            : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
                    )}
                >
                    <Icon className="-ml-1 size-4" />
                    <span className="ml-1.5 text-sm">{label}</span>
                </button>
            ))}
        </div>
    );
}
