import BlogComments from '@/components/blog-comments';
import EmojiReactions from '@/components/emoji-reactions';
import RecentViewers from '@/components/recent-viewers';
import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import { UserInfo } from '@/components/user-info';
import WidgetLoading from '@/components/widget-loading';
import { pluralize } from '@/lib/utils';
import { Deferred } from '@inertiajs/react';
import { Calendar, Clock, Eye, MessageSquare } from 'lucide-react';
import usePermissions from '../hooks/use-permissions';

interface BlogPostProps {
    post: App.Data.PostData;
    comments: App.Data.PaginatedData<App.Data.CommentData>;
    recentViewers: App.Data.RecentViewerData[];
}

export default function BlogPost({ post, comments, recentViewers }: BlogPostProps) {
    const { can } = usePermissions();
    const publishedDate = new Date(post.publishedAt || post.createdAt || new Date());
    const formattedDate = publishedDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    return (
        <article className="mx-auto max-w-4xl" itemScope itemType="https://schema.org/BlogPosting">
            <header className="mb-8">
                {post.isFeatured && (
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
                        <time
                            dateTime={post.publishedAt || post.createdAt || undefined}
                            itemProp="datePublished"
                            aria-label={`Published on ${formattedDate}`}
                        >
                            {formattedDate}
                        </time>
                    </div>

                    <div className="flex items-center gap-1">
                        <Eye className="size-4" aria-hidden="true" />
                        <span aria-label={`Total views: ${post.viewsCount} views`}>
                            {post.viewsCount} {pluralize('view', post.viewsCount)}
                        </span>
                    </div>

                    {post.commentsEnabled && (
                        <div className="flex items-center gap-1">
                            <MessageSquare className="size-4" aria-hidden="true" />
                            <span aria-label={`Total comments: ${post.commentsCount} comments`}>
                                {post.commentsCount} {pluralize('comment', post.commentsCount)}
                            </span>
                        </div>
                    )}

                    {post.readingTime && (
                        <div className="flex items-center gap-1">
                            <Clock className="size-4" aria-hidden="true" />
                            <span aria-label={`Estimated reading time: ${post.readingTime} minutes`}>{post.readingTime} min read</span>
                        </div>
                    )}
                </div>

                <meta itemProp="dateModified" content={post.updatedAt || undefined} />
                <meta itemProp="url" content={window.location.href} />
            </header>

            <figure className="mb-8" itemProp="image" itemScope itemType="https://schema.org/ImageObject">
                {post.featuredImageUrl && (
                    <>
                        <img
                            src={post.featuredImageUrl}
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
                        <EmojiReactions post={post} initialReactions={post.likesSummary} userReactions={post.userReactions} className="mb-4" />
                    </section>
                )}

                <Deferred fallback={<WidgetLoading />} data="recentViewers">
                    <div className="mt-6">
                        <RecentViewers viewers={recentViewers} />
                    </div>
                </Deferred>

                {post.commentsEnabled && (
                    <section className="mt-8 border-t pt-6" aria-label="Comments section">
                        <BlogComments post={post} comments={comments} />
                    </section>
                )}
            </footer>
        </article>
    );
}
