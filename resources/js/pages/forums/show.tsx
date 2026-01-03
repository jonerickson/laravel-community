import { EmptyState } from '@/components/empty-state';
import { FollowButton } from '@/components/follow-button';
import Heading from '@/components/heading';
import Loading from '@/components/loading';
import RichEditorContent from '@/components/rich-editor-content';
import { StyledUserName } from '@/components/styled-user-name';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useApiRequest } from '@/hooks/use-api-request';
import AppLayout from '@/layouts/app-layout';
import { abbreviateNumber, cn } from '@/lib/utils';
import { buildForumBreadcrumbs } from '@/utils/breadcrumbs';
import { stripCharacters, truncate } from '@/utils/truncate';
import { Deferred, Head, Link, router, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { AlertTriangle, Circle, Eye, EyeOff, LibraryBig, Lock, MessageSquare, Pin, Plus, ThumbsDown, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface ForumShowProps {
    forum: App.Data.ForumData;
    children: App.Data.ForumData[];
    topics: App.Data.PaginatedData<App.Data.TopicData>;
}

export default function ForumShow({ forum, children, topics }: ForumShowProps) {
    const { name: siteName, auth, logoUrl } = usePage<App.Data.SharedData>().props;
    const [selectedTopics, setSelectedTopics] = useState<number[]>([]);
    const { loading: isDeleting, execute: executeBulkDelete } = useApiRequest();

    const breadcrumbs = buildForumBreadcrumbs(forum);

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: forum.name,
        description: forum.description || `Discussions and topics in ${forum.name}`,
        url: route('forums.show', { forum: forum.slug }),
        inLanguage: 'en',
        image: forum.category?.featuredImageUrl || logoUrl,
        isPartOf: {
            '@type': 'WebSite',
            name: siteName,
        },
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        about: {
            '@type': 'Thing',
            name: forum.category?.name,
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
        mainEntity: topics?.data.map((topic) => ({
            '@type': 'DiscussionForumPosting',
            headline: topic.title,
            text: stripCharacters(topic.lastPost?.content || ''),
            url: route('forums.topics.show', { forum: forum.slug, topic: topic.slug }),
            datePublished: topic.createdAt,
            dateCreated: topic.createdAt,
            dateModified: topic.updatedAt,
            author: {
                '@type': 'Person',
                name: topic.author.name,
                url: topic.author.referenceId ? route('users.show', { user: topic.author.referenceId }) : null,
            },
            interactionStatistic: [
                {
                    '@type': 'InteractionCounter',
                    interactionType: 'https://schema.org/ViewAction',
                    userInteractionCount: topic.viewsCount,
                },
                {
                    '@type': 'InteractionCounter',
                    interactionType: 'https://schema.org/CommentAction',
                    userInteractionCount: topic.postsCount,
                },
            ],
            comment: {
                '@type': 'Comment',
                '@id': route('forums.topics.show', { forum: forum.slug, topic: topic.slug }) + '#' + topic.lastPost?.id,
                url: route('forums.topics.show', { forum: forum.slug, topic: topic.slug }) + '#' + topic.lastPost?.id,
                text: stripCharacters(topic.lastPost?.content || ''),
                datePublished: topic.lastPost?.createdAt,
                dateCreated: topic.lastPost?.createdAt,
                dateModified: topic.lastPost?.updatedAt,
                author: {
                    '@type': 'Person',
                    name: topic.lastPost?.author.name,
                    url: topic.lastPost?.author.referenceId ? route('users.show', { user: topic.lastPost?.author.referenceId }) : null,
                },
                interactionStatistic: [
                    {
                        '@type': 'InteractionCounter',
                        interactionType: 'https://schema.org/LikeAction',
                        userInteractionCount: topic.lastPost?.likesCount || 0,
                    },
                ],
            },
        })),
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
            setSelectedTopics(topics.data.map((topic) => topic.id));
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
                    setSelectedTopics([]);
                    router.reload({ only: ['topics'] });
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${forum.name} - ${forum.category?.name} - Forums`}>
                <meta name="description" content={forum.description || `Discussions and topics in ${forum.name}`} />
                <meta property="og:title" content={`${forum.name} - ${forum.category?.name} - Forums - ${siteName}`} />
                <meta property="og:description" content={forum.description || `Discussions and topics in ${forum.name}`} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
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
                                onSuccess={() => router.reload({ only: ['forum'] })}
                            />
                            {forum.forumPermissions.canDelete && (
                                <>
                                    {selectedTopics.length > 0 && (
                                        <>
                                            <Button variant="destructive" onClick={handleBulkDelete} disabled={isDeleting}>
                                                <Trash2 />
                                                Delete {selectedTopics.length} Topic{selectedTopics.length > 1 ? 's' : ''}
                                            </Button>
                                            <Button variant="outline" onClick={() => setSelectedTopics([])}>
                                                Clear Selection
                                            </Button>
                                        </>
                                    )}
                                    {selectedTopics.length === 0 && topics && topics.data.length > 0 && (
                                        <Button variant="outline" onClick={() => handleSelectAll(true)}>
                                            Select All
                                        </Button>
                                    )}
                                </>
                            )}
                            {forum.forumPermissions.canCreate && (
                                <Button asChild>
                                    <Link href={route('forums.topics.create', { forum: forum.slug })}>
                                        <Plus />
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

                <Deferred fallback={<></>} data="children">
                    {children && children.length > 0 && (
                        <div className="relative rounded-md border bg-background">
                            <Table className="table table-fixed">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[60%] pl-4">Subforums</TableHead>
                                        <TableHead className="hidden w-[10%] text-center md:table-cell">Topics</TableHead>
                                        <TableHead className="hidden w-[10%] text-center md:table-cell">Posts</TableHead>
                                        <TableHead className="hidden w-[20%] pr-4 text-right md:table-cell">Latest Activity</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {children.map((subforum) => (
                                        <TableRow key={subforum.id} className="hover:bg-accent/20">
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
                                                    <span>{abbreviateNumber(subforum.topicsCount || 0)}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden p-4 text-center md:table-cell">
                                                <div className="flex items-center justify-center gap-1">
                                                    <MessageSquare className="size-4" />
                                                    <span>{abbreviateNumber(subforum.postsCount || 0)}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden p-4 text-right md:table-cell">
                                                {subforum.latestTopic ? (
                                                    <div className="text-sm">
                                                        <div className="mb-1">
                                                            <Link
                                                                href={route('forums.topics.show', {
                                                                    forum: subforum.slug,
                                                                    topic: subforum.latestTopic.slug,
                                                                })}
                                                                className="font-medium text-wrap break-words hover:underline"
                                                            >
                                                                {subforum.latestTopic.title}
                                                            </Link>
                                                        </div>
                                                        <div className="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                                                            <Avatar className="size-4">
                                                                <AvatarFallback className="text-xs">
                                                                    {subforum.latestTopic.author?.name?.charAt(0).toUpperCase() || 'U'}
                                                                </AvatarFallback>
                                                            </Avatar>
                                                            <span>by {subforum.latestTopic.author?.name}</span>
                                                            <span>•</span>
                                                            <span>
                                                                {subforum.latestTopic.lastPost?.createdAt
                                                                    ? formatDistanceToNow(new Date(subforum.latestTopic.lastPost.createdAt), {
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
                </Deferred>

                {topics && topics.data.length > 0 && <Pagination pagination={topics} baseUrl={route('forums.show', forum)} entityLabel="topic" />}

                <Deferred fallback={<Loading variant="table" />} data="topics">
                    {topics && topics.data.length > 0 ? (
                        <div className="relative rounded-md border bg-background">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[80%] pl-4">Topics</TableHead>
                                        <TableHead className="w-[5%] text-center">Replies</TableHead>
                                        <TableHead className="w-[5%] text-center">Views</TableHead>
                                        <TableHead className="w-[10%] pr-4 text-right">Last Activity</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {topics.data.map((topic) => (
                                        <TableRow
                                            key={topic.id}
                                            className={`hover:bg-accent/20 ${selectedTopics.includes(topic.id) ? 'bg-info-foreground' : ''}`}
                                        >
                                            <TableCell className="p-4">
                                                <div className="flex items-start gap-3">
                                                    {forum.forumPermissions.canDelete ? (
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
                                                            {forum.forumPermissions.canModerate && (
                                                                <>
                                                                    {topic.hasReportedContent && (
                                                                        <AlertTriangle className="size-4 text-destructive" />
                                                                    )}
                                                                    {topic.hasUnpublishedContent && <EyeOff className="size-4 text-warning" />}
                                                                    {topic.hasUnapprovedContent && <ThumbsDown className="size-4 text-warning" />}
                                                                </>
                                                            )}
                                                            <Link
                                                                href={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                                                                className={cn('hover:underline', {
                                                                    'font-normal text-muted-foreground': auth && auth.user && topic.isReadByUser,
                                                                    'font-medium text-foreground': !topic.isReadByUser,
                                                                })}
                                                            >
                                                                {truncate(topic.title)}
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
                                                    <span>{abbreviateNumber(topic.postsCount)}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="p-4 text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <Eye className="size-4" />
                                                    <span>{abbreviateNumber(topic.viewsCount)}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="p-4 text-right">
                                                {topic.lastPost ? (
                                                    <div className="text-sm">
                                                        <StyledUserName user={topic.lastPost.author} showIcon={false} />
                                                        {topic.lastPost.createdAt && (
                                                            <div className="text-xs text-muted-foreground">
                                                                {formatDistanceToNow(new Date(topic.lastPost.createdAt), { addSuffix: true })}
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
                        </div>
                    ) : (
                        <EmptyState icon={<LibraryBig />} title="No topics yet" description="Be the first to start a discussion in this forum." />
                    )}
                </Deferred>

                {topics && topics.data.length > 0 && <Pagination pagination={topics} baseUrl={route('forums.show', forum)} entityLabel="topic" />}
            </div>
        </AppLayout>
    );
}
