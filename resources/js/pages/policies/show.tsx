import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Calendar, FileText, User } from 'lucide-react';

interface PoliciesShowProps {
    category: App.Data.PolicyCategoryData;
    policy: App.Data.PolicyData;
}

export default function PolicyShow({ category, policy }: PoliciesShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Policies',
            href: route('policies.index'),
        },
        {
            title: category.name,
            href: route('policies.categories.show', { category: category.slug }),
        },
        {
            title: policy.title,
            href: route('policies.show', { category: category.slug, policy: policy.slug }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Policies - ${category.name} - ${policy.title}`} />
            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <div className="mb-4">
                    <div className="-mb-7">
                        <Heading title={policy.title} />
                    </div>

                    <div className="flex flex-col gap-2 pt-4 text-sm text-muted-foreground sm:flex-row sm:items-center sm:gap-6">
                        <div className="flex items-center gap-1">
                            <FileText className="size-4" />
                            <span>{category.name}</span>
                        </div>

                        {policy.effectiveAt && (
                            <div className="flex items-center gap-1">
                                <Calendar className="size-4" />
                                <span>Effective {new Date(policy.effectiveAt).toLocaleDateString()}</span>
                            </div>
                        )}

                        {policy.author && (
                            <div className="flex items-center gap-1">
                                <User className="size-4" />
                                <span>Published by {typeof policy.author === 'object' ? policy.author.name : 'Administrator'}</span>
                            </div>
                        )}

                        {policy.version && <span>Version {policy.version}</span>}
                    </div>
                </div>

                <RichEditorContent content={policy.content} />

                {policy.updatedAt && (
                    <div className="mt-6 text-sm text-muted-foreground">Last updated on {new Date(policy.updatedAt).toLocaleDateString()}</div>
                )}
            </div>
        </AppLayout>
    );
}
