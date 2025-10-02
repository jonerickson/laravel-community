declare namespace App.Data {
    export type AnnouncementData = {
        id: number;
        title: string;
        slug: string;
        content: string;
        type: App.Enums.AnnouncementType;
        isActive: boolean;
        isDismissible: boolean;
        createdBy: number;
        author: App.Data.UserData | null;
        startsAt: string | null;
        endsAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type ApiData = {
        success: boolean;
        message: string;
        data: unknown;
        meta: App.Data.ApiMetaData;
        errors: { [key: string]: Array<string> } | null;
    };
    export type ApiMetaData = {
        timestamp: string | null;
        version: string;
        additional: Array<unknown>;
    };
    export type AuthData = {
        user: App.Data.UserData | null;
        isAdmin: boolean;
        mustVerifyEmail: boolean;
        can: { [key: string]: boolean };
        roles: Array<string>;
    };
    export type CartData = {
        cartCount: number;
        cartItems: Array<App.Data.CartItemData>;
    };
    export type CartItemData = {
        productId: number;
        priceId: number | null;
        name: string;
        slug: string;
        quantity: number;
        product: App.Data.ProductData | null;
        selectedPrice: App.Data.PriceData | null;
        availablePrices: Array<App.Data.PriceData>;
        addedAt: string | null;
    };
    export type CheckoutData = {
        checkoutUrl: string;
    };
    export type CommentData = {
        id: number;
        commentableType: string;
        commentableId: number;
        content: string;
        isApproved: boolean;
        createdBy: number;
        parentId: number | null;
        rating: number | null;
        likesCount: number;
        likesSummary: Array<App.Data.LikeData>;
        userReaction: string | null;
        userReactions: Array<string>;
        user: App.Data.UserData | null;
        author: App.Data.UserData | null;
        parent: App.Data.CommentData | null;
        replies: Array<App.Data.CommentData> | null;
        createdAt: string | null;
        updatedAt: string | null;
        permissions: App.Data.PermissionData;
    };
    export type DownloadData = {
        id: string;
        name: string;
        description: string | null;
        fileSize: string | null;
        fileType: string | null;
        downloadUrl: string;
        productName: string | null;
        createdAt: string;
    };
    export type FileData = {
        id: string;
        name: string;
        url: string;
        size: number | null;
        mimeType: string | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type FingerprintData = {
        fingerprintId: string;
        firstSeen: string | null;
        lastSeen: string | null;
    };
    export type FlashData = {
        scrollToBottom: boolean | null;
        message: string | null;
        messageVariant: string | null;
    };
    export type ForumCategoryData = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        icon: string | null;
        color: string;
        order: number;
        isActive: boolean;
        forums: Array<App.Data.ForumData> | null;
        image: App.Data.ImageData | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type ForumData = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        categoryId: number | null;
        rules: string | null;
        icon: string | null;
        color: string;
        order: number;
        isActive: boolean;
        topicsCount: number | null;
        postsCount: number | null;
        latestTopics: Array<App.Data.TopicData> | null;
        category: App.Data.ForumCategoryData;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type GroupData = {
        id: number;
        name: string;
        color: string;
    };
    export type ImageData = {
        id: number;
        imageableType: string;
        imageableId: number;
        path: string;
        url: string;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type InvoiceData = {
        externalInvoiceId: string;
        amount: number;
        invoiceUrl: string | null;
        invoicePdfUrl: string | null;
        externalPaymentId: string | null;
    };
    export type LikeData = {
        emoji: string;
        count: number;
        users: Array<string>;
    };
    export type LikeSummaryData = {
        likesSummary: Array<App.Data.LikeData>;
        userReactions: Array<string>;
    };
    export type OrderData = {
        id: number;
        userId: number;
        status: App.Enums.OrderStatus;
        refundReason: string | null;
        refundNotes: string | null;
        amount: number | null;
        isOneTime: boolean;
        isRecurring: boolean;
        checkoutUrl: string | null;
        invoiceUrl: string | null;
        referenceId: string | null;
        invoiceNumber: string | null;
        externalCheckoutId: string | null;
        externalOrderId: string | null;
        externalPaymentId: string | null;
        externalInvoiceId: string | null;
        items: Array<App.Data.OrderItemData>;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type OrderItemData = {
        id: number;
        orderId: number;
        name: string | null;
        productId: number | null;
        priceId: number | null;
        quantity: number;
        amount: number | null;
        isOneTime: boolean;
        isRecurring: boolean;
        product: App.Data.ProductData | null;
        price: App.Data.PriceData | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type PaginatedData = {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
        from: number | null;
        to: number | null;
        links: App.Data.PaginatedLinkData;
    };
    export type PaginatedLinkData = {
        first: string | null;
        last: string | null;
        next: string | null;
        prev: string | null;
    };
    export type PaymentMethodData = {
        id: string;
        type: string;
        brand: string | null;
        last4: string | null;
        expMonth: string | null;
        expYear: string | null;
        holderName: string | null;
        holderEmail: string | null;
        isDefault: boolean;
    };
    export type PaymentSetupIntentData = {
        id: string;
        clientSecret: string;
        status: string;
        customer: string;
        paymentMethodTypes: Array<string>;
        usage: string;
    };
    export type PermissionData = {
        canUpdate: boolean;
        canDelete: boolean;
    };
    export type PolicyCategoryData = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        activePolicies: Array<App.Data.PolicyData>;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type PolicyData = {
        id: number;
        title: string;
        slug: string;
        version: string | null;
        description: string | null;
        content: string;
        isActive: boolean;
        author: App.Data.UserData | null;
        category: App.Data.PolicyCategoryData | null;
        effectiveAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type PostData = {
        id: number;
        type: App.Enums.PostType;
        title: string;
        slug: string;
        excerpt: string | null;
        content: string;
        isPublished: boolean;
        isFeatured: boolean;
        isPinned: boolean;
        commentsEnabled: boolean;
        commentsCount: number;
        likesCount: number;
        likesSummary: Array<App.Data.LikeData>;
        userReaction: string | null;
        userReactions: Array<string>;
        topicId: number | null;
        featuredImage: string | null;
        featuredImageUrl: string | null;
        readingTime: number | null;
        publishedAt: string | null;
        createdBy: number;
        viewsCount: number;
        isReadByUser: boolean;
        readsCount: number;
        author: App.Data.UserData;
        metadata: Array<string, unknown> | null;
        createdAt: string | null;
        updatedAt: string | null;
        comments: Array<App.Data.CommentData> | null;
        isReported: boolean | null;
        reportCount: number | null;
        permissions: App.Data.PermissionData;
    };
    export type PriceData = {
        id: number;
        name: string;
        amount: number;
        currency: string;
        interval: App.Enums.SubscriptionInterval;
        isDefault: boolean;
        isActive: boolean;
    };
    export type ProductCategoryData = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        image: App.Data.ImageData | null;
    };
    export type ProductData = {
        id: number;
        name: string;
        slug: string;
        description: string;
        type: App.Enums.ProductType;
        taxCode: App.Enums.ProductTaxCode | null;
        isFeatured: boolean;
        isSubscriptionOnly: boolean;
        trialDays: number;
        allowPromotionCodes: boolean;
        featuredImage: string | null;
        featuredImageUrl: string | null;
        externalProductId: string | null;
        metadata: Array<string, unknown> | null;
        prices: Array<App.Data.PriceData>;
        defaultPrice: App.Data.PriceData | null;
        averageRating: number | null;
        reviewsCount: number;
        categories: Array<App.Data.ProductCategoryData>;
        policies: Array<App.Data.PolicyData>;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type ReadData = {
        markedAsRead: boolean;
        isReadByUser: boolean;
        type: string;
        id: number;
    };
    export type RecentViewerData = {
        user: App.Data.RecentViewerUserData;
        viewedAt: string;
    };
    export type RecentViewerUserData = {
        id: number;
        name: string;
        avatarUrl: string | null;
    };
    export type SharedData = {
        auth: App.Data.AuthData;
        name: string;
        cartCount: number | null;
        flash: App.Data.FlashData | null;
        sidebarOpen: boolean;
        ziggy: Config & { location: string };
    };
    export type SubscriptionData = {
        name: string;
        user: App.Data.UserData | null;
        status: App.Enums.SubscriptionStatus | null;
        trialEndsAt: string | null;
        endsAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
        product: App.Data.ProductData | null;
        price: App.Data.PriceData | null;
        externalSubscriptionId: string | null;
        externalProductId: string | null;
        externalPriceId: string | null;
        quantity: number | null;
    };
    export type SupportTicketCategoryData = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        color: string | null;
        order: number;
        isActive: boolean;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type SupportTicketData = {
        id: number;
        subject: string;
        description: string;
        status: App.Enums.SupportTicketStatus;
        priority: App.Enums.SupportTicketPriority;
        supportTicketCategoryId: number;
        order: App.Data.OrderData | null;
        category: App.Data.SupportTicketCategoryData | null;
        assignedTo: number | null;
        assignedToUser: App.Data.UserData | null;
        createdBy: number;
        author: App.Data.UserData | null;
        externalId: string | null;
        externalUrl: string | null;
        lastSyncedAt: string | null;
        resolvedAt: string | null;
        closedAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
        comments: Array<App.Data.CommentData>;
        files: Array<App.Data.FileData>;
        isActive: boolean;
    };
    export type TopicData = {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        forumId: number;
        createdBy: number;
        isPinned: boolean;
        isLocked: boolean;
        viewsCount: number;
        uniqueViewsCount: number;
        order: number;
        postsCount: number;
        lastReplyAt: string | null;
        isReadByUser: boolean;
        readsCount: number;
        isHot: boolean;
        trendingScore: number;
        forum: App.Data.ForumData | null;
        author: App.Data.UserData;
        lastPost: App.Data.PostData | null;
        posts: Array<App.Data.PostData> | null;
        createdAt: string | null;
        updatedAt: string | null;
        permissions: App.Data.PermissionData;
    };
    export type UserData = {
        id: number;
        name: string;
        email: string;
        avatarUrl: string | null;
        signature: string | null;
        emailVerifiedAt: string | null;
        groups: Array<App.Data.GroupData>;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type UserIntegrationData = {
        id: number;
        userId: number;
        provider: string;
        providerId: string;
        providerName: string | null;
        providerEmail: string | null;
        providerAvatar: string | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
}
declare namespace App.Enums {
    export type AnnouncementType = 'info' | 'success' | 'warning' | 'error';
    export type OrderRefundReason = 'duplicate' | 'fraudulent' | 'requested_by_customer' | 'other';
    export type OrderStatus =
        | 'pending'
        | 'canceled'
        | 'processing'
        | 'requires_action'
        | 'requires_capture'
        | 'requires_confirmation'
        | 'requires_payment_method'
        | 'succeeded'
        | 'refunded';
    export type PostType = 'blog' | 'forum';
    export type ProductTaxCode =
        | 'general_tangible_goods'
        | 'general_electronically_supplied_services'
        | 'software_saas_personal_use'
        | 'software_saas_business_use'
        | 'software_saas_electronic_download_personal'
        | 'software_saas_electronic_download_business'
        | 'infrastructure_as_service_personal'
        | 'infrastructure_as_service_business'
        | 'platform_as_service_personal'
        | 'platform_as_service_business'
        | 'cloud_business_process_service'
        | 'downloadable_software_personal'
        | 'downloadable_software_business'
        | 'custom_software_personal'
        | 'custom_software_business'
        | 'video_games_downloaded'
        | 'video_games_streamed'
        | 'general_services'
        | 'website_information_services_business'
        | 'website_information_services_personal'
        | 'electronically_delivered_information_business'
        | 'electronically_delivered_information_personal';
    export type ProductType = 'product' | 'subscription';
    export type PublishableStatus = 'published' | 'draft';
    export type ReportReason = 'spam' | 'harassment' | 'inappropriate_content' | 'abuse' | 'impersonation' | 'false_information' | 'other';
    export type ReportStatus = 'pending' | 'reviewed' | 'approved' | 'rejected';
    export type SubscriptionInterval = 'day' | 'week' | 'month' | 'year';
    export type SubscriptionStatus =
        | 'active'
        | 'pending'
        | 'canceled'
        | 'refunded'
        | 'grade_period'
        | 'trialing'
        | 'past_due'
        | 'unpaid'
        | 'incomplete'
        | 'incomplete_expired';
    export type SupportTicketPriority = 'low' | 'medium' | 'high' | 'critical';
    export type SupportTicketStatus = 'new' | 'open' | 'in_progress' | 'resolved' | 'closed';
}
