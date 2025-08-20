import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Policy, PolicyCategory } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Calendar, FileText } from 'lucide-react';

interface PoliciesCategoryProps {
    category: PolicyCategory;
    policies: Policy[];
}

export default function PoliciesCategory({ category, policies }: PoliciesCategoryProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Policies',
            href: '/policies',
        },
        {
            title: category.name,
            href: `/policies/${category.slug}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${category.name} - Policies`} />
            <div className="flex h-full flex-1 flex-col overflow-x-auto rounded-xl p-4">
                <Heading title={category.name} description={category.description || `Browse ${category.name.toLowerCase()} and related documents`} />

                <div className="space-y-6">
                    {policies.map((policy) => (
                        <Card key={policy.id} className="transition-shadow hover:shadow-md">
                            <CardHeader>
                                <div className="flex items-start gap-4">
                                    <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 text-primary">
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
                                            {policy.effective_at && (
                                                <div className="flex items-center gap-1">
                                                    <Calendar className="h-4 w-4" />
                                                    <span>Effective {new Date(policy.effective_at).toLocaleDateString()}</span>
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
                            <FileText className="mb-4 h-12 w-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No Policies Available</CardTitle>
                            <CardDescription>No policies are currently available in this category.</CardDescription>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
