import Product from '@/components/store-product';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { stripCharacters } from '@/utils/truncate';
import { Head, usePage } from '@inertiajs/react';

interface ProductPageProps {
    product: App.Data.ProductData;
    reviews: App.Data.PaginatedData<App.Data.CommentData>;
}

export default function StoreProductShow({ product, reviews }: ProductPageProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Store',
            href: route('store.index'),
        },
        {
            title: product.name,
            href: route('store.products.show', { product: product.slug }),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${product.name} - Store`}>
                <meta name="description" content={stripCharacters(product.description || '')} />
                <meta property="og:title" content={`${product.name} - Store - ${siteName}`} />
                <meta property="og:description" content={stripCharacters(product.description || '')} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="lg:py-2">
                <Product product={product} reviews={reviews} />
            </div>
        </AppLayout>
    );
}
