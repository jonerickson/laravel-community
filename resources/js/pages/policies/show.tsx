import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Policy, PolicyCategory } from '@/types';
import { Head } from '@inertiajs/react';
import { Calendar, FileText, User } from 'lucide-react';

interface PoliciesShowProps {
    category: PolicyCategory;
    policy: Policy;
}

export default function PolicyShow({ category, policy }: PoliciesShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Policies',
            href: '/policies',
        },
        {
            title: category.name,
            href: `/policies/${category.slug}`,
        },
        {
            title: policy.title,
            href: `/policies/${category.slug}/${policy.slug}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${policy.title} - ${category.name} - Policies`} />
            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <div className="mb-8">
                    <div className="-mb-4">
                        <Heading title={policy.title} />
                    </div>

                    <div className="mt-4 flex flex-wrap items-center gap-6 text-sm text-muted-foreground">
                        <div className="flex items-center gap-1">
                            <FileText className="h-4 w-4" />
                            <span>{category.name}</span>
                        </div>

                        {policy.effective_at && (
                            <div className="flex items-center gap-1">
                                <Calendar className="h-4 w-4" />
                                <span>Effective {new Date(policy.effective_at).toLocaleDateString()}</span>
                            </div>
                        )}

                        {policy.version && <span>Version {policy.version}</span>}

                        {policy.created_by && (
                            <div className="flex items-center gap-1">
                                <User className="h-4 w-4" />
                                <span>Published by {typeof policy.author === 'object' ? policy.author.name : 'Administrator'}</span>
                            </div>
                        )}
                    </div>
                </div>

                <div className="prose prose-slate dark:prose-invert max-w-none" dangerouslySetInnerHTML={{ __html: policy.content }} />

                {policy.updated_at && policy.updated_at !== policy.created_at && (
                    <div className="mt-6 text-sm text-muted-foreground">Last updated on {new Date(policy.updated_at).toLocaleDateString()}</div>
                )}
            </div>
        </AppLayout>
    );
}
