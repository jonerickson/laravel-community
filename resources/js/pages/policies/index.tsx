import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { pluralize } from '@/lib/utils';
import type { BreadcrumbItem, PolicyCategory } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileText, Folder } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Policies',
        href: '/policies',
    },
];

interface PoliciesIndexProps {
    categories: PolicyCategory[];
}

export default function PoliciesIndex({ categories }: PoliciesIndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Policies" />
            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <Heading title="Policies" description="Browse our policies, terms of service, and legal documents" />
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {categories.map((category) => (
                        <Card key={category.id} className="transition-shadow hover:shadow-md">
                            <CardHeader>
                                <div className="flex items-start gap-4">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
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
                                            <FileText className="h-4 w-4" />
                                            <span>
                                                {category.active_policies?.length || 0} {pluralize('policy', category.active_policies?.length || 0)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>

                            {category.active_policies && category.active_policies.length > 0 && (
                                <CardContent className="pt-0">
                                    <div className="border-t pt-4">
                                        <div className="mb-3 text-sm font-medium">Recent Policies</div>
                                        <div className="space-y-2">
                                            {category.active_policies.slice(0, 3).map((policy) => (
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
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <FileText className="mb-4 h-12 w-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No Policies Available</CardTitle>
                            <CardDescription>Policy documents will be available here when published.</CardDescription>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
