import { EmptyState } from '@/components/empty-state';
import { FollowButton } from '@/components/follow-button';
import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useApiRequest } from '@/hooks/use-api-request';
import usePermissions from '@/hooks/use-permissions';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { stripCharacters } from '@/utils/truncate';
import { Head, InfiniteScroll, Link, router, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { AlertTriangle, Circle, Eye, EyeOff, LibraryBig, Lock, MessageSquare, Pin, Plus, ThumbsDown, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface ForumShowProps {
    forum: App.Data.ForumData;
    topics: App.Data.PaginatedData<App.Data.TopicData>;
}

export default function ForumShow({ forum, topics: initialTopics }: ForumShowProps) {
    const { can } = usePermissions();
    const { name: siteName, auth } = usePage<App.Data.SharedData>().props;
    const [topics, setTopics] = useState<App.Data.TopicData[]>(initialTopics.data);
    const [selectedTopics, setSelectedTopics] = useState<number[]>([]);
    const { loading: isDeleting, execute: executeBulkDelete } = useApiRequest();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Forums',
            href: route('forums.index'),
        },
    ];

    if (forum.category) {
        breadcrumbs.push({
            title: forum.category.name,
            href: route('forums.categories.show', { category: forum.category.slug }),
        });
    }

    if (forum.parent) {
        breadcrumbs.push({
            title: forum.parent.name,
            href: route('forums.show', { forum: forum.parent.slug }),
        });
    }

    breadcrumbs.push({
        title: forum.name,
        href: route('forums.show', { forum: forum.slug }),
    });

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: forum.name,
        description: forum.description || `Discussions and topics in ${forum.name}`,
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
        hasPart: topics.map((topic) => ({
            '@type': 'DiscussionForumPosting',
            headline: topic.title,
            description: topic.description || `Discussion topic: ${topic.title}`,
            text: topic.lastPost?.content,
            dateCreated: topic.createdAt,
            dateModified: topic.updatedAt,
            author: {
                '@type': 'Person',
                name: topic.author.name,
            },
            url: route('forums.topics.show', { forum: forum.slug, topic: topic.slug }),
            interactionStatistic: [
                {
                    '@type': 'InteractionCounter',
                    interactionType: 'https://schema.org/ViewAction',
                    userInteractionCount: topic.viewsCount,
                },
                {
                    '@type': 'InteractionCounter',
                    interactionType: 'https://schema.org/ReplyAction',
                    userInteractionCount: topic.postsCount,
                },
            ],
        })),
        numberOfItems: topics.length,
    };

    const handleSelectTopic = (topicId: number, checked: boolean) => {
        if (checked) {
            setSelectedTopics((prev) => [...prev, topicId]);
        } else {
            setSelectedTopics((prev) => prev.filter((id) => id !== topicId));
        }
    };

    const handleSelectAll = (checked: boolean) => {
        if (checked) {
            setSelectedTopics(topics.map((topic) => topic.id));
        } else {
            setSelectedTopics([]);
        }
    };

    const handleBulkDelete = async () => {
        if (!selectedTopics.length || !confirm(`Are you sure you want to delete ${selectedTopics.length} topic(s)? This action cannot be undone.`)) {
            return;
        }

        await executeBulkDelete(
            {
                url: route('api.forums.topics.destroy'),
                method: 'DELETE',
                data: {
                    topic_ids: selectedTopics,
                    forum_id: forum.id,
                },
            },
            {
                onSuccess: () => {
                    setTopics((prevTopics) => prevTopics.filter((topic) => !selectedTopics.includes(topic.id)));
                    setSelectedTopics([]);
                    router.reload();
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Forums - ${forum.name}`}>
                <meta name="description" content={forum.description || `Discussions and topics in ${forum.name}`} />
                <meta property="og:title" content={`Forums - ${forum.name} - ${siteName}`} />
                <meta property="og:description" content={forum.description || `Discussions and topics in ${forum.name}`} />
                <meta property="og:type" content="website" />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start sm:gap-0">
                    <div className="flex items-start gap-4">
                        <div
                            className="flex size-12 flex-shrink-0 items-center justify-center rounded-lg text-white"
                            style={{ backgroundColor: forum.color }}
                        >
                            <MessageSquare className="h-6 w-6" />
                        </div>
                        <div className="-mb-6">
                            <Heading title={forum.name} description={forum.description ?? ''} />
                        </div>
                    </div>
                    {auth && auth.user && (
                        <div className="flex w-full flex-col gap-2 sm:w-auto sm:shrink-0 sm:flex-row sm:items-center">
                            <FollowButton
                                type="forum"
                                id={forum.id}
                                isFollowing={forum.isFollowedByUser ?? false}
                                followersCount={forum.followersCount ?? 0}
                            />
                            {can('delete_topics') && (
                                <>
                                    {selectedTopics.length > 0 && (
                                        <>
                                            <Button variant="destructive" onClick={handleBulkDelete} disabled={isDeleting}>
                                                <Trash2 className="mr-2 size-4" />
                                                Delete {selectedTopics.length} Topic{selectedTopics.length > 1 ? 's' : ''}
                                            </Button>
                                            <Button variant="outline" onClick={() => setSelectedTopics([])}>
                                                Clear Selection
                                            </Button>
                                        </>
                                    )}
                                    {selectedTopics.length === 0 && topics.length > 0 && (
                                        <Button variant="outline" onClick={() => handleSelectAll(true)}>
                                            Select All
                                        </Button>
                                    )}
                                </>
                            )}
                            {can('create_topics') && (
                                <Button asChild>
                                    <Link href={route('forums.topics.create', { forum: forum.slug })}>
                                        <Plus className="mr-2 size-4" />
                                        New Topic
                                    </Link>
                                </Button>
                            )}
                        </div>
                    )}
                </div>

                {forum.rules && stripCharacters(forum.rules).length > 0 && (
                    <Alert>
                        <AlertTitle>Forum Rules</AlertTitle>
                        <AlertDescription>
                            <RichEditorContent content={forum.rules} />
                        </AlertDescription>
                    </Alert>
                )}

                {forum.children && forum.children.length > 0 && (
                    <div className="rounded-md border">
                        <Table className="table table-fixed">
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[60%]">Subforums</TableHead>
                                    <TableHead className="hidden w-[10%] text-center md:table-cell">Topics</TableHead>
                                    <TableHead className="hidden w-[10%] text-center md:table-cell">Posts</TableHead>
                                    <TableHead className="hidden w-[20%] text-right md:table-cell">Latest Activity</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {forum.children.map((subforum) => (
                                    <TableRow key={subforum.id} className="hover:bg-muted/50">
                                        <TableCell className="p-4">
                                            <div className="flex items-start gap-3">
                                                <div
                                                    className="flex h-10 w-10 items-center justify-center rounded-lg text-white"
                                                    style={{ backgroundColor: subforum.color }}
                                                >
                                                    <MessageSquare className="size-5" />
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <Link
                                                        href={route('forums.show', { forum: subforum.slug })}
                                                        className="font-medium hover:underline"
                                                    >
                                                        {subforum.name}
                                                    </Link>
                                                    {subforum.description && (
                                                        <p className="mt-1 text-sm text-wrap break-words text-muted-foreground">
                                                            {subforum.description}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden p-4 text-center md:table-cell">
                                            <div className="flex items-center justify-center gap-1">
                                                <MessageSquare className="size-4" />
                                                <span>{subforum.topicsCount || 0}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden p-4 text-center md:table-cell">
                                            <div className="flex items-center justify-center gap-1">
                                                <MessageSquare className="size-4" />
                                                <span>{subforum.postsCount || 0}</span>
                                            </div>
                                        </TableCell>
                                        <TableCell className="hidden p-4 text-right md:table-cell">
                                            {subforum.latestTopics && subforum.latestTopics.length > 0 ? (
                                                <div className="text-sm">
                                                    <div className="mb-1">
                                                        <Link
                                                            href={route('forums.topics.show', {
                                                                forum: subforum.slug,
                                                                topic: subforum.latestTopics[0].slug,
                                                            })}
                                                            className="font-medium text-wrap break-words hover:underline"
                                                        >
                                                            {subforum.latestTopics[0].title}
                                                        </Link>
                                                    </div>
                                                    <div className="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                                                        <Avatar className="size-4">
                                                            <AvatarFallback className="text-xs">
                                                                {subforum.latestTopics[0].author?.name?.charAt(0).toUpperCase() || 'U'}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <span>by {subforum.latestTopics[0].author?.name}</span>
                                                        <span>•</span>
                                                        <span>
                                                            {subforum.latestTopics[0].lastReplyAt
                                                                ? formatDistanceToNow(new Date(subforum.latestTopics[0].lastReplyAt), {
                                                                      addSuffix: true,
                                                                  })
                                                                : subforum.latestTopics[0].createdAt
                                                                  ? formatDistanceToNow(new Date(subforum.latestTopics[0].createdAt), {
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
                )}

                {topics.length > 0 ? (
                    <div className="rounded-md border">
                        <InfiniteScroll data="topics">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[80%]">Forums</TableHead>
                                        <TableHead className="w-[5%] text-center">Replies</TableHead>
                                        <TableHead className="w-[5%] text-center">Views</TableHead>
                                        <TableHead className="w-[10%] text-right">Last Activity</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {topics.map((topic) => (
                                        <TableRow
                                            key={topic.id}
                                            className={`hover:bg-muted/50 ${selectedTopics.includes(topic.id) ? 'bg-info-foreground' : ''}`}
                                        >
                                            <TableCell className="p-4">
                                                <div className="flex items-start gap-3">
                                                    {can('delete_topics') ? (
                                                        <button
                                                            onClick={(e) => {
                                                                e.preventDefault();
                                                                handleSelectTopic(topic.id, !selectedTopics.includes(topic.id));
                                                            }}
                                                            className="relative"
                                                        >
                                                            {selectedTopics.includes(topic.id) ? (
                                                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-600">
                                                                    <Checkbox
                                                                        checked={true}
                                                                        className="pointer-events-none border-white text-white"
                                                                    />
                                                                </div>
                                                            ) : (
                                                                <Avatar className="h-8 w-8 transition-opacity hover:opacity-75">
                                                                    <AvatarFallback className="text-xs">
                                                                        {topic.author.name.charAt(0).toUpperCase()}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                            )}
                                                        </button>
                                                    ) : (
                                                        <Avatar className="h-8 w-8">
                                                            <AvatarFallback className="text-xs">
                                                                {topic.author.name.charAt(0).toUpperCase()}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                    )}
                                                    <div className="min-w-0 flex-1">
                                                        <div className="mb-1 flex items-center gap-2">
                                                            {auth && auth.user && !topic.isReadByUser && (
                                                                <Circle className="size-3 fill-info text-info" />
                                                            )}
                                                            {topic.isHot && <span className="text-sm">🔥</span>}
                                                            {topic.isPinned && <Pin className="size-4 text-info" />}
                                                            {topic.isLocked && <Lock className="size-4 text-muted-foreground" />}
                                                            {can('report_posts') && topic.hasReportedContent && (
                                                                <AlertTriangle className="size-4 text-destructive" />
                                                            )}
                                                            {can('publish_posts') && topic.hasUnpublishedContent && (
                                                                <EyeOff className="size-4 text-warning" />
                                                            )}
                                                            {can('approve_posts') && topic.hasUnapprovedContent && (
                                                                <ThumbsDown className="size-4 text-warning" />
                                                            )}
                                                            <Link
                                                                href={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                                                                className={`hover:underline ${
                                                                    auth && auth.user && topic.isReadByUser
                                                                        ? 'font-normal text-muted-foreground'
                                                                        : 'font-medium'
                                                                }`}
                                                            >
                                                                {topic.title}
                                                            </Link>
                                                        </div>
                                                        <div className="text-xs text-muted-foreground">
                                                            Started by {topic.author.name} •{' '}
                                                            {topic.createdAt
                                                                ? formatDistanceToNow(new Date(topic.createdAt), { addSuffix: true })
                                                                : 'Unknown time'}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="p-4 text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <MessageSquare className="size-4" />
                                                    <span>{topic.postsCount}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="p-4 text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <Eye className="size-4" />
                                                    <span>{topic.viewsCount}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="p-4 text-right">
                                                {topic.lastPost ? (
                                                    <div className="text-sm">
                                                        <div className="font-medium">{topic.lastPost.author?.name}</div>
                                                        {topic.lastReplyAt && (
                                                            <div className="text-xs text-muted-foreground">
                                                                {formatDistanceToNow(new Date(topic.lastReplyAt), { addSuffix: true })}
                                                            </div>
                                                        )}
                                                    </div>
                                                ) : (
                                                    <div className="text-sm text-muted-foreground">No replies</div>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </InfiniteScroll>
                    </div>
                ) : (
                    <EmptyState icon={<LibraryBig />} title="No topics yet" description="Be the first to start a discussion in this forum." />
                )}
            </div>
        </AppLayout>
    );
}
