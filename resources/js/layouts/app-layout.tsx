import AnnouncementsList from '@/components/announcements-list';
import { EmailVerificationBanner } from '@/components/email-verification-banner';
import { UserWarningBanner } from '@/components/user-warning-banner';
import { useFingerprint } from '@/hooks';
import { useFlashMessages } from '@/hooks/use-flash-messages';
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
    background?: boolean;
}

export default ({ children, breadcrumbs, background = false, ...props }: AppLayoutProps) => {
    const { auth, announcements } = usePage<App.Data.SharedData>().props;
    const { layout } = useLayout();

    useFlashMessages();
    useFingerprint();

    const LayoutComponent = layout === 'header' || !auth?.user ? AppHeaderLayout : AppSidebarLayout;

    return (
        <LayoutComponent breadcrumbs={breadcrumbs} background={background} {...props}>
            <div
                className={clsx({
                    'px-6 py-6 lg:px-8': LayoutComponent === AppSidebarLayout,
                    'px-6 py-6 lg:px-4': LayoutComponent === AppHeaderLayout,
                })}
            >
                <div className="flex flex-col space-y-6">
                    <EmailVerificationBanner />
                    <UserWarningBanner />
                    <AnnouncementsList announcements={announcements} />
                    {children}
                </div>
            </div>
        </LayoutComponent>
    );
};
