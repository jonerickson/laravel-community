import HeadingSmall from '@/components/heading-small';
import { UserInfo } from '@/components/user-info';
import { Post } from '@/types';
import { Link } from '@inertiajs/react';
import { Clock } from 'lucide-react';

interface BlogIndexItemProps {
    post: Post;
}

export default function BlogIndexItem({ post }: BlogIndexItemProps) {
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
                    {post.featured_image ? (
                        <img
                            alt={post.title}
                            src={post.featured_image}
                            className="aspect-video w-full rounded-2xl bg-muted object-cover sm:aspect-[2/1] lg:aspect-[3/2]"
                        />
                    ) : (
                        <div className="flex aspect-video w-full items-center justify-center rounded-2xl bg-muted sm:aspect-[2/1] lg:aspect-[3/2]">
                            <span className="text-sm text-muted-foreground">No image</span>
                        </div>
                    )}
                    <div className="absolute inset-0 rounded-2xl ring-1 ring-gray-900/10 ring-inset" />
                </div>
                <div className="flex max-w-xl grow flex-col justify-between">
                    <div className="mt-8 flex items-center gap-x-4 text-xs">
                        <time dateTime={post.published_at || post.created_at} className="text-muted-foreground">
                            {formattedDate}
                        </time>
                        {post.is_featured && (
                            <span className="relative z-10 rounded-full bg-primary/10 px-3 py-1.5 font-medium text-primary">Featured</span>
                        )}
                        {post.reading_time && (
                            <div className="flex items-center gap-1 text-muted-foreground">
                                <Clock className="h-3 w-3" />
                                <span>{post.reading_time} min read</span>
                            </div>
                        )}
                    </div>
                    <div className="group relative mt-2 grow">
                        <HeadingSmall title={post.title} description={post.excerpt || post.content.substring(0, 150) + '...'} />
                    </div>
                    <div className="flex items-center gap-2 py-1.5 pt-4 text-left text-sm">
                        {post.author && <UserInfo user={post.author} showEmail={false} />}
                    </div>
                </div>
            </article>
        </Link>
    );
}
