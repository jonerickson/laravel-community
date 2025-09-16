declare namespace App.Data {
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
    export type PaymentMethodData = {
        id: string;
        type: string;
        brand: string | null;
        last4: string | null;
        expMonth: string | null;
        expYear: string | null;
        holderName: string;
        holderEmail: string;
        isDefault: boolean;
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
        category: App.Data.PolicyCategoryData;
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
    export type SubscriptionData = {
        id: number;
        name: string;
        description: string;
        slug: string;
        featuredImageUrl: string | null;
        current: boolean;
        metadata: { [key: string]: any };
        activePrices: Array<App.Data.PriceData>;
        categories: Array<App.Data.ProductCategoryData>;
        policies: Array<App.Data.PolicyData>;
    };
    export type UserData = {
        id: string;
        name: string;
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
