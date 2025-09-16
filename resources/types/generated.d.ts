declare namespace App.Data {
    export type DownloadData = {
        id: string;
        name: string;
        description: string | null;
        file_size: string | null;
        file_type: string | null;
        download_url: string;
        product_name: string | null;
        created_at: string;
    };
    export type PaymentMethodData = {
        id: string | number;
        type: string;
        brand: string | null;
        last4: string | null;
        expMonth: string | number | null;
        expYear: string | number | null;
        holderName: string | null;
        holderEmail: string | null;
        isDefault: boolean;
    };
}
declare namespace App.Enums {
    export type AnnouncementType = 'info' | 'success' | 'warning' | 'error';
    export type OrderStatus =
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
