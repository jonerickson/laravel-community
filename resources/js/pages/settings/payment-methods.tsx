import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/settings',
    },
    {
        title: 'Payment Methods',
        href: '/settings/payment-methods',
    },
];

export default function Invoices() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment information" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Payment methods" description="Update and manage your account payment methods" />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
