import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

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

export interface ApiResponse<T = unknown> {
    success: boolean;
    message: string;
    data: T;
    meta: {
        timestamp: string;
        version: string;
        [key: string]: unknown;
    };
    errors?: Record<string, string[]> | null;
}

export interface Auth {
    user: User;
    groups: Group[];
    isAdmin: boolean;
    mustVerifyEmail: boolean;
    can: string[];
    roles?: string[];
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface CartResponse {
    cartCount: number;
    cartItems: {
        product_id: number;
        price_id?: number | null;
        name: string;
        slug: string;
        quantity: number;
        product: Product | null;
        selected_price?: ProductPrice | null;
        available_prices?: ProductPrice[];
        added_at: string;
    }[];
}

export interface CheckoutResponse {
    checkout_url: string;
}

export interface Comment {
    id: number;
    commentable_type: string;
    commentable_id: number;
    content: string;
    is_approved: boolean;
    created_by: number;
    parent_id?: number | null;
    rating?: number | null;
    likes_count: number;
    likes_summary: EmojiReaction[];
    user_reaction?: string | null;
    user_reactions: string[];
    user?: User;
    author?: User;
    parent?: Comment;
    replies?: Comment[];
    created_at: string;
    updated_at: string;
}

export interface EmojiReaction {
    emoji: string;
    count: number;
    users: string[];
}

export interface EmojiReactionResponse {
    likes_summary: EmojiReaction[];
    user_reactions: string[];
}

export interface FingerprintTrackingResponse {
    fingerprint_id: string;
    first_seen: string;
    last_seen: string;
}

export interface FlashData {
    scrollToBottom?: boolean;
    message?: string | null;
    messageVariant?: string | null;
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

export interface Group {
    id: number;
    name: string;
    color: string;
}

export interface Image {
    id: number;
    imageable_type: string;
    imageable_id: number;
    path: string;
    url: string;
    created_at: string;
    updated_at: string;
}

export interface Invoice {
    id: string;
    amount_due: number;
    amount_paid: number;
    amount_remaining: number;
    currency: string;
    status: InvoiceStatus;
    customer_id: string;
    subscription_id?: string;
    invoice_pdf?: string;
    hosted_invoice_url?: string;
    created: number;
    due_date?: number;
    paid_at?: number;
    metadata: Record<string, unknown>;
}

export type InvoiceStatus = 'draft' | 'open' | 'paid' | 'uncollectible' | 'void';

export interface Order {
    id: number;
    user_id: number;
    status: OrderStatus;
    amount?: number | null;
    invoice_url?: string | null;
    reference_id?: string | null;
    invoice_number?: string | null;
    external_checkout_id?: string | null;
    external_order_id?: string | null;
    external_payment_id?: string | null;
    external_invoice_id?: string | null;
    created_at: string;
    updated_at: string;
    items?: OrderItem[];
}

export interface OrderItem {
    id: number;
    order_id: number;
    product_id?: number | null;
    price_id?: number | null;
    quantity?: number;
    created_at: string;
    updated_at: string;
    product?: Product | null;
    price?: ProductPrice | null;
}

export type OrderStatus =
    | 'pending'
    | 'canceled'
    | 'processing'
    | 'requires_action'
    | 'requires_capture'
    | 'requires_confirmation'
    | 'requires_payment_method'
    | 'succeeded';

export interface Download {
    id: string;
    name: string;
    description?: string;
    file_size?: string;
    file_type?: string;
    download_url: string;
    product_name?: string;
    created_at: string;
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
    shouldShow?: boolean | ((auth: Auth) => boolean);
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

export interface Policy {
    id: number;
    title: string;
    slug: string;
    description?: string | null;
    content: string;
    version?: string | null;
    is_active: boolean;
    effective_at?: string | null;
    order: number;
    policy_category_id: number;
    category?: PolicyCategory;
    created_by: number;
    author?: User;
    created_at: string;
    updated_at: string;
}

export interface PolicyCategory {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    is_active: boolean;
    order: number;
    active_policies?: Policy[];
    created_by: number;
    author?: User;
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
    is_pinned: boolean;
    comments_enabled: boolean;
    comments_count: number;
    likes_count: number;
    likes_summary: EmojiReaction[];
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
    author: User;
    metadata?: Record<string, never> | null;
    created_at: string;
    updated_at: string;
    comments?: Comment[];
    is_reported?: boolean;
    report_count?: number;
}

export interface Product {
    id: number;
    name: string;
    slug: string;
    description: string;
    type: 'product' | 'subscription';
    is_featured: boolean;
    featured_image?: string | null;
    featured_image_url?: string | null;
    external_product_id?: string | null;
    metadata?: Record<string, never> | null;
    prices?: ProductPrice[];
    default_price?: ProductPrice | null;
    created_at: string;
    updated_at: string;
    image?: string | null;
    price?: number;
    rating?: number;
    average_rating?: number;
    reviews_count?: number;
    category?: ProductCategory | null;
    reviews?: Comment[];
    policies?: Policy[];
}

export interface ProductCategory {
    id: number;
    name: string;
    slug: string;
    description?: string;
    image?: Image | null;
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
    external_price_id?: string | null;
    metadata?: Record<string, never> | null;
    created_at: string;
    updated_at: string;
}

export interface SharedData {
    auth: Auth;
    name: string;
    cartCount?: number;
    flash?: FlashData;
    sidebarOpen: boolean;
    ziggy: Config & { location: string };
    quote?: {
        message: string;
        author: string;
    };
    [key: string]: unknown;
}

export interface SupportTicket {
    id: number;
    subject: string;
    description: string;
    status: 'new' | 'open' | 'in_progress' | 'resolved' | 'closed';
    priority: 'low' | 'medium' | 'high' | 'critical';
    support_ticket_category_id: number;
    category?: SupportTicketCategory;
    assigned_to?: number | null;
    assignedTo?: User | null;
    created_by: number;
    author?: User;
    external_id?: string | null;
    external_url?: string | null;
    last_synced_at?: string | null;
    resolved_at?: string | null;
    closed_at?: string | null;
    created_at: string;
    updated_at: string;
    comments?: Comment[];
    files?: { id: string; name: string; url: string }[];
    is_active: boolean;
}

export interface SupportTicketCategory {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    color?: string | null;
    order: number;
    is_active: boolean;
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
    unique_views_count: number;
    order: number;
    posts_count: number;
    last_reply_at?: string | null;
    is_read_by_user: boolean;
    reads_count: number;
    is_hot: boolean;
    trending_score: number;
    forum?: Forum;
    author: User;
    last_post?: Post;
    posts?: Post[];
    created_at: string;
    updated_at: string;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    signature?: string;
    email_verified_at: string | null;
    groups: Group[];
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface UserSocial {
    id: number;
    user_id: number;
    provider: string;
    provider_id: string;
    provider_name?: string | null;
    provider_email?: string | null;
    provider_avatar?: string | null;
    created_at: string;
    updated_at: string;
}
