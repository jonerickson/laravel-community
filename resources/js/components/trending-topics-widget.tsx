import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import type { Topic } from '@/types';
import { Link } from '@inertiajs/react';
import { format, formatDistanceToNow } from 'date-fns';
import { Eye, Flame, Lock, MessageSquare, Pin, TrendingUp } from 'lucide-react';

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

            <div className="overflow-hidden rounded-lg border border-sidebar-border/50">
                <div className="overflow-x-auto">
                    <table className="w-full">
                        <thead className="bg-muted/50">
                            <tr className="border-b border-sidebar-border/50">
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase">#</th>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase">Topic</th>
                                <th className="px-4 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase">Score</th>
                                <th className="px-4 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase">Views</th>
                                <th className="px-4 py-3 text-center text-xs font-medium tracking-wider text-muted-foreground uppercase">Replies</th>
                                <th className="px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase">Last Reply</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-sidebar-border/30">
                            {topics.slice(0, 5).map((topic, index) => (
                                <tr key={topic.id} className="group transition-colors hover:bg-accent/20">
                                    <td className="px-4 py-3">
                                        <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-orange-500/10 text-xs font-bold text-orange-600 dark:text-orange-400">
                                            {index + 1}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <Link
                                            href={route('forums.topics.show', [topic.forum?.slug, topic.slug])}
                                            className="block space-y-1 transition-colors group-hover:text-primary"
                                        >
                                            <div className="flex items-center gap-2">
                                                {topic.is_hot && <span className="text-sm">🔥</span>}
                                                {topic.is_pinned && <Pin className="size-3 text-info" />}
                                                {topic.is_locked && <Lock className="size-3 text-muted-foreground" />}
                                                <span className="line-clamp-1 text-sm font-medium">{topic.title}</span>
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                in <span className="font-medium">{topic.forum?.name}</span> by{' '}
                                                <span className="font-medium">{topic.author.name}</span>
                                            </div>
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        <Badge variant={getTrendingScoreVariant(topic.trending_score)} className="px-2 py-1 text-xs">
                                            <TrendingUp className="mr-1 size-3" />
                                            {formatTrendingScore(topic.trending_score)}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        <div className="flex items-center justify-center gap-1 text-sm text-muted-foreground">
                                            <Eye className="size-3" />
                                            <span>{topic.views_count}</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-center">
                                        <div className="flex items-center justify-center gap-1 text-sm text-muted-foreground">
                                            <MessageSquare className="size-3" />
                                            <span>{topic.posts_count}</span>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {topic.last_post ? (
                                            <div className="space-y-1">
                                                <div className="text-xs text-muted-foreground">
                                                    <span className="font-medium">{topic.last_post.author.name}</span>
                                                </div>
                                                <div className="text-xs text-muted-foreground/80">
                                                    {formatDistanceToNow(new Date(topic.last_post.created_at), { addSuffix: true })}
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="text-xs text-muted-foreground">{format(new Date(topic.created_at), 'MMM d')}</div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {topics.length > 5 && (
                    <div className="border-t border-sidebar-border/50 bg-muted/30 px-4 py-2">
                        <Link href={route('forums.index')} className="text-xs text-muted-foreground transition-colors hover:text-foreground">
                            +{topics.length - 5} more trending topics
                        </Link>
                    </div>
                )}
            </div>
        </div>
    );
}
