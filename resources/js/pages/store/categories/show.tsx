import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import StoreCategoriesProductItem from '@/components/store-categories-product-item';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Folder, ShoppingBag } from 'lucide-react';

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
    ];

    if (category.parent) {
        breadcrumbs.push({
            title: category.parent.name,
            href: route('store.categories.show', { category: category.parent.slug }),
        });
    }

    breadcrumbs.push({
        title: category.name,
        href: route('store.categories.show', { category: category.slug }),
    });

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
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div className="-mb-6">
                    <Heading title={category.name} description={category.description || undefined} />
                </div>

                {category.children && category.children.length > 0 && (
                    <div>
                        <h3 className="mb-4 text-lg font-semibold">Subcategories</h3>
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                            {category.children.map((subcategory) => (
                                <Link key={subcategory.id} href={route('store.categories.show', { category: subcategory.slug })}>
                                    <Card className="transition-colors hover:bg-muted/50">
                                        <CardContent className="p-4">
                                            <div className="flex items-start gap-3">
                                                <div className="flex size-10 flex-shrink-0 items-center justify-center rounded-lg bg-muted">
                                                    {subcategory.image ? (
                                                        <img
                                                            src={subcategory.image.url}
                                                            alt={subcategory.name}
                                                            className="size-10 rounded-lg object-cover"
                                                        />
                                                    ) : (
                                                        <Folder className="size-5" />
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <h4 className="font-medium">{subcategory.name}</h4>
                                                    {subcategory.description && (
                                                        <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">{subcategory.description}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    </div>
                )}

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
