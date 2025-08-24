import BlogComments from '@/components/blog-comments';
import EmojiReactions from '@/components/emoji-reactions';
import { Spinner } from '@/components/ui/spinner';
import { UserInfo } from '@/components/user-info';
import { Comment, Post, type PaginatedData, type SharedData } from '@/types';
import { Deferred, usePage } from '@inertiajs/react';
import { Calendar, Clock, ImageIcon } from 'lucide-react';

interface BlogPostProps {
    post: Post;
    comments: Comment[];
    commentsPagination: PaginatedData;
}

export default function BlogPost({ post, comments, commentsPagination }: BlogPostProps) {
    const { auth } = usePage<SharedData>().props;
    const publishedDate = new Date(post.published_at || post.created_at);
    const formattedDate = publishedDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    return (
        <article className="mx-auto max-w-4xl" itemScope itemType="https://schema.org/BlogPosting">
            <header className="mb-8">
                {post.is_featured && (
                    <div className="mb-4" role="banner">
                        <span
                            className="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-sm font-medium text-primary"
                            aria-label="Featured blog post"
                        >
                            Featured Post
                        </span>
                    </div>
                )}

                <h1 className="mb-4 text-4xl font-bold tracking-tight text-foreground lg:text-5xl" itemProp="headline">
                    {post.title}
                </h1>

                {post.excerpt && (
                    <p className="mb-6 text-xl text-muted-foreground" itemProp="description">
                        {post.excerpt}
                    </p>
                )}

                <div className="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                    {post.author && (
                        <div itemProp="author" itemScope itemType="https://schema.org/Person">
                            <UserInfo user={post.author} showEmail={false} />
                            <meta itemProp="name" content={post.author.name} />
                        </div>
                    )}

                    <div className="flex items-center gap-1">
                        <Calendar className="h-4 w-4" aria-hidden="true" />
                        <time dateTime={post.published_at || post.created_at} itemProp="datePublished" aria-label={`Published on ${formattedDate}`}>
                            {formattedDate}
                        </time>
                    </div>

                    {post.reading_time && (
                        <div className="flex items-center gap-1">
                            <Clock className="h-4 w-4" aria-hidden="true" />
                            <span aria-label={`Estimated reading time: ${post.reading_time} minutes`}>{post.reading_time} min read</span>
                        </div>
                    )}
                </div>

                <meta itemProp="dateModified" content={post.updated_at} />
                <meta itemProp="url" content={window.location.href} />
            </header>

            <figure className="mb-8" itemProp="image" itemScope itemType="https://schema.org/ImageObject">
                {post.featured_image_url ? (
                    <>
                        <img
                            src={post.featured_image_url}
                            alt={`Featured image for ${post.title}`}
                            className="aspect-video w-full rounded-lg object-cover"
                            itemProp="url"
                            loading="eager"
                        />
                        <meta itemProp="width" content="1200" />
                        <meta itemProp="height" content="675" />
                    </>
                ) : (
                    <div
                        className="flex aspect-video w-full items-center justify-center rounded-lg bg-muted"
                        role="img"
                        aria-label="No featured image available"
                    >
                        <ImageIcon className="h-16 w-16 text-muted-foreground" aria-hidden="true" />
                    </div>
                )}
            </figure>

            <div
                className="prose prose-lg dark:prose-invert prose-headings:text-foreground prose-p:text-foreground prose-li:text-foreground prose-strong:text-foreground prose-a:text-primary hover:prose-a:text-primary/80 prose-code:text-foreground prose-pre:bg-muted max-w-none"
                dangerouslySetInnerHTML={{ __html: post.content }}
                itemProp="articleBody"
                role="main"
                aria-label="Article content"
            />

            <footer className="mt-8">
                {auth && auth.user && (
                    <section aria-label="Post reactions">
                        <EmojiReactions post={post} initialReactions={post.likes_summary} userReactions={post.user_reactions} className="mb-4" />
                    </section>
                )}

                {post.comments_enabled && (
                    <section className="mt-8 border-t pt-6" aria-label="Comments section">
                        <Deferred
                            fallback={
                                <div className="flex items-center justify-center" role="status" aria-live="polite" aria-label="Loading comments">
                                    <Spinner />
                                </div>
                            }
                            data="comments"
                        >
                            <BlogComments post={post} comments={comments} commentsPagination={commentsPagination} />
                        </Deferred>
                    </section>
                )}
            </footer>
        </article>
    );
}
