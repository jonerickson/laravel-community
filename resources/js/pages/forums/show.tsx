import Heading from '@/components/heading';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Forum, PaginatedData, Topic } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { Clock, Eye, Lock, MessageSquare, Pin, Plus } from 'lucide-react';

interface ForumShowProps {
    forum: Forum;
    topics: {
        data: Topic[];
        pagination: PaginatedData;
    };
}

export default function ForumShow({ forum, topics }: ForumShowProps) {
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

                <div className="grid gap-4">
                    {topics.data.map((topic) => (
                        <Card key={topic.id} className="transition-shadow hover:shadow-md">
                            <CardContent className="p-4">
                                <div className="flex items-start gap-4">
                                    <Avatar className="h-10 w-10">
                                        <AvatarFallback>{topic.author?.name.charAt(0).toUpperCase()}</AvatarFallback>
                                    </Avatar>

                                    <div className="min-w-0 flex-1">
                                        <div className="mb-1 flex items-center gap-2">
                                            {topic.is_pinned && <Pin className="h-4 w-4 text-blue-500" />}
                                            {topic.is_locked && <Lock className="h-4 w-4 text-gray-500" />}
                                            <Link href={`/forums/${forum.slug}/${topic.slug}`} className="text-lg font-semibold hover:underline">
                                                {topic.title}
                                            </Link>
                                        </div>

                                        {topic.description && <p className="mb-2 text-sm text-muted-foreground">{topic.description}</p>}

                                        <div className="flex items-center gap-4 text-sm text-muted-foreground">
                                            <span>Started by {topic.author?.name}</span>
                                            <div className="flex items-center gap-1">
                                                <Eye className="h-4 w-4" />
                                                <span>{topic.views_count} views</span>
                                            </div>
                                            <div className="flex items-center gap-1">
                                                <MessageSquare className="h-4 w-4" />
                                                <span>{topic.replies_count} replies</span>
                                            </div>
                                            <div className="flex items-center gap-1">
                                                <Clock className="h-4 w-4" />
                                                <span>{formatDistanceToNow(new Date(topic.created_at), { addSuffix: true })}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {topic.last_post && (
                                        <div className="text-right text-sm">
                                            <div className="text-muted-foreground">Last reply</div>
                                            <div className="font-medium">{topic.last_post.author?.name}</div>
                                            {topic.last_reply_at && (
                                                <div className="text-xs text-muted-foreground">
                                                    {formatDistanceToNow(new Date(topic.last_reply_at), { addSuffix: true })}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {topics.data.length === 0 && (
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

                {/* Pagination can be added here if needed */}
                {/*{topics.pagination.last_page > 1 && (*/}
                {/*    <div className="flex justify-center">*/}
                {/*        /!* Add pagination component here *!/*/}
                {/*    </div>*/}
                {/*)}*/}
            </div>
        </AppLayout>
    );
}
