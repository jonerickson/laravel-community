import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum, ForumCategory, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { LibraryBig, MessageSquare, Users } from 'lucide-react';
import { route } from 'ziggy-js';
import usePermissions from '../../../hooks/use-permissions';

interface CategoryShowProps {
    category: ForumCategory;
    forums: Forum[];
}

export default function ForumCategoryShow({ category, forums }: CategoryShowProps) {
    const { can } = usePermissions();
    const { name: siteName } = usePage<SharedData>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Forums',
            href: route('forums.index'),
        },
        {
            title: category.name,
            href: route('forums.categories.show', { category: category.slug }),
        },
    ];

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: category.name,
        description: category.description || `Forums in ${category.name} category`,
        url: window.location.href,
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        breadcrumb: {
            '@type': 'BreadcrumbList',
            numberOfItems: breadcrumbs.length,
            itemListElement: breadcrumbs.map((breadcrumb, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                name: breadcrumb.title,
                item: breadcrumb.href,
            })),
        },
        mainEntity: {
            '@type': 'CollectionPage',
            name: category.name,
            description: category.description || `Forums in ${category.name} category`,
            hasPart: forums.map((forum) => ({
                '@type': 'CollectionPage',
                name: forum.name,
                description: forum.description,
                url: route('forums.categories.show', { category: category.slug }),
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
            <Head title={`Forums - ${category.name}`}>
                <meta name="description" content={category.description || `Forums in ${category.name} category`} />
                <meta property="og:title" content={`Forums - ${category.name} - ${siteName}`} />
                <meta property="og:description" content={category.description || `Forums in ${category.name} category`} />
                <meta property="og:type" content="website" />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex items-start gap-4">
                    <div
                        className="flex size-12 flex-shrink-0 items-center justify-center rounded-lg text-white"
                        style={{ backgroundColor: category.color }}
                    >
                        <MessageSquare className="h-6 w-6" />
                    </div>
                    <div className="-mb-6">
                        <Heading title={category.name} description={category.description || ''} />
                    </div>
                </div>

                {can('view_any_forums') && forums.length > 0 ? (
                    <div className="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[60%]"></TableHead>
                                    <TableHead className="w-[10%] text-center">Topics</TableHead>
                                    <TableHead className="w-[10%] text-center">Posts</TableHead>
                                    <TableHead className="w-[20%] text-right">Latest Activity</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {forums.map((forum) => (
                                    <TableRow key={forum.id} className="hover:bg-muted/50">
                                        <TableCell className="p-4">
                                            <div className="flex items-start gap-3">
                                                <div
                                                    className="flex h-10 w-10 items-center justify-center rounded-lg text-white"
                                                    style={{ backgroundColor: forum.color }}
                                                >
                                                    <MessageSquare className="size-5" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <div className="mb-1">
                                                        <Link
                                                            href={route('forums.show', { forum: forum.slug })}
                                                            className="font-medium hover:underline"
                                                        >
                                                            {forum.name}
                                                        </Link>
                                                    </div>
                                                    {forum.description && <p className="text-sm text-muted-foreground">{forum.description}</p>}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="p-4 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <MessageSquare className="size-4" />
                                                <span>{forum.topics_count || 0}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="p-4 text-center">
                                            <div className="flex items-center justify-center gap-1">
                                                <Users className="size-4" />
                                                <span>{forum.posts_count || 0}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="p-4 text-right">
                                            {forum.latest_topics && forum.latest_topics.length > 0 ? (
                                                <div className="text-sm">
                                                    <div className="mb-1">
                                                        <Link
                                                            href={route('forums.topics.show', {
                                                                forum: forum.slug,
                                                                topic: forum.latest_topics[0].slug,
                                                            })}
                                                            className="font-medium hover:underline"
                                                        >
                                                            {forum.latest_topics[0].title}
                                                        </Link>
                                                    </div>
                                                    <div className="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                                                        <Avatar className="size-4">
                                                            <AvatarFallback className="text-xs">
                                                                {forum.latest_topics[0].author?.name?.charAt(0).toUpperCase() || 'U'}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <span>by {forum.latest_topics[0].author?.name}</span>
                                                        <span>•</span>
                                                        <span>
                                                            {forum.latest_topics[0].last_reply_at
                                                                ? formatDistanceToNow(new Date(forum.latest_topics[0].last_reply_at), {
                                                                      addSuffix: true,
                                                                  })
                                                                : formatDistanceToNow(new Date(forum.latest_topics[0].created_at), {
                                                                      addSuffix: true,
                                                                  })}
                                                        </span>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="text-sm text-muted-foreground">No topics yet</div>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                ) : (
                    <EmptyState icon={<LibraryBig />} title="No forums available" description="There are no forums in this category yet." />
                )}
            </div>
        </AppLayout>
    );
}
