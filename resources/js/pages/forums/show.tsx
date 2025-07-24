import Heading from '@/components/heading';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardTitle } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum, PaginatedData, Topic } from '@/types';
import { Head, Link, WhenVisible } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Eye, Lock, MessageSquare, Pin, Plus } from 'lucide-react';

interface ForumShowProps {
    forum: Forum;
    topics: Topic[];
    topicsPagination: PaginatedData;
}

export default function ForumShow({ forum, topics, topicsPagination }: ForumShowProps) {
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
            <Head title={`${forum.name} - Forums`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg text-white" style={{ backgroundColor: forum.color }}>
                            <MessageSquare className="h-6 w-6" />
                        </div>
                        <div className="-mb-8">
                            <Heading title={forum.name} description={forum.description ?? ''} />
                        </div>
                    </div>
                    <Link href={`/forums/${forum.slug}/create`}>
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            New Topic
                        </Button>
                    </Link>
                </div>

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
                                <TableRow key={topic.id} className="hover:bg-muted/50">
                                    <TableCell>
                                        <div className="flex items-start gap-3">
                                            <Avatar className="h-8 w-8">
                                                <AvatarFallback className="text-xs">{topic.author?.name.charAt(0).toUpperCase()}</AvatarFallback>
                                            </Avatar>
                                            <div className="min-w-0 flex-1">
                                                <div className="mb-1 flex items-center gap-2">
                                                    {topic.is_pinned && <Pin className="h-4 w-4 text-blue-500" />}
                                                    {topic.is_locked && <Lock className="h-4 w-4 text-gray-500" />}
                                                    <Link href={`/forums/${forum.slug}/${topic.slug}`} className="font-medium hover:underline">
                                                        {topic.title}
                                                    </Link>
                                                </div>
                                                {topic.description && (
                                                    <p className="mb-1 text-sm text-wrap break-words text-muted-foreground">{topic.description}</p>
                                                )}
                                                <div className="text-xs text-muted-foreground">
                                                    Started by {topic.author?.name} •{' '}
                                                    {formatDistanceToNow(new Date(topic.created_at), { addSuffix: true })}
                                                </div>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <div className="flex items-center justify-center gap-1">
                                            <MessageSquare className="h-4 w-4" />
                                            <span>{topic.posts_count}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-center">
                                        <div className="flex items-center justify-center gap-1">
                                            <Eye className="h-4 w-4" />
                                            <span>{topic.views_count}</span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right">
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

                <WhenVisible
                    fallback={<></>}
                    always={topicsPagination.current_page < topicsPagination.last_page}
                    params={{
                        data: {
                            page: topicsPagination.current_page + 1,
                        },
                        only: ['topics', 'topicsPagination'],
                    }}
                >
                    {topicsPagination.current_page >= topicsPagination.last_page ? (
                        <></>
                    ) : (
                        <div className="flex items-center justify-center py-8">
                            <Spinner />
                        </div>
                    )}
                </WhenVisible>

                {topics.length === 0 && (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <MessageSquare className="mb-4 h-12 w-12 text-muted-foreground" />
                            <CardTitle className="mb-2">No Topics Yet</CardTitle>
                            <CardDescription className="mb-4">Be the first to start a discussion in this forum.</CardDescription>
                            <Link href={`/forums/${forum.slug}/create`}>
                                <Button>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Create First Topic
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
