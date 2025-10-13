import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import StoreCategoriesProductItem from '@/components/store-categories-product-item';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { ShoppingBag } from 'lucide-react';

interface StoreCategoryShowProps {
    category: App.Data.ProductCategoryData;
    products: App.Data.ProductData[];
}

export default function StoreCategoryShow({ category, products }: StoreCategoryShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Store',
            href: route('store.index'),
        },
        {
            title: 'Categories',
            href: route('store.categories.index'),
        },
        {
            title: category.name,
            href: route('store.categories.show', { category: category.slug }),
        },
    ];

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: category.name,
        description: category.description || `Products in ${category.name} category`,
        url: window.location.href,
        breadcrumb: {
            '@type': 'BreadcrumbList',
            itemListElement: breadcrumbs.map((breadcrumb, index) => ({
                '@type': 'ListItem',
                position: index + 1,
                name: breadcrumb.title,
                item: breadcrumb.href,
            })),
        },
        hasPart: products.map((product) => ({
            '@type': 'Product',
            name: product.name,
            description: product.description,
            url: route('store.products.show', { product: product.slug }),
            image: product.featuredImageUrl,
            category: category.name,
            offers: {
                '@type': 'Offer',
                price: product.defaultPrice?.amount ? product.defaultPrice.amount : 0,
                priceCurrency: 'USD',
            },
        })),
        numberOfItems: products.length,
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Store - ${category.name}`}>
                <meta name="description" content={category.description || `Products in ${category.name} category`} />
                <meta property="og:title" content={`${category.name} - Store`} />
                <meta property="og:description" content={category.description || `Products in ${category.name} category`} />
                <meta property="og:type" content="website" />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>
            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <Heading title={category.name} description={category.description || undefined} />

                {products.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                        {products.map((product) => (
                            <StoreCategoriesProductItem key={product.id} product={product} />
                        ))}
                    </div>
                ) : (
                    <EmptyState
                        icon={<ShoppingBag />}
                        title="No products found"
                        description={`No products are currently available in the ${category.name} category.`}
                    />
                )}
            </div>
        </AppLayout>
    );
}
