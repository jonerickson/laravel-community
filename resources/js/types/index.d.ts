import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    isAdmin: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    target?: string;
}

export interface Invoice {
    id: string;
    amount: number;
    status: InvoiceStatus;
}

export type InvoiceStatus = 'draft' | 'open' | 'paid' | 'uncollectible' | 'void';

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Product {
    id: number;
    name: string;
    slug: string;
    description: string;
    type: 'product' | 'subscription';
    featured_image?: string | null;
    featured_image_url?: string | null;
    stripe_product_id?: string | null;
    metadata?: Record<string, never> | null;
    created_at: string;
    updated_at: string;
}

export interface ProductPrice {
    id: number;
    product_id: number;
    name: string;
    description?: string | null;
    amount: number;
    currency: string;
    interval?: string | null;
    interval_count: number;
    is_active: boolean;
    is_default: boolean;
    stripe_price_id?: string | null;
    metadata?: Record<string, never> | null;
    created_at: string;
    updated_at: string;
}

export interface ProductCategory {
    id: number;
    name: string;
    slug: string;
    description?: string;
    imageUrl: string;
    imageAlt?: string;
}

export interface Announcement {
    id: number;
    title: string;
    slug: string;
    content: string;
    type: 'info' | 'success' | 'warning' | 'error';
    is_active: boolean;
    is_dismissible: boolean;
    created_by: number;
    author?: User;
    starts_at?: string | null;
    ends_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface Post {
    id: number;
    type: 'forum' | 'blog';
    title: string;
    slug: string;
    excerpt?: string | null;
    content: string;
    is_published: boolean;
    is_featured: boolean;
    topic_id?: number;
    featured_image?: string | null;
    featured_image_url?: string | null;
    reading_time?: number;
    published_at?: string | null;
    created_by: number;
    author?: User;
    metadata?: Record<string, never> | null;
    comments?: Comment[];
    comments_count?: number;
    created_at: string;
    updated_at: string;
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

export interface Comment {
    id: number;
    commentable_type: string;
    commentable_id: number;
    content: string;
    is_approved: boolean;
    user_id: number;
    parent_id?: number | null;
    user?: User;
    parent?: Comment;
    replies?: Comment[];
    created_at: string;
    updated_at: string;
}

export interface Forum {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    icon?: string | null;
    color: string;
    order: number;
    is_active: boolean;
    topics_count?: number;
    posts_count?: number;
    latest_topics?: Topic[];
    created_at: string;
    updated_at: string;
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
    posts_count: number;
    last_reply_at?: string | null;
    forum?: Forum;
    author?: User;
    last_post?: Post;
    posts?: Post[];
    created_at: string;
    updated_at: string;
}
