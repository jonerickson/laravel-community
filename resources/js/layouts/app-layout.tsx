import { useLayout } from '@/hooks/use-layout';
import AppHeaderLayout from '@/layouts/app/app-header-layout';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { clsx } from 'clsx';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => {
    const { layout } = useLayout();

    const LayoutComponent = layout === 'header' ? AppHeaderLayout : AppSidebarLayout;

    return (
        <LayoutComponent breadcrumbs={breadcrumbs} {...props}>
            <div
                className={clsx({
                    'px-8 py-6': layout === 'sidebar',
                    'px-4 py-6': layout === 'header',
                })}
            >
                {children}
            </div>
        </LayoutComponent>
    );
};
