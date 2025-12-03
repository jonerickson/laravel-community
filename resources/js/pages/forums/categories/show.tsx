import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import Loading from '@/components/loading';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { abbreviateNumber } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { truncate } from '@/utils/truncate';
import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { LibraryBig, MessageSquare, Users } from 'lucide-react';
import { route } from 'ziggy-js';

interface CategoryShowProps {
    category: App.Data.ForumCategoryData;
    forums: App.Data.ForumData[];
}

export default function ForumCategoryShow({ category, forums }: CategoryShowProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
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
        '@type': 'CollectionPage',
        name: category.name,
        description: category.description || `Forums in ${category.name} category`,
        url: route('forums.categories.show', { category: category.slug }),
        image: category.featuredImageUrl || logoUrl,
        inLanguage: 'en',
        isPartOf: {
            '@type': 'WebSite',
            name: siteName,
        },
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
        mainEntity: forums?.map((forum) => ({
            '@type': 'CollectionPage',
            name: forum.name,
            description: forum.description,
            url: route('forums.categories.show', { category: category.slug }),
            interactionStatistic: [
                {
                    '@type': 'InteractionCounter',
                    interactionType: 'https://schema.org/CreateAction',
                    userInteractionCount: forum.topicsCount || 0,
                },
                {
                    '@type': 'InteractionCounter',
                    interactionType: 'https://schema.org/CommentAction',
                    userInteractionCount: forum.postsCount || 0,
                },
            ],
        })),
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${category.name} - Forums`}>
                <meta name="description" content={category.description || `Forums in ${category.name} category`} />
                <meta property="og:title" content={`${category.name} - Forums - ${siteName}`} />
                <meta property="og:description" content={category.description || `Forums in ${category.name} category`} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={category.featuredImageUrl || logoUrl} />
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
                        <Heading title={category.name} description={category.description || undefined} />
                    </div>
                </div>

                <Deferred fallback={<Loading variant="table" />} data="forums">
                    {forums && forums.length > 0 ? (
                        <div className="relative rounded-md border bg-background">
                            <Table className="table table-fixed">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[60%] pl-4">Forums</TableHead>
                                        <TableHead className="hidden w-[10%] text-center md:table-cell">Topics</TableHead>
                                        <TableHead className="hidden w-[10%] text-center md:table-cell">Posts</TableHead>
                                        <TableHead className="hidden w-[20%] pr-4 text-right md:table-cell">Latest Activity</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {forums &&
                                        forums.map((forum) => (
                                            <TableRow key={forum.id} className="hover:bg-accent/20">
                                                <TableCell className="p-4">
                                                    <div className="flex items-start gap-3">
                                                        <div
                                                            className="flex h-10 w-10 items-center justify-center rounded-lg text-white"
                                                            style={{ backgroundColor: forum.color }}
                                                        >
                                                            <MessageSquare className="size-5" />
                                                        </div>
                                                        <div className="min-w-0 flex-1">
                                                            <Link
                                                                href={route('forums.show', { forum: forum.slug })}
                                                                className="font-medium hover:underline"
                                                            >
                                                                {forum.name}
                                                            </Link>
                                                            {forum.description && (
                                                                <p className="mt-1 text-sm text-wrap break-words text-muted-foreground">
                                                                    {forum.description}
                                                                </p>
                                                            )}
                                                        </div>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="hidden p-4 text-center md:table-cell">
                                                    <div className="flex items-center justify-center gap-1">
                                                        <MessageSquare className="size-4" />
                                                        <span>{abbreviateNumber(forum.topicsCount || 0)}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="hidden p-4 text-center md:table-cell">
                                                    <div className="flex items-center justify-center gap-1">
                                                        <Users className="size-4" />
                                                        <span>{abbreviateNumber(forum.postsCount || 0)}</span>
                                                    </div>
                                                </TableCell>
                                                <TableCell className="hidden p-4 text-right md:table-cell">
                                                    {forum.latestTopic ? (
                                                        <div className="text-sm">
                                                            <div className="mb-1">
                                                                <Link
                                                                    href={route('forums.topics.show', {
                                                                        forum: forum.slug,
                                                                        topic: forum.latestTopic.slug,
                                                                    })}
                                                                    className="font-medium text-wrap break-words hover:underline"
                                                                >
                                                                    {truncate(forum.latestTopic.title, 25)}
                                                                </Link>
                                                            </div>
                                                            <div className="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                                                                <Avatar className="size-4">
                                                                    <AvatarFallback className="text-xs">
                                                                        {forum.latestTopic.author?.name?.charAt(0).toUpperCase() || 'U'}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                                <span>by {forum.latestTopic.author?.name}</span>
                                                                <span>•</span>
                                                                <span>
                                                                    {forum.latestTopic.lastPost?.createdAt
                                                                        ? formatDistanceToNow(new Date(forum.latestTopic.lastPost.createdAt), {
                                                                              addSuffix: true,
                                                                          })
                                                                        : 'N/A'}
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
                </Deferred>
            </div>
        </AppLayout>
    );
}
