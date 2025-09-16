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
    export type LikeData = {
        emoji: string;
        count: number;
        users: Array<string>;
    };
    export type LikeSummaryData = {
        likesSummary: Array<App.Data.LikeData>;
        userReactions: Array<string>;
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
    export type SubscriptionData = {
        id: number;
        name: string;
        description: string;
        slug: string;
        featuredImageUrl: string | null;
        current: boolean;
        metadata: Array<string, unknown> | null;
        activePrices: Array<App.Data.PriceData>;
        categories: Array<App.Data.ProductCategoryData>;
        policies: Array<App.Data.PolicyData>;
    };
    export type UserData = {
        id: string;
        name: string;
    };
    export type UserSocialData = {
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
    export type OrderStatus =
        | 'pending'
        | 'canceled'
        | 'processing'
        | 'requires_action'
        | 'requires_capture'
        | 'requires_confirmation'
        | 'requires_payment_method'
        | 'succeeded';
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
    export type SupportTicketPriority = 'low' | 'medium' | 'high' | 'critical';
    export type SupportTicketStatus = 'new' | 'open' | 'in_progress' | 'resolved' | 'closed';
}
