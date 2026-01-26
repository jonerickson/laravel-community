import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import Loading from '@/components/loading';
import StoreCategoriesProductItem from '@/components/store-categories-product-item';
import StoreIndexCategoriesItem from '@/components/store-index-categories-item';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import { Folder, ImageIcon, Star, UserPlus } from 'lucide-react';
import { route } from 'ziggy-js';

interface StoreIndexProps {
    categories: App.Data.ProductCategoryData[];
    featuredProducts: App.Data.ProductData[];
    userProvidedProducts: App.Data.ProductData[];
}

export default function StoreIndex({ categories, featuredProducts, userProvidedProducts }: StoreIndexProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Store',
            href: route('store.index'),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Store">
                <meta name="description" content="Browse our most popular products" />
                <meta property="og:title" content={`Store - ${siteName}`} />
                <meta property="og:description" content="Browse our most popular products" />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto">
                <div>
                    <div className="sm:flex sm:items-baseline sm:justify-between">
                        <Heading title="Shop by category" description="Browse our most popular products" />
                        <Link href={route('store.categories.index')} className="hidden text-sm font-semibold sm:block">
                            Browse all categories
                            <span aria-hidden="true"> &rarr;</span>
                        </Link>
                    </div>

                    <Deferred fallback={<Loading variant="grid" />} data={'categories'}>
                        {categories && categories.length > 0 ? (
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3">
                                {categories.map((category) => (
                                    <StoreIndexCategoriesItem key={category.id} item={category} />
                                ))}
                            </div>
                        ) : (
                            <EmptyState icon={<Folder />} title="No product categories" description="Check back later for more product options." />
                        )}
                    </Deferred>

                    <div className="mt-6 sm:hidden">
                        <Link href={route('store.categories.index')} className="block text-sm font-semibold">
                            Browse all categories
                            <span aria-hidden="true"> &rarr;</span>
                        </Link>
                    </div>
                </div>

                <div>
                    <div className="sm:flex sm:items-baseline sm:justify-between">
                        <Heading title="Featured products" description="Our most popular products" />
                    </div>

                    <Deferred fallback={<Loading variant="masonry" />} data={'featuredProducts'}>
                        {featuredProducts && featuredProducts.length > 0 ? (
                            <div className="grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:grid-rows-2 sm:gap-x-6 lg:gap-8">
                                {featuredProducts.slice(0, 3).map((product, index) => (
                                    <div
                                        key={product.id}
                                        className={cn('group relative aspect-[2/1] overflow-hidden rounded-lg', {
                                            'sm:row-span-2 sm:aspect-square': index === 0,
                                            'sm:aspect-auto': index !== 0,
                                        })}
                                    >
                                        {product.featuredImageUrl ? (
                                            <img
                                                alt={product.name}
                                                src={product.featuredImageUrl}
                                                className="absolute size-full object-cover group-hover:opacity-75"
                                            />
                                        ) : (
                                            <div className="absolute flex size-full items-center justify-center bg-muted group-hover:opacity-75">
                                                <ImageIcon className="h-16 w-16 text-muted-foreground" />
                                            </div>
                                        )}
                                        <div aria-hidden="true" className="absolute inset-0 bg-gradient-to-b from-transparent to-black opacity-50" />
                                        <div className="absolute inset-0 flex items-end p-6">
                                            <div>
                                                <h3 className="font-semibold text-white">
                                                    <Link href={route('store.products.show', { slug: product.slug })}>
                                                        <span className="absolute inset-0" />
                                                        {product.name}
                                                    </Link>
                                                </h3>
                                                <p aria-hidden="true" className="mt-1 text-sm text-white">
                                                    Shop now
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <EmptyState icon={<Star />} title="No featured products" description="Check back later for more product options." />
                        )}
                    </Deferred>
                </div>

                <div>
                    <div className="sm:flex sm:items-baseline sm:justify-between">
                        <Heading title="Community provided" description="Browse community-submitted products" />
                        <Link href={route('store.categories.index')} className="hidden text-sm font-semibold sm:block">
                            Browse all community products
                            <span aria-hidden="true"> &rarr;</span>
                        </Link>
                    </div>

                    <Deferred fallback={<Loading variant="grid" />} data={'userProvidedProducts'}>
                        {userProvidedProducts && userProvidedProducts.length > 0 ? (
                            <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {userProvidedProducts.map((product) => (
                                    <StoreCategoriesProductItem key={product.id} product={product} />
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                icon={<UserPlus />}
                                title="No community submitted products"
                                description="Check back later for more product options."
                            />
                        )}
                    </Deferred>

                    <div className="mt-6 sm:hidden">
                        <Link href={route('store.categories.index')} className="block text-sm font-semibold">
                            Browse all community products
                            <span aria-hidden="true"> &rarr;</span>
                        </Link>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
