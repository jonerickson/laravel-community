import { EmptyState } from '@/components/empty-state';
import ForumCategoryCard from '@/components/forum-category-card';
import ForumSelectionDialog from '@/components/forum-selection-dialog';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Head, router, usePage } from '@inertiajs/react';
import { MessageSquare, Plus } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface ForumsIndexProps {
    categories: App.Data.ForumCategoryData[];
}

export default function ForumCategoryIndex({ categories }: ForumsIndexProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const canStartNewTopic = categories.some((category) => category.forumPermissions.canCreate);

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: `${siteName} Forums`,
        description: 'Connect with our community and get support',
        url: route('forums.index'),
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        hasPart: categories.map((category) => ({
            '@type': 'CollectionPage',
            name: category.name,
            description: category.description || `Forums in ${category.name} category`,
            url: route('forums.categories.show', { category: category.slug }),
            numberOfItems: category.forums?.length || 0,
            hasPart:
                category.forums?.map((forum) => ({
                    '@type': 'CollectionPage',
                    name: forum.name,
                    description: forum.description,
                    url: route('forums.show', { forum: forum.slug }),
                    interactionStatistic: [
                        {
                            '@type': 'InteractionCounter',
                            interactionType: 'https://schema.org/CommentAction',
                            userInteractionCount: forum.topicsCount || 0,
                        },
                        {
                            '@type': 'InteractionCounter',
                            interactionType: 'https://schema.org/ReplyAction',
                            userInteractionCount: forum.postsCount || 0,
                        },
                    ],
                })) || [],
        })),
        numberOfItems: categories.length,
    };

    return (
        <AppLayout>
            <Head title="Forums">
                <meta name="description" content="Connect with our community and get support through our forums" />
                <meta property="og:title" content={`Forums - ${siteName}`} />
                <meta property="og:description" content="Connect with our community and get support through our forums" />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start sm:gap-0">
                    <div className="flex items-start gap-4">
                        <div className="-mb-6">
                            <Heading title="Forums" description="Connect with our community and get support" />
                        </div>
                    </div>
                    {canStartNewTopic && (
                        <div className="flex w-full flex-col gap-2 sm:w-auto sm:shrink-0 sm:flex-row sm:items-center">
                            <Button onClick={() => setIsDialogOpen(true)}>
                                <Plus />
                                New Topic
                            </Button>
                        </div>
                    )}
                </div>

                {categories.length > 0 ? (
                    <div className="grid gap-6">
                        {categories.map((category) => (
                            <ForumCategoryCard key={category.id} category={category} />
                        ))}
                    </div>
                ) : (
                    <EmptyState icon={<MessageSquare />} title="No forums available" description="Check back later for community discussions." />
                )}

                <ForumSelectionDialog
                    categories={categories}
                    isOpen={isDialogOpen}
                    onClose={() => setIsDialogOpen(false)}
                    onSelect={(forum) => router.get(route('forums.topics.create', { forum: forum.slug }))}
                    title="Select a forum"
                    description="Choose which forum you'd like to create a new topic in."
                />
            </div>
        </AppLayout>
    );
}
