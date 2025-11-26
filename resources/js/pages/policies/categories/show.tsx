import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Calendar, FileText } from 'lucide-react';

interface PoliciesCategoryProps {
    category: App.Data.PolicyCategoryData;
    policies: App.Data.PolicyData[];
}

export default function PolicyCategoryShow({ category, policies }: PoliciesCategoryProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Policies',
            href: route('policies.index'),
        },
        {
            title: category.name,
            href: route('policies.categories.show', { category: category.slug }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${category.name} - Policies`}>
                <meta name="description" content={category.description || `Policies in ${category.name} category`} />
                <meta property="og:title" content={`${category.name} - Policies - ${siteName}`} />
                <meta property="og:description" content={category.description || `Policies in ${category.name} category`} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <Heading title={category.name} description={category.description || `Browse ${category.name.toLowerCase()} and related documents`} />

                <div className="space-y-6">
                    {policies.map((policy) => (
                        <Card key={policy.id} className="transition-shadow hover:shadow-md">
                            <CardHeader>
                                <div className="flex items-start gap-4">
                                    <div className="flex size-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                        <FileText className="h-6 w-6" />
                                    </div>
                                    <div className="flex-1">
                                        <CardTitle>
                                            <Link
                                                href={route('policies.show', { category: category.slug, policy: policy.slug })}
                                                className="hover:underline"
                                            >
                                                {policy.title}
                                            </Link>
                                        </CardTitle>
                                        {policy.description && <CardDescription className="mt-1">{policy.description}</CardDescription>}
                                        <div className="mt-3 flex items-center gap-4 text-sm text-muted-foreground">
                                            {policy.effectiveAt && (
                                                <div className="flex items-center gap-1">
                                                    <Calendar className="size-4" />
                                                    <span>Effective {new Date(policy.effectiveAt).toLocaleDateString()}</span>
                                                </div>
                                            )}
                                            {policy.version && <span>Version {policy.version}</span>}
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>
                        </Card>
                    ))}
                </div>

                {policies.length === 0 && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <FileText className="mb-4 size-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No Policies Available</CardTitle>
                            <CardDescription>No policies are currently available in this category.</CardDescription>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
