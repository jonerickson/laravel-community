import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import LayoutTabs from '@/components/layout-tabs';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { route } from 'ziggy-js';

export default function Appearance() {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Settings',
            href: route('settings'),
        },
        {
            title: 'Appearance Settings',
            href: route('settings.appearance'),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Appearance settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Appearance settings" description="Update your account's appearance settings" />

                    <div className="space-y-4">
                        <div>
                            <h3 className="mb-2 text-sm font-medium text-foreground">Theme</h3>
                            <p className="mb-3 text-sm text-muted-foreground">Choose your preferred theme appearance</p>
                            <AppearanceTabs />
                        </div>

                        <div>
                            <h3 className="mb-2 text-sm font-medium text-foreground">Layout</h3>
                            <p className="mb-3 text-sm text-muted-foreground">Choose between sidebar or header navigation layout</p>
                            <LayoutTabs />
                        </div>
                    </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
