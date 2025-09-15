import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import type { Topic } from '@/types';
import { Link } from '@inertiajs/react';
import { format, formatDistanceToNow } from 'date-fns';
import { Clock, Eye, Flame, Lock, MessageSquare, Pin, TrendingUp, Users } from 'lucide-react';

interface TrendingTopicsWidgetProps {
    topics?: Topic[];
    className?: string;
}

export default function TrendingTopicsWidget({ topics = [], className }: TrendingTopicsWidgetProps) {
    if (topics.length === 0) {
        return (
            <div className={`relative ${className}`}>
                <div className="relative overflow-hidden rounded-xl border border-sidebar-border/70 py-12 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="relative flex items-center justify-center">
                        <div className="space-y-3 text-center">
                            <HeadingSmall title="Trending topics" description="No trending topics right now" />
                            <Button asChild variant="outline" size="sm">
                                <Link href={route('forums.index')}>
                                    <TrendingUp className="size-4" />
                                    Browse Forums
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    const formatTrendingScore = (score: number): string => {
        if (score >= 1000) {
            return `${(score / 1000).toFixed(1)}k`;
        }
        return Math.round(score).toString();
    };

    const getTrendingScoreVariant = (score: number): 'default' | 'secondary' | 'destructive' | 'outline' => {
        if (score >= 100) return 'destructive'; // Very hot
        if (score >= 50) return 'default'; // Hot
        if (score >= 10) return 'secondary'; // Warm
        return 'outline'; // Cool
    };

    return (
        <div className={`space-y-4 ${className}`}>
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="flex items-center gap-2 text-lg font-semibold">
                        <Flame className="size-4 text-orange-400" />
                        Trending topics
                    </h2>
                    <p className="text-sm text-muted-foreground">The most engaging forum discussions right now</p>
                </div>
                <Link href={route('forums.index')} className="text-sm font-medium text-primary hover:underline">
                    View all forums
                </Link>
            </div>

            <div className="space-y-3">
                {topics.slice(0, 5).map((topic, index) => (
                    <Link
                        key={topic.id}
                        href={route('forums.topics.show', [topic.forum?.slug, topic.slug])}
                        className="group block grid cursor-pointer grid-cols-12 gap-4 rounded-lg border border-sidebar-border/50 bg-card/30 p-4 transition-all duration-200 hover:border-accent/30 hover:bg-accent/20 hover:shadow-sm"
                    >
                        <div className="col-span-8 space-y-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-bold text-orange-600 dark:text-orange-400">
                                    #{index + 1}
                                </span>
                                {topic.is_hot && <span className="text-sm">🔥</span>}
                                {topic.is_pinned && <Pin className="size-4 text-info" />}
                                {topic.is_locked && <Lock className="size-4 text-muted-foreground" />}
                                <span className="line-clamp-1 text-sm font-medium">{topic.title}</span>
                            </div>
                            <div className="text-xs text-muted-foreground">
                                in <span className="font-medium">{topic.forum?.name}</span> by{' '}
                                <span className="font-medium">{topic.author.name}</span> • <span>{format(new Date(topic.created_at), 'MMM d')}</span>
                            </div>
                        </div>

                        <div className="col-span-4 flex items-start justify-end">
                            <div className="flex items-center gap-4 text-xs text-muted-foreground">
                                <div className="flex items-center gap-1">
                                    <TrendingUp className="size-3" />
                                    <Badge variant={getTrendingScoreVariant(topic.trending_score)} className="px-1.5 py-0.5 text-xs">
                                        {formatTrendingScore(topic.trending_score)}
                                    </Badge>
                                </div>
                                <div className="flex items-center gap-1">
                                    <Eye className="size-3" />
                                    <span>{topic.views_count}</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <MessageSquare className="size-3" />
                                    <span>{topic.posts_count}</span>
                                </div>
                                <div className="flex items-center gap-1">
                                    <Users className="size-3" />
                                    <span>{topic.unique_views_count}</span>
                                </div>
                            </div>
                        </div>

                        {topic.last_post && (
                            <div className="col-span-12 space-y-1 border-t border-sidebar-border/30 pt-2">
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <Clock className="size-3" />
                                    <span>
                                        <span className="font-medium">{topic.last_post.author.name}</span> replied{' '}
                                        {formatDistanceToNow(new Date(topic.last_post.created_at), { addSuffix: true })}
                                    </span>
                                </div>
                                <div className="line-clamp-2 text-xs text-muted-foreground/80 italic">
                                    "{topic.last_post.content.replace(/<[^>]*>/g, '').substring(0, 200)}
                                    {topic.last_post.content.length > 200 ? '...' : ''}"
                                </div>
                            </div>
                        )}
                    </Link>
                ))}

                {topics.length > 5 && (
                    <div className="border-t border-sidebar-border/50 pt-2">
                        <Link href={route('forums.index')} className="text-xs text-muted-foreground transition-colors hover:text-foreground">
                            +{topics.length - 5} more trending topics
                        </Link>
                    </div>
                )}
            </div>
        </div>
    );
}
