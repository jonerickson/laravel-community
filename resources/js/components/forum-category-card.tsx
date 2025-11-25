import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import usePermissions from '@/hooks/use-permissions';
import { cn, pluralize } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';
import { AlertTriangle, Circle, EyeOff, Lock, MessageSquare, Pin, ThumbsDown } from 'lucide-react';
import { route } from 'ziggy-js';

interface ForumCategoryCardProps {
    category: App.Data.ForumCategoryData;
}

export default function ForumCategoryCard({ category }: ForumCategoryCardProps) {
    const { auth } = usePage<App.Data.SharedData>().props;
    const { can } = usePermissions();

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
                                        {category.postsCount} {pluralize('post', category.postsCount)}
                                    </div>
                                </div>
                            </Link>
                            {category.forums && category.forums.length > 0 && (
                                <div className="mt-4 flex flex-wrap gap-2 md:flex-col md:gap-0 md:space-y-2">
                                    {category.forums.map((forum) => (
                                        <Button size="sm" variant="outline" asChild>
                                            <div key={forum.id} className="flex w-full items-center justify-start gap-2 sm:w-auto">
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
                            {category.forums?.flatMap((forum) =>
                                (forum.latestTopics || []).slice(0, 5).map((topic) => (
                                    <Link
                                        key={`${forum.id}-${topic.id}`}
                                        href={route('forums.topics.show', { forum: forum.slug, topic: topic.slug })}
                                        className="flex items-center gap-3 px-4 py-2 hover:bg-accent/20 sm:px-6"
                                    >
                                        <div className="flex-shrink-0">
                                            <div
                                                className="flex size-6 items-center justify-center rounded-full text-sm font-medium text-white sm:size-8"
                                                style={{ backgroundColor: forum.color }}
                                            >
                                                {topic.author.name?.charAt(0).toUpperCase() || 'U'}
                                            </div>
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                {auth && auth.user && !topic.isReadByUser && <Circle className="size-3 fill-info text-info" />}
                                                {topic.isHot && <span className="text-sm">🔥</span>}
                                                {topic.isPinned && <Pin className="size-4 text-info" />}
                                                {topic.isLocked && <Lock className="size-4 text-muted-foreground" />}
                                                {can('report_posts') && topic.hasReportedContent && (
                                                    <AlertTriangle className="size-4 text-destructive" />
                                                )}
                                                {can('publish_posts') && topic.hasUnpublishedContent && <EyeOff className="size-4 text-warning" />}
                                                {can('approve_posts') && topic.hasUnapprovedContent && <ThumbsDown className="size-4 text-warning" />}
                                                <span
                                                    className={cn('truncate text-sm sm:text-base', {
                                                        'font-normal text-muted-foreground': auth && auth.user && topic.isReadByUser,
                                                        'font-medium text-foreground': !topic.isReadByUser,
                                                    })}
                                                >
                                                    {topic.title}
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
                                                    <span>{topic.postsCount}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </Link>
                                )),
                            )}
                            {!category.forums?.some((forum) => forum.latestTopics && forum.latestTopics.length > 0) && (
                                <EmptyState title="No topics" description="There are no recent topics to show." border={false} />
                            )}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
