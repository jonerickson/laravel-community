import { useLayout } from '@/hooks/use-layout';
import AppHeaderLayout from '@/layouts/app/app-header-layout';
import AppSidebarLayout from '@/layouts/app/app-sidebar-layout';
import { type BreadcrumbItem } from '@/types';
import { usePage } from '@inertiajs/react';
import { clsx } from 'clsx';
import { type ReactNode } from 'react';

interface AppLayoutProps {
    children: ReactNode;
    breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, breadcrumbs, ...props }: AppLayoutProps) => {
    const { auth } = usePage<App.Data.SharedData>().props;
    const { layout } = useLayout();

    const LayoutComponent = layout === 'header' || !auth?.user ? AppHeaderLayout : AppSidebarLayout;

    return (
        <LayoutComponent breadcrumbs={breadcrumbs} {...props}>
            <div
                className={clsx({
                    'px-6 py-6 lg:px-8': LayoutComponent === AppSidebarLayout,
                    'px-6 py-6 lg:px-4': LayoutComponent === AppHeaderLayout,
                })}
            >
                {children}
            </div>
        </LayoutComponent>
    );
};
