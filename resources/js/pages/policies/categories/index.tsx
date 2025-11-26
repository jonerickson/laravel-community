import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { pluralize } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { File, FileText, Folder } from 'lucide-react';
import { route } from 'ziggy-js';

interface PoliciesIndexProps {
    categories: App.Data.PolicyCategoryData[];
}

export default function PolicyCategoryIndex({ categories }: PoliciesIndexProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Policies',
            href: route('policies.index'),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Policies">
                <meta name="description" content="Browse our policies, terms of service, and legal documents" />
                <meta property="og:title" content={`Policies - ${siteName}`} />
                <meta property="og:description" content="Browse our policies, terms of service, and legal documents" />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <Heading title="Policies" description="Browse our policies, terms of service, and legal documents" />
                <div className="grid gap-6 md:grid-cols-2">
                    {categories.map((category) => (
                        <Card key={category.id} className="transition-shadow hover:shadow-md">
                            <CardHeader>
                                <div className="flex items-start gap-4">
                                    <div className="flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <Folder className="h-6 w-6" />
                                    </div>
                                    <div className="flex-1">
                                        <CardTitle>
                                            <Link href={route('policies.categories.show', { category: category.slug })} className="hover:underline">
                                                {category.name}
                                            </Link>
                                        </CardTitle>
                                        {category.description && <CardDescription className="mt-1">{category.description}</CardDescription>}
                                        <div className="mt-3 flex items-center gap-1 text-sm text-muted-foreground">
                                            <FileText className="size-4" />
                                            <span>
                                                {category.activePolicies?.length || 0} {pluralize('policy', category.activePolicies?.length || 0)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>

                            {category.activePolicies && category.activePolicies.length > 0 && (
                                <CardContent className="pt-0">
                                    <div className="border-t pt-4">
                                        <div className="mb-3 text-sm font-medium">Recent Policies</div>
                                        <div className="space-y-2">
                                            {category.activePolicies.slice(0, 3).map((policy) => (
                                                <div key={policy.id}>
                                                    <Link
                                                        href={route('policies.show', { category: category.slug, policy: policy.slug })}
                                                        className="block text-sm hover:underline"
                                                    >
                                                        {policy.title}
                                                    </Link>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </CardContent>
                            )}
                        </Card>
                    ))}
                </div>

                {categories.length === 0 && (
                    <EmptyState icon={<File />} title="No policies available" description="Policy documents will be available here when published." />
                )}
            </div>
        </AppLayout>
    );
}
