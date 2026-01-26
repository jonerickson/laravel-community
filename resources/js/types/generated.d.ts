declare namespace App.Data {
    export type AnnouncementData = {
        id: number;
        title: string;
        slug: string;
        content: string;
        type: App.Enums.AnnouncementType;
        isActive: boolean;
        isDismissible: boolean;
        createdBy: number | null;
        author: App.Data.UserData;
        startsAt: string | null;
        endsAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type ApiData = {
        success: boolean;
        message: string | null;
        data: unknown;
        meta: App.Data.ApiMetaData;
        errors: { [key: string]: Array<string> } | null;
    };
    export type ApiMetaData = {
        timestamp: string | null;
        version: string;
        requestId: string;
        traceId: string;
        additional: Array<unknown>;
    };
    export type AuthData = {
        user: App.Data.UserData | null;
        isAdmin: boolean;
        isImpersonating: boolean;
        mustVerifyEmail: boolean;
    };
    export type BalanceData = {
        available: number;
        pending: number;
        currency: string;
        breakdown: Array<string, unknown> | null;
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
        referenceId: string;
        commentableType: string;
        commentableId: number;
        content: string;
        isApproved: boolean;
        createdBy: number | null;
        parentId: number | null;
        rating: number | null;
        likesCount: number;
        likesSummary: Array<App.Data.LikeData>;
        userReaction: string | null;
        userReactions: Array<string>;
        user: App.Data.UserData;
        author: App.Data.UserData;
        parent: App.Data.CommentData | null;
        replies: Array<App.Data.CommentData> | null;
        createdAt: string | null;
        updatedAt: string | null;
        policyPermissions: App.Data.PolicyPermissionData;
    };
    export type ConnectedAccountData = {
        id: string;
        email: string;
        businessName: string | null;
        chargesEnabled: boolean;
        payoutsEnabled: boolean;
        detailsSubmitted: boolean;
        capabilities: Array<string, unknown> | null;
        requirements: Array<string, unknown> | null;
        country: string | null;
        defaultCurrency: string | null;
    };
    export type CustomerData = {
        id: string;
        email: string;
        name: string | null;
        phone: string | null;
        currency: string | null;
        metadata: Array<string, unknown> | null;
    };
    export type DiscountData = {
        id: number;
        code: string;
        type: App.Enums.DiscountType;
        discountType: App.Enums.DiscountValueType;
        value: number;
        currentBalance: number | null;
        maxUses: number | null;
        timesUsed: number;
        minOrderAmount: number | null;
        expiresAt: string | null;
        activatedAt: string | null;
        isExpired: boolean;
        isValid: boolean;
        hasBalance: boolean;
        amountApplied: number | null;
        balanceBefore: number | null;
        balanceAfter: number | null;
        externalDiscountId: string | null;
        externalCouponId: string | null;
        createdAt: string | null;
        updatedAt: string | null;
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
    export type FieldData = {
        id: number;
        name: string;
        label: string;
        type: App.Enums.FieldType;
        description: string | null;
        options: Array<{ value: string; label: string }> | null;
        isRequired: boolean;
        isPublic: boolean;
        order: number;
        value: string | null;
    };
    export type FileData = {
        id: number;
        referenceId: string;
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
        postsCount: number;
        isActive: boolean;
        featuredImage: string | null;
        featuredImageUrl: string | null;
        forums: Array<App.Data.ForumData> | null;
        groups: Array<App.Data.GroupData> | null;
        createdAt: string | null;
        updatedAt: string | null;
        forumPermissions: App.Data.ForumPermissionData;
    };
    export type ForumData = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        categoryId: number | null;
        parentId: number | null;
        rules: string | null;
        icon: string | null;
        color: string;
        order: number;
        isActive: boolean;
        topicsCount: number | null;
        postsCount: number | null;
        isFollowedByUser: boolean | null;
        followersCount: number | null;
        latestTopics: Array<App.Data.TopicData> | null;
        latestTopic: App.Data.TopicData | null;
        category: App.Data.ForumCategoryData | null;
        parent: App.Data.ForumData | null;
        children: Array<App.Data.ForumData> | null;
        groups: Array<App.Data.GroupData> | null;
        createdAt: string | null;
        updatedAt: string | null;
        forumPermissions: App.Data.ForumPermissionData;
    };
    export type ForumPermissionData = {
        canCreate: boolean;
        canRead: boolean;
        canUpdate: boolean;
        canDelete: boolean;
        canModerate: boolean;
        canReply: boolean;
        canReport: boolean;
        canPin: boolean;
        canLock: boolean;
        canMove: boolean;
    };
    export type GroupData = {
        id: number;
        name: string;
        color: string;
        style: App.Enums.GroupStyleType;
        icon: string | null;
    };
    export type GroupStyleData = {
        color: string;
        style: App.Enums.GroupStyleType;
        icon: string | null;
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
    export type InventoryItemData = {
        id: number;
        productId: number;
        sku: string;
        quantityAvailable: number;
        quantityReserved: number;
        quantityDamaged: number;
        quantityOnHand: number;
        reorderPoint: number | null;
        reorderQuantity: number | null;
        warehouseLocation: string | null;
        trackInventory: boolean;
        allowBackorder: boolean;
        isLowStock: boolean;
        isOutOfStock: boolean;
    };
    export type InvoiceData = {
        externalInvoiceId: string;
        amount: number;
        invoiceUrl: string | null;
        invoicePdfUrl: string | null;
        externalOrderId: string | null;
        externalPaymentId: string | null;
        discounts: Array<App.Data.DiscountData> | null;
    };
    export type KnowledgeBaseArticleData = {
        id: number;
        type: App.Enums.KnowledgeBaseArticleType;
        title: string;
        slug: string;
        excerpt: string | null;
        content: string;
        isPublished: boolean;
        categoryId: number | null;
        category: App.Data.KnowledgeBaseCategoryData | null;
        featuredImage: string | null;
        featuredImageUrl: string | null;
        readingTime: number | null;
        publishedAt: string | null;
        createdBy: number | null;
        author: App.Data.UserData;
        metadata: Array<string, unknown> | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type KnowledgeBaseCategoryData = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        icon: string | null;
        color: string | null;
        order: number;
        articlesCount: number;
        isActive: boolean;
        featuredImage: string | null;
        featuredImageUrl: string | null;
        articles: Array<App.Data.KnowledgeBaseArticleData> | null;
        createdAt: string | null;
        updatedAt: string | null;
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
    export type NavigationPageData = {
        id: number;
        title: string;
        slug: string;
        label: string;
        order: number;
        url: string;
    };
    export type OrderData = {
        id: number;
        userId: number;
        status: App.Enums.OrderStatus;
        refundReason: string | null;
        refundNotes: string | null;
        amount: number | null;
        amountSubtotal: number | null;
        amountDue: number | null;
        amountPaid: number | null;
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
        discounts: Array<App.Data.DiscountData>;
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
    export type PageData = {
        id: number;
        title: string;
        slug: string;
        description: string | null;
        htmlContent: string;
        cssContent: string | null;
        jsContent: string | null;
        isPublished: boolean;
        publishedAt: string | null;
        showInNavigation: boolean;
        navigationLabel: string | null;
        navigationOrder: number;
        author: App.Data.UserData;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type PaginatedData<T = unknown> = {
        data: Array<T>;
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
        from: number | null;
        to: number | null;
        links: App.Data.PaginatedLinkData | null;
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
        customer: string | null;
        paymentMethodTypes: Array<string>;
        usage: string;
    };
    export type PayoutData = {
        id: number;
        userId: number;
        amount: number;
        status: App.Enums.PayoutStatus;
        paymentMethod: App.Enums.PayoutDriver | null;
        externalPayoutId: string | null;
        failureReason: string | null;
        notes: string | null;
        processedAt: string | null;
        processedBy: number | null;
        createdAt: string | null;
        updatedAt: string | null;
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
        author: App.Data.UserData;
        category: App.Data.PolicyCategoryData | null;
        effectiveAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type PolicyPermissionData = {
        canCreate: boolean;
        canRead: boolean;
        canUpdate: boolean;
        canDelete: boolean;
    };
    export type PostData = {
        id: number;
        type: App.Enums.PostType;
        title: string;
        slug: string;
        excerpt: string | null;
        content: string;
        isPublished: boolean;
        isApproved: boolean;
        isFeatured: boolean;
        isPinned: boolean;
        commentsEnabled: boolean;
        commentsCount: number;
        likesCount: number;
        likesSummary: Array<App.Data.LikeData>;
        userReaction: string | null;
        userReactions: Array<string>;
        topic: App.Data.TopicData | null;
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
        comments: Array<App.Data.CommentData> | null;
        isReported: boolean | null;
        reportCount: number | null;
        createdAt: string | null;
        updatedAt: string | null;
        policyPermissions: App.Data.PolicyPermissionData;
    };
    export type PriceData = {
        id: number;
        name: string;
        amount: number;
        type: App.Enums.PriceType | null;
        currency: string;
        interval: App.Enums.SubscriptionInterval | null;
        isDefault: boolean;
        isActive: boolean;
        externalPriceId: string | null;
    };
    export type ProductCategoryData = {
        id: number;
        name: string;
        slug: string;
        description: string | null;
        parentId: number | null;
        featuredImage: string | null;
        featuredImageUrl: string | null;
        isVisible: boolean;
        isActive: boolean;
        parent: App.Data.ProductCategoryData | null;
        children: Array<App.Data.ProductCategoryData> | null;
    };
    export type ProductData = {
        id: number;
        referenceId: string;
        name: string;
        slug: string;
        description: string | null;
        type: App.Enums.ProductType;
        order: number;
        taxCode: App.Enums.ProductTaxCode | null;
        isFeatured: boolean;
        isSubscriptionOnly: boolean;
        isMarketplaceProduct: boolean;
        seller: App.Data.UserData | null;
        approvalStatus: App.Enums.ProductApprovalStatus;
        isActive: boolean;
        isVisible: boolean;
        trialDays: number;
        allowPromotionCodes: boolean;
        allowDiscountCodes: boolean;
        featuredImage: string | null;
        featuredImageUrl: string | null;
        images: Array<App.Data.ImageData>;
        externalProductId: string | null;
        metadata: Array<string, unknown> | null;
        prices: Array<App.Data.PriceData>;
        defaultPrice: App.Data.PriceData | null;
        averageRating: number;
        reviewsCount: number;
        categories: Array<App.Data.ProductCategoryData>;
        policies: Array<App.Data.PolicyData>;
        inventoryItem: App.Data.InventoryItemData | null;
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
        referenceId: string;
        name: string;
        avatarUrl: string | null;
    };
    export type SearchResultData = {
        id: number;
        type: string;
        title: string;
        url: string;
        description: string | null;
        excerpt: string | null;
        version: string | null;
        price: string | null;
        forumName: string | null;
        categoryName: string | null;
        authorName: string | null;
        postType: string | null;
        effectiveAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
    };
    export type SharedData = {
        auth: App.Data.AuthData;
        announcements: Array<App.Data.AnnouncementData>;
        navigationPages: Array<App.Data.NavigationPageData>;
        name: string;
        email: string | null;
        phone: string | null;
        address: string | null;
        slogan: string | null;
        logoUrl: string;
        cartCount: number;
        memberCount: number;
        postCount: number;
        discordOnlineCount: number;
        discordCount: number;
        robloxCount: number;
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
        doesNotExpire: boolean;
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
        referenceId: string;
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
        author: App.Data.UserData;
        externalId: string | null;
        externalUrl: string | null;
        lastSyncedAt: string | null;
        resolvedAt: string | null;
        closedAt: string | null;
        createdAt: string | null;
        updatedAt: string | null;
        latestComment: App.Data.CommentData | null;
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
        createdBy: number | null;
        isPinned: boolean;
        isLocked: boolean;
        viewsCount: number;
        order: number;
        postsCount: number;
        isReadByUser: boolean;
        readsCount: number;
        isHot: boolean;
        trendingScore: number;
        isFollowedByUser: boolean | null;
        followersCount: number | null;
        hasReportedContent: boolean;
        hasUnpublishedContent: boolean;
        hasUnapprovedContent: boolean;
        forum: App.Data.ForumData | null;
        author: App.Data.UserData;
        lastPost: App.Data.PostData | null;
        createdAt: string | null;
        updatedAt: string | null;
        policyPermissions: App.Data.PolicyPermissionData;
    };
    export type TransferData = {
        id: string;
        amount: number;
        currency: string;
        destination: string;
        sourceTransaction: string | null;
        metadata: Array<string, unknown> | null;
        reversed: boolean;
        createdAt: string | null;
    };
    export type UserData = {
        id: number;
        referenceId: string | null;
        name: string;
        email: string;
        avatarUrl: string | null;
        signature: string | null;
        emailVerifiedAt: string | null;
        groups: Array<App.Data.GroupData>;
        fields: Array<App.Data.FieldData>;
        displayStyle: App.Data.GroupStyleData | null;
        warningPoints: number;
        activeConsequenceType: App.Enums.WarningConsequenceType | null;
        hasPassword: boolean;
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
    export type BillingReason = 'manual' | 'subscription_create' | 'subscription_cycle' | 'subscription_threshold' | 'subscription_update';
    export type CommissionStatus = 'paid' | 'pending' | 'rejected' | 'cancelled' | 'returned';
    export type DiscountType = 'cancellation' | 'gift_card' | 'promo_code' | 'manual';
    export type DiscountValueType = 'fixed' | 'percentage';
    export type FieldType = 'checkbox' | 'date' | 'datetime' | 'number' | 'radio' | 'rich_text' | 'select' | 'text' | 'textarea';
    export type FileVisibility = 'public' | 'private';
    export type FilterType = 'fingerprint' | 'ip_address' | 'user' | 'string';
    export type GroupStyleType = 'solid' | 'gradient' | 'holographic';
    export type HttpMethod = 'head' | 'get' | 'post' | 'put' | 'patch' | 'delete' | 'options';
    export type HttpStatusCode =
        | '200'
        | '201'
        | '202'
        | '204'
        | '301'
        | '302'
        | '303'
        | '400'
        | '401'
        | '402'
        | '403'
        | '404'
        | '405'
        | '406'
        | '409'
        | '422'
        | '423'
        | '500'
        | '503';
    export type InventoryAlertType = 'low_stock' | 'out_of_stock' | 'reorder';
    export type InventoryReservationStatus = 'active' | 'fulfilled' | 'cancelled' | 'expired';
    export type InventoryTransactionType = 'adjustment' | 'sale' | 'return' | 'damage' | 'restock' | 'reserved' | 'released';
    export type KnowledgeBaseArticleType = 'guide' | 'faq' | 'changelog' | 'troubleshooting' | 'announcement' | 'other';
    export type OrderRefundReason = 'duplicate' | 'fraudulent' | 'requested_by_customer' | 'other';
    export type OrderStatus =
        | 'pending'
        | 'canceled'
        | 'expired'
        | 'processing'
        | 'requires_action'
        | 'requires_capture'
        | 'requires_confirmation'
        | 'requires_payment_method'
        | 'succeeded'
        | 'refunded';
    export type PaymentBehavior = 'allow_incomplete' | 'default_incomplete' | 'error_if_incomplete' | 'pending_if_incomplete';
    export type PayoutDriver = 'stripe';
    export type PayoutStatus = 'pending' | 'completed' | 'failed' | 'cancelled';
    export type PostType = 'blog' | 'forum';
    export type PriceType = 'one_time' | 'recurring';
    export type ProductApprovalStatus = 'pending' | 'approved' | 'rejected' | 'withdrawn';
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
    export type ProrationBehavior = 'create_prorations' | 'always_invoice' | 'none';
    export type PublishableStatus = 'published' | 'draft';
    export type RenderEngine = 'blade' | 'expression_language';
    export type ReportReason = 'spam' | 'harassment' | 'inappropriate_content' | 'abuse' | 'impersonation' | 'false_information' | 'other';
    export type ReportStatus = 'pending' | 'reviewed' | 'approved' | 'rejected';
    export type Role = 'super-admin' | 'moderator' | 'user' | 'guest' | 'support-agent';
    export type SubscriptionInterval = 'month' | 'year';
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
    export type SupportTicketStatus = 'new' | 'open' | 'in_progress' | 'waiting_on_customer' | 'resolved' | 'closed';
    export type WarningConsequenceType = 'none' | 'moderate_content' | 'post_restriction' | 'ban';
}
declare namespace App.Services.Migration {
    export type DependencyType = 'pre' | 'post';
}
