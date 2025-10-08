import BlogIndexItem from '@/components/blog-index-item';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { Link } from '@inertiajs/react';
import { BookOpen, Rss } from 'lucide-react';

interface BlogPostsGridProps {
    posts?: App.Data.PostData[];
    className?: string;
}

export default function BlogPostsGrid({ posts = [], className }: BlogPostsGridProps) {
    if (posts.length === 0) {
        return (
            <div className={`relative ${className}`}>
                <div className="relative overflow-hidden rounded-xl border border-sidebar-border/70 py-12 dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    <div className="relative flex items-center justify-center">
                        <div className="space-y-3 text-center">
                            <HeadingSmall title="Latest blog posts" description="No blog posts available right now" />
                            <Button asChild variant="outline" size="sm">
                                <Link href={route('blog.index')}>
                                    <BookOpen className="size-4" />
                                    Browse Blog
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className={`space-y-6 ${className}`}>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="flex items-center gap-2 text-lg font-semibold">
                        <Rss className="size-4 text-success" />
                        Latest blog posts
                    </h2>
                    <p className="text-sm text-muted-foreground">Stay updated with our latest articles and insights</p>
                </div>
                <Link href={route('blog.index')} className="text-sm font-medium text-primary hover:underline">
                    View all posts
                </Link>
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                {posts.map((post) => (
                    <BlogIndexItem key={post.id} post={post} />
                ))}
            </div>
        </div>
    );
}
