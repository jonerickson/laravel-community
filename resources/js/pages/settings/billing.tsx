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
        title: 'Billing Information',
        href: '/settings/billing',
    },
];

export default function Billing() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing information" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Billing information" description="Update your account billing information" />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
