import ForumSelectionDialog from '@/components/forum-selection-dialog';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useCookie } from '@/hooks/use-cookie';
import AppLayout from '@/layouts/app-layout';
import { pluralize } from '@/lib/utils';
import type { BreadcrumbItem, Forum, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Circle, Eye, Grid3X3, List, MessageSquare, Pin, Plus, Users } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Forums',
        href: '/forums',
    },
];

interface ForumsIndexProps {
    forums: Forum[];
}

export default function ForumsIndex({ forums }: ForumsIndexProps) {
    const { name: siteName } = usePage<SharedData>().props;
    const [viewMode, setViewMode] = useCookie<'list' | 'grid'>('forum_view', 'list');
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: `${siteName} Forums`,
        description: 'Connect with our community and get support',
        url: window.location.href,
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        mainEntity: {
            '@type': 'CollectionPage',
            name: 'Community Forums',
            description: 'Browse our community forums and discussions',
            hasPart: forums.map((forum) => ({
                '@type': 'WebPage',
                name: forum.name,
                description: forum.description,
                url: `/forums/${forum.slug}`,
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
            })),
        },
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
            <div className="flex h-full flex-1 flex-col overflow-x-auto rounded-xl p-4">
                <div className="flex items-start justify-between">
                    <Heading title="Forums" description="Connect with our community and get support" />

                    <div className="flex items-center gap-3">
                        <div className="hidden items-center gap-1 rounded-lg border p-1 md:flex">
                            <Button
                                variant={viewMode === 'list' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setViewMode('list')}
                                className="h-8 px-3"
                            >
                                <List className="h-4 w-4" />
                            </Button>
                            <Button
                                variant={viewMode === 'grid' ? 'default' : 'ghost'}
                                size="sm"
                                onClick={() => setViewMode('grid')}
                                className="h-8 px-3"
                            >
                                <Grid3X3 className="h-4 w-4" />
                            </Button>
                        </div>
                        <Button onClick={() => setIsDialogOpen(true)}>
                            <Plus className="mr-2 h-4 w-4" />
                            New Topic
                        </Button>
                    </div>
                </div>

                <div className={viewMode === 'grid' ? 'grid gap-6 md:grid-cols-2 lg:grid-cols-3' : 'grid gap-8'}>
                    {forums.map((forum) => (
                        <Card key={forum.id} className="transition-shadow hover:shadow-md">
                            <CardHeader>
                                <div className="flex items-start gap-4">
                                    <div
                                        className="flex h-12 w-12 items-center justify-center rounded-lg text-white"
                                        style={{ backgroundColor: forum.color }}
                                    >
                                        <MessageSquare className="h-6 w-6" />
                                    </div>
                                    <div className="flex-1">
                                        <div className="flex items-center gap-2">
                                            <CardTitle>
                                                <Link href={route('forums.show', { forum: forum.slug })} className="hover:underline">
                                                    {forum.name}
                                                </Link>
                                            </CardTitle>
                                        </div>
                                        {forum.description && <CardDescription className="mt-1">{forum.description}</CardDescription>}
                                        <div className="mt-3 flex items-center gap-4 text-sm text-muted-foreground">
                                            <div className="flex items-center gap-1">
                                                <MessageSquare className="h-4 w-4" />
                                                <span>{(forum.topics_count || 0) + ' ' + pluralize('topic', forum.topics_count || 0)}</span>
                                            </div>
                                            <div className="flex items-center gap-1">
                                                <Users className="h-4 w-4" />
                                                <span>{(forum.posts_count || 0) + ' ' + pluralize('post', forum.posts_count || 0)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardHeader>

                            {forum.latest_topics && forum.latest_topics.length > 0 && (
                                <CardContent className="pt-0">
                                    <div className="border-t pt-4">
                                        <div className="mb-3 text-sm font-medium">Recent Topics</div>
                                        <div className="space-y-3">
                                            {forum.latest_topics.slice(0, 3).map((topic) => (
                                                <div key={topic.id} className="flex items-center gap-3">
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex items-center gap-2">
                                                            {!topic.is_read_by_user && <Circle className="h-2 w-2 fill-blue-500 text-blue-500" />}
                                                            {topic.is_hot && <span className="text-sm">🔥</span>}
                                                            {topic.is_pinned && <Pin className="h-3 w-3 text-blue-500" />}
                                                            <Link
                                                                href={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                                                                className={`truncate text-sm hover:underline ${
                                                                    topic.is_read_by_user ? 'font-normal text-muted-foreground' : 'font-medium'
                                                                }`}
                                                            >
                                                                {topic.title}
                                                            </Link>
                                                        </div>
                                                        <div className="mt-1 flex items-center gap-3 text-xs text-muted-foreground">
                                                            <span>by {topic.author?.name}</span>
                                                            <div className="flex items-center gap-1">
                                                                <Eye className="h-3 w-3" />
                                                                <span>{topic.views_count}</span>
                                                            </div>
                                                            <div className="flex items-center gap-1">
                                                                <MessageSquare className="h-3 w-3" />
                                                                <span>{topic.posts_count}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    {topic.last_reply_at && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {formatDistanceToNow(new Date(topic.last_reply_at), { addSuffix: true })}
                                                        </div>
                                                    )}
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </CardContent>
                            )}
                        </Card>
                    ))}
                </div>

                {forums.length === 0 && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <MessageSquare className="mb-4 h-12 w-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No Forums Available</CardTitle>
                            <CardDescription>Check back later for community discussions.</CardDescription>
                        </CardContent>
                    </Card>
                )}

                <ForumSelectionDialog forums={forums} isOpen={isDialogOpen} onClose={() => setIsDialogOpen(false)} />
            </div>
        </AppLayout>
    );
}
