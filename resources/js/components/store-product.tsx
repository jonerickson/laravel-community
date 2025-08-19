import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { StarRating } from '@/components/star-rating';
import { StoreProductRating } from '@/components/store-product-rating';
import { StoreProductReviewsList } from '@/components/store-product-reviews-list';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useCartOperations } from '@/hooks/use-cart-operations';
import type { Comment, PaginatedData, Product as ProductType } from '@/types';
import { Deferred } from '@inertiajs/react';
import { CurrencyIcon, GlobeIcon, ImageIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

const policies = [
    { name: 'International delivery', icon: GlobeIcon, description: 'Get your order in 2 years' },
    { name: 'Loyalty rewards', icon: CurrencyIcon, description: "Don't look at other tees" },
];

interface ProductProps {
    product: ProductType;
    reviews: Comment[];
    reviewsPagination: PaginatedData;
}

export default function Product({ product: productData, reviews, reviewsPagination }: ProductProps) {
    const [quantity, setQuantity] = useState(1);
    const [selectedPriceId, setSelectedPriceId] = useState<number | null>(productData?.default_price?.id || null);
    const [isRatingModalOpen, setIsRatingModalOpen] = useState(false);
    const { addItem, loading } = useCartOperations();

    useEffect(() => {
        if (productData?.default_price && !selectedPriceId) {
            setSelectedPriceId(productData.default_price.id);
        }
    }, [selectedPriceId, productData?.default_price]);

    const handleAddToCart = async () => {
        if (!productData) return;

        await addItem(productData.id, selectedPriceId, quantity);
    };

    return (
        <div className="sm:flex sm:items-baseline sm:justify-between">
            <div className="lg:grid lg:auto-rows-min lg:grid-cols-12 lg:items-stretch lg:gap-x-8">
                <div className="lg:col-span-5 lg:col-start-8">
                    <Heading
                        title={productData.name}
                        description={
                            productData
                                ? productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price
                                    ? `$${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.amount} ${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.currency}${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.interval ? ` / ${(productData.prices?.find((p) => p.id === selectedPriceId) || productData.default_price)?.interval}` : ''}`
                                    : 'Price TBD'
                                : '0.00'
                        }
                    />
                    <div className="-mt-2 flex items-center gap-4">
                        <StarRating rating={productData.average_rating || 0} showValue={true} />
                        <Deferred fallback={<div className="text-sm text-primary">Loading reviews...</div>} data="reviews">
                            <Dialog open={isRatingModalOpen} onOpenChange={setIsRatingModalOpen}>
                                <DialogTrigger asChild>
                                    <button className="text-sm text-primary hover:underline">See all {productData.reviews_count || 0} reviews</button>
                                </DialogTrigger>
                                <DialogContent className="max-h-[80vh] min-w-[90vh] overflow-y-auto">
                                    <DialogHeader>
                                        <DialogTitle>Product Reviews</DialogTitle>
                                    </DialogHeader>
                                    <div className="pt-4">
                                        <StoreProductReviewsList reviews={reviews || []} reviewsPagination={reviewsPagination} />

                                        <div className="border-t border-muted pt-6">
                                            <h3 className="mb-4 text-lg font-medium">Write a Review</h3>
                                            <StoreProductRating
                                                product={productData}
                                                onRatingAdded={() => {
                                                    setIsRatingModalOpen(false);
                                                    window.location.reload();
                                                }}
                                            />
                                        </div>
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </Deferred>
                    </div>
                </div>

                <div className="mt-8 lg:col-span-7 lg:col-start-1 lg:row-span-3 lg:row-start-1 lg:mt-0 lg:flex lg:flex-col">
                    <h2 className="sr-only">Images</h2>

                    <div className="grid grid-cols-1 lg:flex-1 lg:grid-cols-2 lg:gap-8">
                        {productData?.featured_image_url ? (
                            <img
                                alt={productData.name}
                                src={productData.featured_image_url}
                                className="col-span-2 row-span-2 h-full w-full rounded-lg object-cover"
                            />
                        ) : (
                            <div className="col-span-2 row-span-2 flex h-full w-full items-center justify-center rounded-lg bg-muted">
                                <ImageIcon className="h-24 w-24 text-muted-foreground" />
                            </div>
                        )}
                    </div>

                    <div className="mt-4">
                        <div className="flex gap-2 overflow-x-auto">
                            <div className="h-16 w-16 flex-shrink-0 rounded border border-border">
                                {productData?.featured_image_url ? (
                                    <img
                                        alt={`${productData.name} thumbnail`}
                                        src={productData.featured_image_url}
                                        className="h-full w-full rounded object-cover"
                                    />
                                ) : (
                                    <div className="flex h-full w-full items-center justify-center rounded bg-muted">
                                        <ImageIcon className="h-6 w-6 text-muted-foreground" />
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mt-8 lg:col-span-5 lg:flex lg:h-full lg:flex-col">
                    <HeadingSmall title="Description" />
                    <p
                        dangerouslySetInnerHTML={{
                            __html: productData?.description,
                        }}
                        className="mt-2 text-sm text-muted-foreground"
                    />

                    {productData && (
                        <div className="mt-8 space-y-4">
                            {productData.prices && productData.prices.length > 0 && (
                                <div className="flex items-center gap-4">
                                    <label htmlFor="price" className="text-sm font-medium">
                                        Price:
                                    </label>
                                    <Select
                                        value={selectedPriceId?.toString() || ''}
                                        onValueChange={(value) => setSelectedPriceId(value ? parseInt(value) : null)}
                                        disabled={productData.prices.length === 1}
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select price" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {productData.prices.map((price) => (
                                                <SelectItem key={price.id} value={price.id.toString()}>
                                                    {price.name} - ${price.amount} {price.currency}
                                                    {price.interval && ` / ${price.interval}`}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="flex items-center gap-4">
                                <label htmlFor="quantity" className="text-sm font-medium">
                                    Quantity:
                                </label>
                                <Select value={quantity.toString()} onValueChange={(value) => setQuantity(parseInt(value))}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Quantity" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((num) => (
                                            <SelectItem key={num} value={num.toString()}>
                                                {num}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    )}

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            handleAddToCart();
                        }}
                    >
                        <Button
                            type="submit"
                            disabled={loading === productData?.id || !productData}
                            className="mt-8 flex w-full items-center justify-center"
                        >
                            {loading === productData?.id ? 'Adding...' : 'Add to cart'}
                        </Button>
                    </form>

                    <div className="mt-8 border-t border-accent pt-8"></div>

                    <section aria-labelledby="policies-heading">
                        <h2 id="policies-heading" className="sr-only">
                            Our Policies
                        </h2>

                        <dl className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            {policies.map((policy) => (
                                <div key={policy.name} className="rounded-lg border border-border bg-accent p-6 text-center">
                                    <dt>
                                        <policy.icon aria-hidden="true" className="mx-auto size-6 shrink-0 text-sidebar-accent-foreground" />
                                        <div className="mt-2">
                                            <HeadingSmall title={policy.name} description={policy.description} />
                                        </div>
                                    </dt>
                                </div>
                            ))}
                        </dl>
                    </section>
                </div>
            </div>
        </div>
    );
}
