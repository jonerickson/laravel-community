import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { UserInfo } from '@/components/user-info';
import { pluralize } from '@/lib/utils';
import { stripCharacters, truncate } from '@/utils/truncate';
import { Link } from '@inertiajs/react';
import { Clock, Eye, ImageIcon, MessageCircle } from 'lucide-react';
import usePermissions from '../hooks/use-permissions';

interface BlogIndexItemProps {
    post: App.Data.PostData;
}

export default function BlogIndexItem({ post }: BlogIndexItemProps) {
    const { can } = usePermissions();
    const publishedDate = new Date(post.publishedAt || post.createdAt || new Date());
    const formattedDate = publishedDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    return (
        <Link href={route('blog.show', { post: post.slug })} className="group">
            <article className="flex flex-col items-start justify-between">
                <div className="relative w-full overflow-hidden rounded-2xl">
                    {post.featuredImageUrl ? (
                        <img
                            alt={post.title}
                            src={post.featuredImageUrl}
                            className="aspect-video w-full rounded-2xl bg-muted object-cover transition-transform group-hover:scale-105 group-hover:shadow-lg sm:aspect-[2/1] lg:aspect-[3/2]"
                        />
                    ) : (
                        <div className="flex aspect-video w-full items-center justify-center rounded-2xl bg-muted transition-transform group-hover:scale-105 group-hover:shadow-lg sm:aspect-[2/1] lg:aspect-[3/2]">
                            <ImageIcon className="h-16 w-16 text-muted-foreground" />
                        </div>
                    )}
                    <div className="absolute inset-0 rounded-2xl ring-1 ring-ring/20 ring-inset" />
                </div>
                <div className="mt-4 flex max-w-xl grow flex-col justify-between">
                    <div className="flex flex-row gap-2">
                        {post.isFeatured && <Badge variant="secondary">Featured</Badge>}
                        {!post.isReadByUser && <Badge variant="default">New</Badge>}
                    </div>
                    <div className="mt-2 flex items-center gap-x-4 text-xs">
                        <time dateTime={post.publishedAt || post.createdAt || undefined} className="text-muted-foreground">
                            {formattedDate}
                        </time>

                        <div className="flex items-center gap-1 text-muted-foreground">
                            <Eye className="size-3" />
                            <span>
                                {post.viewsCount} {pluralize('view', post.viewsCount)}
                            </span>
                        </div>

                        {can('view_any_comments') && post.commentsEnabled && (
                            <div className="flex items-center gap-1 text-muted-foreground">
                                <MessageCircle className="size-3" />
                                <span>
                                    {post.commentsCount} {pluralize('comment', post.commentsCount)}
                                </span>
                            </div>
                        )}

                        {post.readingTime && (
                            <div className="flex items-center gap-1 text-muted-foreground">
                                <Clock className="size-3" />
                                <span>{post.readingTime} min read</span>
                            </div>
                        )}
                    </div>
                    <div className="group relative mt-2 grow">
                        <HeadingSmall title={post.title} description={post.excerpt || truncate(stripCharacters(post.content))} />
                    </div>
                    <div className="flex items-center gap-2 py-1.5 pt-4 text-left text-sm">
                        {post.author && <UserInfo user={post.author} showEmail={false} />}
                    </div>
                </div>
            </article>
        </Link>
    );
}
