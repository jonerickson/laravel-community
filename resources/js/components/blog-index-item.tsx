import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { UserInfo } from '@/components/user-info';
import { pluralize } from '@/lib/utils';
import { Post } from '@/types';
import { Link } from '@inertiajs/react';
import { Clock, Eye, ImageIcon, MessageCircle } from 'lucide-react';
import usePermissions from '../hooks/use-permissions';

interface BlogIndexItemProps {
    post: Post;
}

export default function BlogIndexItem({ post }: BlogIndexItemProps) {
    const { can } = usePermissions();
    const publishedDate = new Date(post.published_at || post.created_at);
    const formattedDate = publishedDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    return (
        <Link href={route('blog.show', { post: post.slug })}>
            <article className="flex flex-col items-start justify-between">
                <div className="relative w-full">
                    {post.featured_image_url ? (
                        <img
                            alt={post.title}
                            src={post.featured_image_url}
                            className="aspect-video w-full rounded-2xl bg-muted object-cover sm:aspect-[2/1] lg:aspect-[3/2]"
                        />
                    ) : (
                        <div className="flex aspect-video w-full items-center justify-center rounded-2xl bg-muted sm:aspect-[2/1] lg:aspect-[3/2]">
                            <ImageIcon className="h-16 w-16 text-muted-foreground" />
                        </div>
                    )}
                    <div className="absolute inset-0 rounded-2xl ring-1 ring-gray-900/10 ring-inset" />
                </div>
                <div className="mt-4 flex max-w-xl grow flex-col justify-between">
                    <div className="flex flex-row gap-2">
                        {post.is_featured && <Badge variant="secondary">Featured</Badge>}
                        {!post.is_read_by_user && <Badge variant="default">New</Badge>}
                    </div>
                    <div className="mt-2 flex items-center gap-x-4 text-xs">
                        <time dateTime={post.published_at || post.created_at} className="text-muted-foreground">
                            {formattedDate}
                        </time>

                        <div className="flex items-center gap-1 text-muted-foreground">
                            <Eye className="h-3 w-3" />
                            <span>
                                {post.views_count} {pluralize('view', post.views_count)}
                            </span>
                        </div>

                        {can('view_any_comments') && post.comments_enabled && (
                            <div className="flex items-center gap-1 text-muted-foreground">
                                <MessageCircle className="h-3 w-3" />
                                <span>
                                    {post.comments_count} {pluralize('comment', post.comments_count)}
                                </span>
                            </div>
                        )}

                        {post.reading_time && (
                            <div className="flex items-center gap-1 text-muted-foreground">
                                <Clock className="h-3 w-3" />
                                <span>{post.reading_time} min read</span>
                            </div>
                        )}
                    </div>
                    <div className="group relative mt-2 grow">
                        <HeadingSmall
                            title={post.title}
                            description={post.excerpt || post.content.replace(/<\/?[^>]+(>|$)/g, '').substring(0, 150) + '...'}
                        />
                    </div>
                    <div className="flex items-center gap-2 py-1.5 pt-4 text-left text-sm">
                        {post.author && <UserInfo user={post.author} showEmail={false} />}
                    </div>
                </div>
            </article>
        </Link>
    );
}
