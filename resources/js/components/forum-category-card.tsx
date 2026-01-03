import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { abbreviateNumber, cn, pluralize } from '@/lib/utils';
import { truncate } from '@/utils/truncate';
import { Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { AlertTriangle, Circle, EyeOff, Lock, MessageSquare, Pin, ThumbsDown } from 'lucide-react';
import { route } from 'ziggy-js';

interface ForumCategoryCardProps {
    category: App.Data.ForumCategoryData;
}

export default function ForumCategoryCard({ category }: ForumCategoryCardProps) {
    const { auth } = usePage<App.Data.SharedData>().props;

    const allTopics = (category.forums || [])
        .flatMap((forum) =>
            (forum.latestTopics || []).map((topic) => ({
                ...topic,
                forum,
            })),
        )
        .sort((a, b) => {
            const dateA = a.updatedAt ? new Date(a.updatedAt).getTime() : 0;
            const dateB = b.updatedAt ? new Date(b.updatedAt).getTime() : 0;
            return dateB - dateA;
        });

    return (
        <Card className="overflow-hidden py-0 transition-shadow hover:shadow-sm">
            <CardContent className="p-0">
                <div className="flex flex-col md:flex-row">
                    <div className="w-full border-b bg-primary/5 px-4 py-4 sm:px-6 md:w-72 md:border-r md:border-b-0">
                        <div className="space-y-3">
                            <Link href={route('forums.categories.show', { category: category.slug })}>
                                <Heading title={category.name} description={category.description || undefined} />
                                <div className="-mt-4">
                                    {category.featuredImageUrl && (
                                        <div className="pb-4">
                                            <img
                                                src={category.featuredImageUrl}
                                                alt={`${category.name} category image`}
                                                className="h-48 w-full rounded-lg object-cover"
                                            />
                                        </div>
                                    )}
                                    <div className="text-sm text-muted-foreground">
                                        {abbreviateNumber(category.postsCount)} {pluralize('post', category.postsCount)}
                                    </div>
                                </div>
                            </Link>
                            {category.forums && category.forums.length > 0 && (
                                <div className="mt-4 flex flex-wrap gap-2 md:flex-col md:gap-0 md:space-y-2">
                                    {category.forums.map((forum) => (
                                        <Button key={forum.id} size="sm" variant="outline" asChild>
                                            <div className="flex w-full items-center justify-start gap-2 sm:w-auto">
                                                <div className="h-2 w-2 rounded-full" style={{ backgroundColor: forum.color }} />
                                                <Link
                                                    href={route('forums.show', { forum: forum.slug })}
                                                    className="text-sm font-medium hover:underline"
                                                >
                                                    {forum.name}
                                                </Link>
                                            </div>
                                        </Button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="flex-1 py-3">
                        <div className="space-y-1">
                            {allTopics.map((topic) => (
                                <Link
                                    key={`${topic.forum.id}-${topic.id}`}
                                    href={route('forums.topics.show', { forum: topic.forum.slug, topic: topic.slug })}
                                    className="flex items-center gap-3 px-4 py-2 hover:bg-accent/20 sm:px-6"
                                >
                                    <div className="flex-shrink-0">
                                        <div
                                            className="flex size-6 items-center justify-center rounded-full text-sm font-medium text-white sm:size-8"
                                            style={{ backgroundColor: topic.forum.color }}
                                        >
                                            {topic.author.name?.charAt(0).toUpperCase() || 'U'}
                                        </div>
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <div className="flex items-center gap-2">
                                            {auth && auth.user && !topic.isReadByUser && <Circle className="size-3 shrink-0 fill-info text-info" />}
                                            {topic.isHot && <span className="shrink-0 text-sm">🔥</span>}
                                            {topic.isPinned && <Pin className="size-4 shrink-0 text-info" />}
                                            {topic.isLocked && <Lock className="size-4 shrink-0 text-muted-foreground" />}
                                            {topic.forum.forumPermissions.canModerate && (
                                                <>
                                                    {topic.hasReportedContent && <AlertTriangle className="size-4 shrink-0 text-destructive" />}
                                                    {topic.hasUnpublishedContent && <EyeOff className="size-4 shrink-0 text-warning" />}
                                                    {topic.hasUnapprovedContent && <ThumbsDown className="size-4 shrink-0 text-warning" />}
                                                </>
                                            )}
                                            <span
                                                className={cn('text-sm text-pretty sm:text-base', {
                                                    'font-normal text-muted-foreground': auth && auth.user && topic.isReadByUser,
                                                    'font-medium text-foreground': !topic.isReadByUser,
                                                })}
                                            >
                                                {truncate(topic.title)}
                                            </span>
                                        </div>
                                        <div className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                            <span className="text-nowrap">Started by {topic.author.name}</span>
                                            <span>•</span>
                                            <span className="truncate text-nowrap">
                                                {topic.lastPost?.createdAt
                                                    ? formatDistanceToNow(new Date(topic.lastPost.createdAt), { addSuffix: true })
                                                    : 'N/A'}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="flex-shrink-0 text-right">
                                        <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                            <div className="flex items-center gap-1">
                                                <MessageSquare className="size-3" />
                                                <span>{abbreviateNumber(topic.postsCount)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </Link>
                            ))}
                            {allTopics.length === 0 && (
                                <EmptyState title="No topics" description="There are no recent topics to show." border={false} />
                            )}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
