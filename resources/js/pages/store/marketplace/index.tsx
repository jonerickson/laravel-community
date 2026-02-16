import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import StoreCategoriesProductItem from '@/components/store-categories-product-item';
import { Pagination } from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';

interface StoreMarketplaceIndexProps {
    products: App.Data.PaginatedData<App.Data.ProductData>;
}

export default function StoreMarketplaceIndex({ products }: StoreMarketplaceIndexProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Store',
            href: route('store.index'),
        },
        {
            title: 'Community products',
            href: route('store.marketplace'),
        },
    ];

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: 'Community products',
        description: 'Browse community-submitted products',
        url: route('store.marketplace'),
        breadcrumb: {
            '@type': 'BreadcrumbList',
            itemListElement: breadcrumbs.map((breadcrumb, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                name: breadcrumb.title,
                item: breadcrumb.href,
            })),
        },
        hasPart: products.data.map((product) => ({
            '@type': 'Product',
            name: product.name,
            description: product.description,
            url: route('store.products.show', { product: product.slug }),
            image: product.featuredImageUrl,
            offers: {
                '@type': 'Offer',
                price: product.defaultPrice?.amount ? product.defaultPrice.amount : 0,
                priceCurrency: 'USD',
            },
        })),
        numberOfItems: products.data.length,
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Store - Community products">
                <meta name="description" content="Browse community-submitted products" />
                <meta property="og:title" content={`Community products - Store - ${siteName}`} />
                <meta property="og:description" content="Browse community-submitted products" />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="-mb-8">
                    <Heading title="Community products" description="Browse community-submitted products" />
                </div>

                <Pagination pagination={products} baseUrl={route('store.marketplace')} entityLabel="product" />

                {products.data.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {products.data.map((product) => (
                            <StoreCategoriesProductItem key={product.id} product={product} />
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        icon={<ShoppingBag />}
                        title="No community products found"
                        description="No community-submitted products are currently available."
                    />
                )}

                <Pagination pagination={products} baseUrl={route('store.marketplace')} entityLabel="product" />
            </div>
        </AppLayout>
    );
}