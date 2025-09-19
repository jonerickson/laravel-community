import BlogComments from '@/components/blog-comments';
import EmojiReactions from '@/components/emoji-reactions';
import RecentViewers from '@/components/recent-viewers';
import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import { Spinner } from '@/components/ui/spinner';
import { UserInfo } from '@/components/user-info';
import { pluralize } from '@/lib/utils';
import { Post } from '@/types';
import { Deferred } from '@inertiajs/react';
import { Calendar, Clock, Eye, MessageSquare } from 'lucide-react';
import usePermissions from '../hooks/use-permissions';

interface BlogPostProps {
    post: Post;
    comments: App.Data.CommentData[];
    commentsPagination: App.Data.PaginatedData;
    recentViewers: App.Data.RecentViewerData[];
}

export default function BlogPost({ post, comments, commentsPagination, recentViewers }: BlogPostProps) {
    const { can } = usePermissions();
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
                        <Badge aria-label="Featured blog post" variant="secondary">
                            Featured Post
                        </Badge>
                    </div>
                )}

                <h1 className="mb-4 text-4xl font-bold tracking-tight text-foreground lg:text-5xl" itemProp="headline">
                    {post.title}
                </h1>

                {post.excerpt && (
                    <p className="mb-6 max-w-3xl text-lg text-muted-foreground" itemProp="description">
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
                        <Calendar className="size-4" aria-hidden="true" />
                        <time dateTime={post.published_at || post.created_at} itemProp="datePublished" aria-label={`Published on ${formattedDate}`}>
                            {formattedDate}
                        </time>
                    </div>

                    <div className="flex items-center gap-1">
                        <Eye className="size-4" aria-hidden="true" />
                        <span aria-label={`Total views: ${post.views_count} views`}>
                            {post.views_count} {pluralize('view', post.views_count)}
                        </span>
                    </div>

                    {post.comments_enabled && can('view_any_comments') && (
                        <div className="flex items-center gap-1">
                            <MessageSquare className="size-4" aria-hidden="true" />
                            <span aria-label={`Total comments: ${post.comments_count} comments`}>
                                {post.comments_count} {pluralize('comment', post.comments_count)}
                            </span>
                        </div>
                    )}

                    {post.reading_time && (
                        <div className="flex items-center gap-1">
                            <Clock className="size-4" aria-hidden="true" />
                            <span aria-label={`Estimated reading time: ${post.reading_time} minutes`}>{post.reading_time} min read</span>
                        </div>
                    )}
                </div>

                <meta itemProp="dateModified" content={post.updated_at} />
                <meta itemProp="url" content={window.location.href} />
            </header>

            <figure className="mb-8" itemProp="image" itemScope itemType="https://schema.org/ImageObject">
                {post.featured_image_url && (
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
                )}
            </figure>

            <RichEditorContent itemProp="articleBody" role="main" aria-label="Article content" content={post.content} />

            <footer className="mt-4">
                {can('like_posts') && (
                    <section aria-label="Post reactions">
                        <EmojiReactions post={post} initialReactions={post.likes_summary} userReactions={post.user_reactions} className="mb-4" />
                    </section>
                )}

                <Deferred fallback={null} data="recentViewers">
                    <div className="mt-6">
                        <RecentViewers viewers={recentViewers} />
                    </div>
                </Deferred>

                {post.comments_enabled && can('view_any_comments') && (
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
