import { AbstractBackgroundPattern } from '@/components/ui/abstract-background-pattern';
import { SidebarInset } from '@/components/ui/sidebar';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'div'> {
    variant?: 'header' | 'sidebar';
}

export function AppContent({ variant = 'header', children, ...props }: AppContentProps) {
    if (variant === 'sidebar') {
        return <SidebarInset {...props}>{children}</SidebarInset>;
    }

    return (
        <>
            <main className="relative">
                <div className="pointer-events-none absolute inset-0">
                    <AbstractBackgroundPattern showColors={false} opacity={0.08} />
                </div>

                <div className="mx-auto flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl" {...props}>
                    {children}
                </div>
            </main>
        </>
    );
}
