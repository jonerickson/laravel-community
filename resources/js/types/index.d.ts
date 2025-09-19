import { LucideIcon } from 'lucide-react';

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface Forum {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    category_id?: number | null;
    rules?: string | null;
    icon?: string | null;
    color: string;
    order: number;
    is_active: boolean;
    topics_count?: number;
    posts_count?: number;
    latest_topics?: Topic[];
    category: ForumCategory;
    created_at: string;
    updated_at: string;
}

export interface ForumCategory {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    icon?: string | null;
    color: string;
    order: number;
    is_active: boolean;
    forums?: Forum[];
    image?: Image | null;
    created_at: string;
    updated_at: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string | (() => string);
    icon?: LucideIcon | null;
    isActive?: boolean;
    target?: string;
    shouldShow?: boolean | ((auth: App.Data.AuthData) => boolean);
}

export interface PaginatedData {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
}

export interface Post {
    id: number;
    type: PostType;
    title: string;
    slug: string;
    excerpt?: string | null;
    content: string;
    is_published: boolean;
    is_featured: boolean;
    is_pinned: boolean;
    comments_enabled: boolean;
    comments_count: number;
    likes_count: number;
    likes_summary: App.Data.LikeData[];
    user_reaction?: string | null;
    user_reactions: string[];
    topic_id?: number;
    featured_image?: string | null;
    featured_image_url?: string | null;
    reading_time?: number;
    published_at?: string | null;
    created_by: number;
    views_count: number;
    is_read_by_user: boolean;
    reads_count: number;
    author: App.Data.UserData;
    metadata?: Record<string, never> | null;
    created_at: string;
    updated_at: string;
    comments?: App.Data.CommentData[];
    is_reported?: boolean;
    report_count?: number;
}

export interface Topic {
    id: number;
    title: string;
    slug: string;
    description?: string | null;
    forum_id: number;
    created_by: number;
    is_pinned: boolean;
    is_locked: boolean;
    views_count: number;
    unique_views_count: number;
    order: number;
    posts_count: number;
    last_reply_at?: string | null;
    is_read_by_user: boolean;
    reads_count: number;
    is_hot: boolean;
    trending_score: number;
    forum?: Forum;
    author: App.Data.UserData;
    last_post?: Post;
    posts?: Post[];
    created_at: string;
    updated_at: string;
}
