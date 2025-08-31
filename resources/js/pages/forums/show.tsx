import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Pagination } from '@/components/ui/pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useApiRequest } from '@/hooks/use-api-request';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum, PaginatedData, SharedData, Topic } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Circle, Eye, LibraryBig, Lock, MessageSquare, Pin, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface ForumShowProps {
    forum: Forum;
    topics: Topic[];
    topicsPagination: PaginatedData;
}

export default function ForumShow({ forum, topics: initialTopics, topicsPagination }: ForumShowProps) {
    const { auth, name: siteName } = usePage<SharedData>().props;
    const [topics, setTopics] = useState<Topic[]>(initialTopics);
    const [selectedTopics, setSelectedTopics] = useState<number[]>([]);
    const { loading: isDeleting, execute: executeBulkDelete } = useApiRequest();
    const isAdmin = auth?.isAdmin;

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'WebPage',
        name: forum.name,
        description: forum.description || `Discussions and topics in ${forum.name}`,
        url: window.location.href,
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        mainEntity: {
            '@type': 'CollectionPage',
            name: forum.name,
            description: forum.description || `Discussions and topics in ${forum.name}`,
            interactionStatistic: [
                {
                    '@type': 'InteractionCounter',
                    interactionType: 'https://schema.org/CommentAction',
                    userInteractionCount: topics.length,
                },
            ],
            hasPart: topics.map((topic) => ({
                '@type': 'DiscussionForumPosting',
                headline: topic.title,
                description: topic.description,
                dateCreated: topic.created_at,
                dateModified: topic.updated_at,
                author: {
                    '@type': 'Person',
                    name: topic.author?.name,
                },
                url: `/forums/${forum.slug}/${topic.slug}`,
                interactionStatistic: [
                    {
                        '@type': 'InteractionCounter',
                        interactionType: 'https://schema.org/ViewAction',
                        userInteractionCount: topic.views_count,
                    },
                    {
                        '@type': 'InteractionCounter',
                        interactionType: 'https://schema.org/ReplyAction',
                        userInteractionCount: topic.posts_count,
                    },
                ],
            })),
        },
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
                    toast.success('The post(s) have been successfully deleted.');
                },
            },
        );
    };

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Forums',
            href: '/forums',
        },
        {
            title: forum.name,
            href: `/forums/${forum.slug}`,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${forum.name} - Forums`}>
                <meta name="description" content={forum.description || `Discussions and topics in ${forum.name}`} />
                <meta property="og:title" content={`${forum.name} - Forums`} />
                <meta property="og:description" content={forum.description || `Discussions and topics in ${forum.name}`} />
                <meta property="og:type" content="website" />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center sm:gap-0">
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg text-white" style={{ backgroundColor: forum.color }}>
                            <MessageSquare className="h-6 w-6" />
                        </div>
                        <div className="-mb-8">
                            <Heading title={forum.name} description={forum.description ?? ''} />
                        </div>
                    </div>
                    <div className="flex shrink-0 items-center gap-2">
                        {isAdmin && (
                            <>
                                {selectedTopics.length > 0 && (
                                    <>
                                        <Button variant="destructive" onClick={handleBulkDelete} disabled={isDeleting}>
                                            <Trash2 className="mr-2 h-4 w-4" />
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
                        {auth?.user && (
                            <Button asChild>
                                <Link href={route('forums.topics.create', { forum: forum.slug })}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    New Topic
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {forum.rules && (
                    <Alert>
                        <AlertTitle>Forum Rules</AlertTitle>
                        <AlertDescription>
                            <p className="prose prose-base" dangerouslySetInnerHTML={{ __html: forum.rules }} />
                        </AlertDescription>
                    </Alert>
                )}

                {topics.length > 0 && (
                    <>
                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[80%]"></TableHead>
                                        <TableHead className="w-[5%] text-center">Replies</TableHead>
                                        <TableHead className="w-[5%] text-center">Views</TableHead>
                                        <TableHead className="w-[10%] text-right">Last Activity</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {topics.map((topic) => (
                                        <TableRow
                                            key={topic.id}
                                            className={`hover:bg-muted/50 ${selectedTopics.includes(topic.id) ? 'bg-blue-50' : ''}`}
                                        >
                                            <TableCell className="p-4">
                                                <div className="flex items-start gap-3">
                                                    {isAdmin ? (
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
                                                                        {topic.author?.name.charAt(0).toUpperCase()}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                            )}
                                                        </button>
                                                    ) : (
                                                        <Avatar className="h-8 w-8">
                                                            <AvatarFallback className="text-xs">
                                                                {topic.author?.name.charAt(0).toUpperCase()}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                    )}
                                                    <div className="min-w-0 flex-1">
                                                        <div className="mb-1 flex items-center gap-2">
                                                            {!topic.is_read_by_user && <Circle className="h-3 w-3 fill-blue-500 text-blue-500" />}
                                                            {topic.is_hot && <span className="text-sm">🔥</span>}
                                                            {topic.is_pinned && <Pin className="h-4 w-4 text-blue-500" />}
                                                            {topic.is_locked && <Lock className="h-4 w-4 text-gray-500" />}
                                                            <Link
                                                                href={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                                                                className={`hover:underline ${
                                                                    topic.is_read_by_user ? 'font-normal text-muted-foreground' : 'font-medium'
                                                                }`}
                                                            >
                                                                {topic.title}
                                                            </Link>
                                                        </div>
                                                        {topic.description && (
                                                            <p className="mb-1 text-sm text-wrap break-words text-muted-foreground">
                                                                {topic.description}
                                                            </p>
                                                        )}
                                                        <div className="text-xs text-muted-foreground">
                                                            Started by {topic.author?.name} •{' '}
                                                            {formatDistanceToNow(new Date(topic.created_at), { addSuffix: true })}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="p-4 text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <MessageSquare className="h-4 w-4" />
                                                    <span>{topic.posts_count}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="p-4 text-center">
                                                <div className="flex items-center justify-center gap-1">
                                                    <Eye className="h-4 w-4" />
                                                    <span>{topic.views_count}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="p-4 text-right">
                                                {topic.last_post ? (
                                                    <div className="text-sm">
                                                        <div className="font-medium">{topic.last_post.author?.name}</div>
                                                        {topic.last_reply_at && (
                                                            <div className="text-xs text-muted-foreground">
                                                                {formatDistanceToNow(new Date(topic.last_reply_at), { addSuffix: true })}
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

                        <Pagination
                            pagination={topicsPagination}
                            baseUrl={route('forums.show', { forum: forum.slug })}
                            entityLabel="topic"
                            className="py-4"
                        />
                    </>
                )}

                {topics.length === 0 && (
                    <div className="mt-2">
                        <EmptyState
                            icon={<LibraryBig className="h-12 w-12" />}
                            title="No topics yet"
                            description="Be the first to start a discussion in this forum."
                        />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
