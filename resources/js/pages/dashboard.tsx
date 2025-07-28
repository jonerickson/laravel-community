import AnnouncementsList from '@/components/announcements-list';
import DashboardProductGrid from '@/components/dashboard-product-grid';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type Announcement, type BreadcrumbItem, type Product } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    newestProduct?: Product;
    popularProduct?: Product;
    featuredProduct?: Product;
    announcements?: Announcement[];
}

export default function Dashboard({ newestProduct, popularProduct, featuredProduct, announcements = [] }: DashboardProps) {
    const handleAnnouncementDismiss = (announcementId: number) => {
        console.log('Dismissed announcement:', announcementId);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="relative flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="relative z-10 flex flex-col gap-4">
                    {announcements.length > 0 && <AnnouncementsList announcements={announcements} onDismiss={handleAnnouncementDismiss} />}

                    <DashboardProductGrid newestProduct={newestProduct} popularProduct={popularProduct} featuredProduct={featuredProduct} />

                    <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
