import ForumSelectionDialog from '@/components/forum-selection-dialog';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { pluralize } from '@/lib/utils';
import type { BreadcrumbItem, ForumCategory, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Circle, MessageSquare, Pin, Plus } from 'lucide-react';
import { useState } from 'react';
import usePermissions from '../../hooks/use-permissions';
import HeadingSmall from '@/components/heading-small';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Forums',
        href: '/forums',
    },
];

interface ForumsIndexProps {
    categories: ForumCategory[];
}

export default function ForumsIndex({ categories }: ForumsIndexProps) {
    const { can } = usePermissions();
    const { name: siteName } = usePage<SharedData>().props;
    const [isDialogOpen, setIsDialogOpen] = useState(false);

    const allForums = categories.flatMap(category => category.forums || []);

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
            hasPart: allForums.map((forum) => ({
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
            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <div className="flex items-start justify-between">
                    <Heading title="Forums" description="Connect with our community and get support" />
                    <div className="flex items-center gap-3">
                        {can('create_topics') && (
                            <Button onClick={() => setIsDialogOpen(true)}>
                                <Plus className="mr-2 h-4 w-4" />
                                New Topic
                            </Button>
                        )}
                    </div>
                </div>

                {can('view_any_forums') && categories.length > 0 && (
                    <div className="grid gap-4">
                        {categories.map((category) => (
                            <Card key={category.id} className="transition-shadow hover:shadow-sm py-0 overflow-hidden">
                                <CardContent className="p-0">
                                    <div className="flex flex-col md:flex-row">
                                        <div className="w-full md:w-64 bg-primary/5 px-6 py-4 md:border-r border-b md:border-b-0">
                                            <div className="space-y-3">
                                                <div>
                                                    <Heading title={category.name} description={category.description}/>
                                                    <div className="mt-2 text-sm text-muted-foreground">
                                                        {category.forums?.reduce((total, forum) => total + (forum.topics_count || 0), 0) || 0} {pluralize('post', category.forums?.reduce((total, forum) => total + (forum.topics_count || 0), 0) || 0)}
                                                    </div>
                                                </div>
                                                {category.forums && category.forums.length > 0 && (
                                                    <div className="flex flex-wrap gap-3 md:flex-col md:space-y-2 md:gap-0">
                                                        {category.forums.map((forum) => (
                                                            <div key={forum.id} className="flex items-center gap-2">
                                                                <div
                                                                    className="w-2 h-2 rounded-full"
                                                                    style={{ backgroundColor: forum.color }}
                                                                />
                                                                <Link
                                                                    href={route('forums.show', { forum: forum.slug })}
                                                                    className="text-sm hover:underline font-medium"
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
                                                {category.forums?.flatMap(forum =>
                                                    (forum.latest_topics || []).slice(0, 5).map((topic) => (
                                                        <div key={`${forum.id}-${topic.id}`} className="flex items-center gap-3 px-6 py-2 hover:bg-muted/30">
                                                            <div className="flex-shrink-0">
                                                                <div
                                                                    className="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium"
                                                                    style={{ backgroundColor: forum.color }}
                                                                >
                                                                    {topic.author?.name?.charAt(0).toUpperCase() || 'U'}
                                                                </div>
                                                            </div>
                                                            <div className="flex-1 min-w-0">
                                                                <div className="flex items-center gap-2">
                                                                    {!topic.is_read_by_user && (
                                                                        <Circle className="h-2 w-2 fill-primary text-primary" />
                                                                    )}
                                                                    {topic.is_pinned && <Pin className="h-3 w-3 text-primary" />}
                                                                    <Link
                                                                        href={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                                                                        className={`font-medium hover:underline truncate ${
                                                                            topic.is_read_by_user ? 'text-muted-foreground' : 'text-foreground'
                                                                        }`}
                                                                    >
                                                                        {topic.title}
                                                                    </Link>
                                                                </div>
                                                                <div className="flex items-center gap-3 mt-1 text-xs text-muted-foreground">
                                                                    <span>by {topic.author?.name}</span>
                                                                    <span>•</span>
                                                                    <span>{topic.last_reply_at ? formatDistanceToNow(new Date(topic.last_reply_at), { addSuffix: true }) : formatDistanceToNow(new Date(topic.created_at), { addSuffix: true })}</span>
                                                                </div>
                                                            </div>
                                                            <div className="flex-shrink-0 text-right">
                                                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                                    <div className="flex items-center gap-1">
                                                                        <MessageSquare className="h-3 w-3" />
                                                                        <span>{topic.posts_count}</span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ))
                                                )}
                                                {!category.forums?.some(forum => forum.latest_topics && forum.latest_topics.length > 0) && (
                                                    <div className="text-center py-8 text-muted-foreground">
                                                        <MessageSquare className="h-8 w-8 mx-auto mb-2" />
                                                        <p>No recent topics in this category</p>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {categories.length === 0 && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <MessageSquare className="mb-4 h-12 w-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No Forums Available</CardTitle>
                            <CardDescription>Check back later for community discussions.</CardDescription>
                        </CardContent>
                    </Card>
                )}

                <ForumSelectionDialog forums={allForums} isOpen={isDialogOpen} onClose={() => setIsDialogOpen(false)} />
            </div>
        </AppLayout>
    );
}
