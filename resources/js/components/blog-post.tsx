import BlogComments from '@/components/blog-comments';
import { UserInfo } from '@/components/user-info';
import { Post } from '@/types';
import { Calendar, Clock } from 'lucide-react';

interface BlogPostProps {
    post: Post;
}

export default function BlogPost({ post }: BlogPostProps) {
    const publishedDate = new Date(post.published_at || post.created_at);
    const formattedDate = publishedDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    return (
        <article className="mx-auto max-w-4xl">
            <div className="mb-8">
                {post.is_featured && (
                    <div className="mb-4">
                        <span className="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary">
                            Featured Post
                        </span>
                    </div>
                )}

                <h1 className="mb-4 text-4xl font-bold tracking-tight text-foreground lg:text-5xl">{post.title}</h1>

                {post.excerpt && <p className="mb-6 text-xl text-muted-foreground">{post.excerpt}</p>}

                <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                    {post.author && <UserInfo user={post.author} showEmail={false} />}

                    <div className="flex items-center gap-1">
                        <Calendar className="h-4 w-4" />
                        <time dateTime={post.published_at || post.created_at}>{formattedDate}</time>
                    </div>

                    {post.reading_time && (
                        <div className="flex items-center gap-1">
                            <Clock className="h-4 w-4" />
                            <span>{post.reading_time} min read</span>
                        </div>
                    )}
                </div>
            </div>

            {post.featured_image_url && (
                <div className="mb-8">
                    <img src={post.featured_image_url} alt={post.title} className="aspect-video w-full rounded-lg object-cover" />
                </div>
            )}

            <div
                className="prose prose-lg dark:prose-invert prose-headings:text-foreground prose-p:text-foreground prose-li:text-foreground prose-strong:text-foreground prose-a:text-primary hover:prose-a:text-primary/80 prose-code:text-foreground prose-pre:bg-muted max-w-none"
                dangerouslySetInnerHTML={{ __html: post.content }}
            />

            <div className="mt-12 border-t pt-8">
                <BlogComments post={post} />
            </div>
        </article>
    );
}
