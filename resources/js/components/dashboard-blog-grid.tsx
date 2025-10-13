import BlogIndexItem from '@/components/blog-index-item';

interface BlogPostsGridProps {
    posts?: App.Data.PostData[];
}

export default function DashboardBlogGrid({ posts = [] }: BlogPostsGridProps) {
    return (
        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
            {posts.map((post) => (
                <BlogIndexItem key={post.id} post={post} />
            ))}
        </div>
    );
}
