import * as React from 'react';

import { cn } from '@/lib/utils';

interface CardProps extends React.ComponentProps<'div'> {
    gradient?: boolean;
}

function Card({ className, gradient = false, ...props }: CardProps) {
    if (gradient) {
        return (
            <div className="group relative rounded-xl p-[1px]">
                <div className="absolute inset-0 rounded-xl bg-gradient-to-br from-indigo-500/20 via-purple-500/20 to-pink-500/20 opacity-0 transition-opacity duration-300 group-hover:opacity-100" />
                <div className="absolute inset-0 rounded-xl bg-gradient-to-br from-indigo-500/10 via-purple-500/10 to-pink-500/10" />
                <div
                    data-slot="card"
                    className={cn('relative flex flex-col gap-6 rounded-xl bg-card py-6 text-card-foreground shadow-sm', className)}
                    {...props}
                />
            </div>
        );
    }

    return (
        <div
            data-slot="card"
            className={cn('relative flex flex-col gap-6 rounded-xl border bg-card py-6 text-card-foreground shadow-sm', className)}
            {...props}
        />
    );
}

function CardHeader({ className, ...props }: React.ComponentProps<'div'>) {
    return <div data-slot="card-header" className={cn('flex flex-col gap-1.5 px-6', className)} {...props} />;
}

function CardTitle({ className, ...props }: React.ComponentProps<'div'>) {
    return <div data-slot="card-title" className={cn('leading-none font-semibold', className)} {...props} />;
}

function CardDescription({ className, ...props }: React.ComponentProps<'div'>) {
    return <div data-slot="card-description" className={cn('text-sm text-muted-foreground', className)} {...props} />;
}

function CardContent({ className, ...props }: React.ComponentProps<'div'>) {
    return <div data-slot="card-content" className={cn('px-6', className)} {...props} />;
}

function CardFooter({ className, ...props }: React.ComponentProps<'div'>) {
    return <div data-slot="card-footer" className={cn('flex items-center px-6', className)} {...props} />;
}

export { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle };
