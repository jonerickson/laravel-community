<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductTaxCode: string implements HasLabel
{
    case GeneralTangibleGoods = 'general_tangible_goods';
    case GeneralElectronicallySuppliedServices = 'general_electronically_supplied_services';
    case SoftwareSaasPersonalUse = 'software_saas_personal_use';
    case SoftwareSaasBusinessUse = 'software_saas_business_use';
    case SoftwareSaasElectronicDownloadPersonal = 'software_saas_electronic_download_personal';
    case SoftwareSaasElectronicDownloadBusiness = 'software_saas_electronic_download_business';
    case InfrastructureAsServicePersonal = 'infrastructure_as_service_personal';
    case InfrastructureAsServiceBusiness = 'infrastructure_as_service_business';
    case PlatformAsServicePersonal = 'platform_as_service_personal';
    case PlatformAsServiceBusiness = 'platform_as_service_business';
    case CloudBusinessProcessService = 'cloud_business_process_service';
    case DownloadableSoftwarePersonal = 'downloadable_software_personal';
    case DownloadableSoftwareBusiness = 'downloadable_software_business';
    case CustomSoftwarePersonal = 'custom_software_personal';
    case CustomSoftwareBusiness = 'custom_software_business';
    case VideoGamesDownloaded = 'video_games_downloaded';
    case VideoGamesStreamed = 'video_games_streamed';
    case GeneralServices = 'general_services';
    case WebsiteInformationServicesBusiness = 'website_information_services_business';
    case WebsiteInformationServicesPersonal = 'website_information_services_personal';
    case ElectronicallyDeliveredInformationBusiness = 'electronically_delivered_information_business';
    case ElectronicallyDeliveredInformationPersonal = 'electronically_delivered_information_personal';

    public function getStripeCode(): string
    {
        return match ($this) {
            self::GeneralTangibleGoods => 'txcd_99999999',
            self::GeneralElectronicallySuppliedServices => 'txcd_10000000',
            self::SoftwareSaasPersonalUse => 'txcd_10103000',
            self::SoftwareSaasBusinessUse => 'txcd_10103001',
            self::SoftwareSaasElectronicDownloadPersonal => 'txcd_10103100',
            self::SoftwareSaasElectronicDownloadBusiness => 'txcd_10103101',
            self::InfrastructureAsServicePersonal => 'txcd_10010001',
            self::InfrastructureAsServiceBusiness => 'txcd_10101000',
            self::PlatformAsServicePersonal => 'txcd_10102001',
            self::PlatformAsServiceBusiness => 'txcd_10102000',
            self::CloudBusinessProcessService => 'txcd_10104001',
            self::DownloadableSoftwarePersonal => 'txcd_10202000',
            self::DownloadableSoftwareBusiness => 'txcd_10202003',
            self::CustomSoftwarePersonal => 'txcd_10203000',
            self::CustomSoftwareBusiness => 'txcd_10203001',
            self::VideoGamesDownloaded => 'txcd_10201000',
            self::VideoGamesStreamed => 'txcd_10201003',
            self::GeneralServices => 'txcd_20030000',
            self::WebsiteInformationServicesBusiness => 'txcd_10701400',
            self::WebsiteInformationServicesPersonal => 'txcd_10701401',
            self::ElectronicallyDeliveredInformationBusiness => 'txcd_10701410',
            self::ElectronicallyDeliveredInformationPersonal => 'txcd_10701411',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::GeneralTangibleGoods => 'General - Tangible Goods',
            self::GeneralElectronicallySuppliedServices => 'General - Electronically Supplied Services',
            self::SoftwareSaasPersonalUse => 'Software as a Service (SaaS) - Personal Use',
            self::SoftwareSaasBusinessUse => 'Software as a Service (SaaS) - Business Use',
            self::SoftwareSaasElectronicDownloadPersonal => 'SaaS - Electronic Download - Personal Use',
            self::SoftwareSaasElectronicDownloadBusiness => 'SaaS - Electronic Download - Business Use',
            self::InfrastructureAsServicePersonal => 'Infrastructure as a Service (IaaS) - Personal Use',
            self::InfrastructureAsServiceBusiness => 'Infrastructure as a Service (IaaS) - Business Use',
            self::PlatformAsServicePersonal => 'Platform as a Service (PaaS) - Personal Use',
            self::PlatformAsServiceBusiness => 'Platform as a Service (PaaS) - Business Use',
            self::CloudBusinessProcessService => 'Cloud-based Business Process as a Service',
            self::DownloadableSoftwarePersonal => 'Downloadable Software - Personal Use',
            self::DownloadableSoftwareBusiness => 'Downloadable Software - Business Use',
            self::CustomSoftwarePersonal => 'Custom Software - Personal Use',
            self::CustomSoftwareBusiness => 'Custom Software - Business Use',
            self::VideoGamesDownloaded => 'Video Games - Downloaded',
            self::VideoGamesStreamed => 'Video Games - Streamed',
            self::GeneralServices => 'General - Services',
            self::WebsiteInformationServicesBusiness => 'Website Information Services - Business Use',
            self::WebsiteInformationServicesPersonal => 'Website Information Services - Personal Use',
            self::ElectronicallyDeliveredInformationBusiness => 'Electronically Delivered Information Services - Business Use',
            self::ElectronicallyDeliveredInformationPersonal => 'Electronically Delivered Information Services - Personal Use',
        };
    }
}
