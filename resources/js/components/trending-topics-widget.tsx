import { Badge } from '@/components/ui/badge';
import { abbreviateNumber } from '@/lib/utils';
import { truncate } from '@/utils/truncate';
import { Link } from '@inertiajs/react';
import { format, formatDistanceToNow } from 'date-fns';
import { AlertTriangle, Eye, EyeOff, Lock, MessageSquare, Pin, ThumbsDown, TrendingUp } from 'lucide-react';

interface TrendingTopicsWidgetProps {
    topics?: App.Data.TopicData[];
}

export default function TrendingTopicsWidget({ topics = [] }: TrendingTopicsWidgetProps) {
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
        <div className="relative overflow-hidden rounded-lg border border-sidebar-border/50 bg-background">
            <div className="overflow-x-auto">
                <table className="w-full">
                    <thead className="bg-muted/50">
                        <tr className="border-b border-sidebar-border/50">
                            <th className="w-0 px-4 py-3 text-left text-xs font-medium tracking-wider text-muted-foreground uppercase">#</th>
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
                                            {topic.isHot && <span className="text-sm">🔥</span>}
                                            {topic.isPinned && <Pin className="size-3 text-info" />}
                                            {topic.isLocked && <Lock className="size-3 text-muted-foreground" />}
                                            {topic.forum?.forumPermissions.canModerate && (
                                                <>
                                                    {topic.hasReportedContent && <AlertTriangle className="size-3 text-destructive" />}
                                                    {topic.hasUnpublishedContent && <EyeOff className="size-3 text-warning" />}
                                                    {topic.hasUnapprovedContent && <ThumbsDown className="size-3 text-warning" />}
                                                </>
                                            )}
                                            <span className="line-clamp-1 text-sm font-medium">{truncate(topic.title)}</span>
                                        </div>
                                        <div className="text-xs text-muted-foreground">
                                            in <span className="font-medium">{topic.forum?.name}</span> by{' '}
                                            <span className="font-medium">{topic.author.name}</span>
                                        </div>
                                    </Link>
                                </td>
                                <td className="w-0 px-4 py-3 text-center">
                                    <Badge variant={getTrendingScoreVariant(topic.trendingScore)} className="px-2 py-1 text-xs">
                                        <TrendingUp className="mr-1 size-3" />
                                        {formatTrendingScore(topic.trendingScore)}
                                    </Badge>
                                </td>
                                <td className="px-4 py-3 text-center">
                                    <div className="flex items-center justify-center gap-1 text-sm text-muted-foreground">
                                        <Eye className="size-3" />
                                        <span>{abbreviateNumber(topic.viewsCount)}</span>
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-center">
                                    <div className="flex items-center justify-center gap-1 text-sm text-muted-foreground">
                                        <MessageSquare className="size-3" />
                                        <span>{abbreviateNumber(topic.postsCount)}</span>
                                    </div>
                                </td>
                                <td className="px-4 py-3">
                                    {topic.lastPost ? (
                                        <div className="space-y-1">
                                            <div className="text-xs text-muted-foreground">
                                                <span className="font-medium">{topic.lastPost.author.name}</span>
                                            </div>
                                            <div className="text-xs text-muted-foreground/80">
                                                {topic.lastPost.createdAt
                                                    ? formatDistanceToNow(new Date(topic.lastPost.createdAt), { addSuffix: true })
                                                    : 'N/A'}
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="text-xs text-muted-foreground">
                                            {topic.createdAt ? format(new Date(topic.createdAt), 'MMM d') : 'N/A'}
                                        </div>
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
    );
}
