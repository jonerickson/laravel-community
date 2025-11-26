import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

interface PageShowProps {
    page: App.Data.PageData;
}

export default function PageShow({ page }: PageShowProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const scriptExecutedRef = useRef(false);
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: page.title,
            href: '#',
        },
    ];

    const pageDescription = page.description || `${page.title} - ${siteName}`;

    useEffect(() => {
        if (page.jsContent && !scriptExecutedRef.current) {
            try {
                const script = document.createElement('script');
                script.textContent = page.jsContent;
                document.body.appendChild(script);
                scriptExecutedRef.current = true;

                return () => {
                    document.body.removeChild(script);
                };
            } catch (error) {
                console.error('Error executing page script:', error);
            }
        }
    }, [page.jsContent]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={page.title}>
                <meta name="description" content={pageDescription} />
                <meta property="og:title" content={`${page.title} - ${siteName}`} />
                <meta property="og:description" content={pageDescription} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
                {page.cssContent && <style dangerouslySetInnerHTML={{ __html: page.cssContent }} />}
            </Head>

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <article className="mx-auto w-full max-w-7xl">
                    <div dangerouslySetInnerHTML={{ __html: page.htmlContent }} />
                </article>
            </div>
        </AppLayout>
    );
}
