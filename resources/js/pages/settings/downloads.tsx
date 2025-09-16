import { type BreadcrumbItem, type Download, type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';

import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Download as DownloadIcon, File, FileText } from 'lucide-react';

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

interface DownloadsPageProps {
    downloads: Download[];
}

export default function Downloads() {
    const { downloads } = usePage<SharedData>().props as unknown as DownloadsPageProps;

    const getFileIcon = (fileType?: string) => {
        if (!fileType) return File;

        if (fileType.includes('pdf')) return FileText;
        return File;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Downloads" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Downloads" description="Access downloadable files from your purchased products and digital content" />

                    {downloads && downloads.length > 0 ? (
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {downloads.map((download) => {
                                const FileIcon = getFileIcon(download.file_type);

                                return (
                                    <Card key={download.id}>
                                        <CardHeader>
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                                    <FileIcon className="h-5 w-5 text-primary" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <CardTitle className="truncate text-sm">{download.name}</CardTitle>
                                                    {download.product_name && (
                                                        <CardDescription className="text-xs">from {download.product_name}</CardDescription>
                                                    )}
                                                </div>
                                            </div>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            {download.description && <p className="text-sm text-muted-foreground">{download.description}</p>}

                                            <div className="flex items-center justify-between text-xs text-muted-foreground">
                                                {download.file_size && <span>{download.file_size}</span>}
                                                {download.file_type && <span>{download.file_type.toUpperCase()}</span>}
                                            </div>

                                            <Button className="w-full" size="sm" asChild>
                                                <a href={download.download_url} download>
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
                            icon={<DownloadIcon className="h-12 w-12" />}
                            title="No downloads available"
                            description="You don't have any downloadable files yet. Downloads will appear here when you purchase products that include digital files."
                        />
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
