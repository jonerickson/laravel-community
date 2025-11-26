import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';

import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Download as DownloadIcon, File } from 'lucide-react';
import { route } from 'ziggy-js';

interface DownloadsPageProps {
    downloads: App.Data.DownloadData[];
}

export default function Downloads() {
    const { downloads } = usePage<App.Data.SharedData>().props as unknown as DownloadsPageProps;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Settings',
            href: route('settings'),
        },
        {
            title: 'Downloads',
            href: route('settings.downloads'),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Downloads" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Downloads" description="Access downloadable files from your purchased products and digital content" />

                    {downloads && downloads.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {downloads.map((download) => {
                                return (
                                    <Card key={download.id}>
                                        <CardHeader>
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                                    <File className="h-5 w-5 text-primary" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <CardTitle className="truncate text-sm">{download.name}</CardTitle>
                                                    {download.productName && (
                                                        <CardDescription className="text-xs">from {download.productName}</CardDescription>
                                                    )}
                                                </div>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            {download.description && <p className="text-sm text-muted-foreground">{download.description}</p>}

                                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                {download.fileSize && <span>{download.fileSize}</span>}
                                                {download.fileType && <span>{download.fileType.toUpperCase()}</span>}
                                            </div>

                                            <Button className="w-full" size="sm" asChild>
                                                <a href={download.downloadUrl} download>
                                                    <DownloadIcon className="mr-2 h-4 w-4" />
                                                    Download
                                                </a>
                                            </Button>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    ) : (
                        <EmptyState
                            icon={<DownloadIcon />}
                            title="No downloads available"
                            description="You don't have any downloadable files yet. Downloads will appear here when you purchase products that include digital files."
                        />
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
