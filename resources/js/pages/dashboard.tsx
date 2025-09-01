import AnnouncementsList from '@/components/announcements-list';
import DashboardProductGrid from '@/components/dashboard-product-grid';
import SupportTicketWidget from '@/components/support-ticket-widget';
import WidgetLoading from '@/components/widget-loading';
import AppLayout from '@/layouts/app-layout';
import { type Announcement, type BreadcrumbItem, type Product, type SupportTicket } from '@/types';
import { Deferred, Head } from '@inertiajs/react';

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
    supportTickets?: SupportTicket[];
}

export default function Dashboard({ newestProduct, popularProduct, featuredProduct, announcements = [], supportTickets = [] }: DashboardProps) {
    const handleAnnouncementDismiss = (announcementId: number) => {
        console.log('Dismissed announcement:', announcementId);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="relative flex h-full flex-1 flex-col gap-4 overflow-x-auto">
                <div className="relative z-10 flex flex-col gap-4">
                    {announcements.length > 0 && (
                        <Deferred fallback={<WidgetLoading />} data={'announcements'}>
                            <AnnouncementsList announcements={announcements} onDismiss={handleAnnouncementDismiss} />
                        </Deferred>
                    )}

                    <DashboardProductGrid newestProduct={newestProduct} popularProduct={popularProduct} featuredProduct={featuredProduct} />

                    <Deferred fallback={<WidgetLoading />} data={'supportTickets'}>
                        <SupportTicketWidget tickets={supportTickets} />
                    </Deferred>
                </div>
            </div>
        </AppLayout>
    );
}
