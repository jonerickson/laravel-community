import { EmptyState } from '@/components/empty-state';
import ForumSelectionDialog from '@/components/forum-selection-dialog';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { pluralize } from '@/lib/utils';
import type { BreadcrumbItem, ForumCategory, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Circle, Lock, MessageSquare, Pin, Plus } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';
import usePermissions from '../../../hooks/use-permissions';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Forums',
        href: route('forums.index'),
    },
];

interface ForumsIndexProps {
    categories: ForumCategory[];
}

export default function ForumCategoryIndex({ categories }: ForumsIndexProps) {
    const { can } = usePermissions();
    const { name: siteName } = usePage<SharedData>().props;
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const allForums = categories.flatMap((category) => category.forums || []);

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: `${siteName} Forums`,
        description: 'Connect with our community and get support',
        url: window.location.href,
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        breadcrumb: {
            '@type': 'BreadcrumbList',
            itemListElement: breadcrumbs.map((breadcrumb, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                name: breadcrumb.title,
                item: breadcrumb.href,
            })),
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
                            userInteractionCount: forum.topics_count || 0,
                        },
                        {
                            '@type': 'InteractionCounter',
                            interactionType: 'https://schema.org/ReplyAction',
                            userInteractionCount: forum.posts_count || 0,
                        },
                    ],
                })) || [],
        })),
        numberOfItems: categories.length,
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Forums">
                <meta name="description" content="Connect with our community and get support through our forums" />
                <meta property="og:title" content={`Forums - ${siteName}`} />
                <meta property="og:description" content="Connect with our community and get support through our forums" />
                <meta property="og:type" content="website" />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start sm:gap-0">
                    <div className="flex items-start gap-4">
                        <div className="-mb-6">
                            <Heading title="Forums" description="Connect with our community and get support" />
                        </div>
                    </div>
                    <div className="flex w-full flex-col gap-2 sm:w-auto sm:shrink-0 sm:flex-row sm:items-center">
                        {can('create_topics') && (
                            <Button onClick={() => setIsDialogOpen(true)}>
                                <Plus className="mr-2 size-4" />
                                New Topic
                            </Button>
                        )}
                    </div>
                </div>

                {can('view_any_forums_categories') && categories.length > 0 ? (
                    <div className="grid gap-4">
                        {categories.map((category) => (
                            <Card key={category.id} className="overflow-hidden py-0 transition-shadow hover:shadow-sm">
                                <CardContent className="p-0">
                                    <div className="flex flex-col md:flex-row">
                                        <div className="w-full border-b bg-primary/5 px-6 py-4 md:w-64 md:border-r md:border-b-0">
                                            <div className="space-y-3">
                                                <Link href={route('forums.categories.show', { category: category.slug })}>
                                                    <Heading title={category.name} description={category.description || undefined} />
                                                    <div className="-mt-4">
                                                        {category.image && (
                                                            <div className="pb-4">
                                                                <img
                                                                    src={category.image.url}
                                                                    alt={`${category.name} category image`}
                                                                    className="h-48 w-full rounded-lg object-cover"
                                                                />
                                                            </div>
                                                        )}
                                                        <div className="text-sm text-muted-foreground">
                                                            {category.forums?.reduce((total, forum) => total + (forum.topics_count || 0), 0) || 0}{' '}
                                                            {pluralize(
                                                                'post',
                                                                category.forums?.reduce((total, forum) => total + (forum.topics_count || 0), 0) || 0,
                                                            )}
                                                        </div>
                                                    </div>
                                                </Link>
                                                {category.forums && category.forums.length > 0 && (
                                                    <div className="mt-2 flex flex-wrap gap-3 md:flex-col md:gap-0 md:space-y-2">
                                                        {category.forums.map((forum) => (
                                                            <div key={forum.id} className="flex items-center gap-2">
                                                                <div className="h-2 w-2 rounded-full" style={{ backgroundColor: forum.color }} />
                                                                <Link
                                                                    href={route('forums.show', { forum: forum.slug })}
                                                                    className="text-sm font-medium hover:underline"
                                                                >
                                                                    {forum.name}
                                                                </Link>
                                                            </div>
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex-1 py-3">
                                            <div className="space-y-1">
                                                {category.forums?.flatMap((forum) =>
                                                    (forum.latest_topics || []).slice(0, 5).map((topic) => (
                                                        <Link
                                                            key={`${forum.id}-${topic.id}`}
                                                            href={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                                                            className="flex items-center gap-3 px-6 py-2 hover:bg-muted/30"
                                                        >
                                                            <div className="flex-shrink-0">
                                                                <div
                                                                    className="flex h-8 w-8 items-center justify-center rounded-full text-sm font-medium text-white"
                                                                    style={{ backgroundColor: forum.color }}
                                                                >
                                                                    {topic.author.name?.charAt(0).toUpperCase() || 'U'}
                                                                </div>
                                                            </div>
                                                            <div className="min-w-0 flex-1">
                                                                <div className="flex items-center gap-2">
                                                                    {!topic.is_read_by_user && <Circle className="size-3 fill-info text-info" />}
                                                                    {topic.is_hot && <span className="text-sm">🔥</span>}
                                                                    {topic.is_pinned && <Pin className="size-4 text-info" />}
                                                                    {topic.is_locked && <Lock className="size-4 text-muted-foreground" />}
                                                                    <span
                                                                        className={`truncate font-medium ${
                                                                            topic.is_read_by_user ? 'text-muted-foreground' : 'text-foreground'
                                                                        }`}
                                                                    >
                                                                        {topic.title}
                                                                    </span>
                                                                </div>
                                                                <div className="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                                                                    <span>by {topic.author.name}</span>
                                                                    <span>•</span>
                                                                    <span>
                                                                        {topic.last_reply_at
                                                                            ? formatDistanceToNow(new Date(topic.last_reply_at), { addSuffix: true })
                                                                            : formatDistanceToNow(new Date(topic.created_at), { addSuffix: true })}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div className="flex-shrink-0 text-right">
                                                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                                    <div className="flex items-center gap-1">
                                                                        <MessageSquare className="size-3" />
                                                                        <span>{topic.posts_count}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </Link>
                                                    )),
                                                )}
                                                {!category.forums?.some((forum) => forum.latest_topics && forum.latest_topics.length > 0) && (
                                                    <div className="flex flex-col items-center justify-center py-14 text-center">
                                                        <MessageSquare className="mb-2 size-8 text-muted-foreground/50" />
                                                        <HeadingSmall title={'No topics'} description={'There are no recent topics to show.'} />
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <EmptyState icon={<MessageSquare />} title="No forums available" description="Check back later for community discussions." />
                )}

                <ForumSelectionDialog forums={allForums} isOpen={isDialogOpen} onClose={() => setIsDialogOpen(false)} />
            </div>
        </AppLayout>
    );
}
