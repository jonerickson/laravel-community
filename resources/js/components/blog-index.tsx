import BlogIndexItem from '@/components/blog-index-item';
import Heading from '@/components/heading';
import { Post } from '@/types';

interface BlogIndexProps {
    posts: Post[];
}

export default function BlogIndex({ posts }: BlogIndexProps) {
    return (
        <div>
            <div>
                <div className="sm:flex sm:items-baseline sm:justify-between">
                    <Heading title="Blog" description="Browse our latest blog posts and articles" />
                </div>
                <div className="mx-auto grid max-w-2xl grid-cols-1 gap-8 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                    {posts.map((post) => (
                        <BlogIndexItem key={post.id} post={post} />
                    ))}
                </div>
            </div>
        </div>
    );
}
