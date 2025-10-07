import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import RichEditorContent from '@/components/rich-editor-content';
import { StarRating } from '@/components/star-rating';
import { StoreProductRating } from '@/components/store-product-rating';
import { StoreProductReviewsList } from '@/components/store-product-reviews-list';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { pluralize } from '@/lib/utils';
import { Deferred, router, useForm, usePage } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ProductProps {
    product: App.Data.ProductData;
    reviews: App.Data.CommentData[];
    reviewsPagination: App.Data.PaginatedData;
}

export default function Product({ product: productData, reviews, reviewsPagination }: ProductProps) {
    const { auth } = usePage<App.Data.SharedData>().props;
    const [selectedPriceId, setSelectedPriceId] = useState<number | null>(productData?.defaultPrice?.id || null);
    const [isRatingModalOpen, setIsRatingModalOpen] = useState(false);

    const { data, setData, post, processing, errors } = useForm({
        price_id: selectedPriceId,
        quantity: 1,
    });

    useEffect(() => {
        let newPriceId = null;

        if (productData?.prices && productData.prices.length === 1) {
            newPriceId = productData.prices[0].id;
        } else if (productData?.defaultPrice) {
            newPriceId = productData.defaultPrice.id;
        }

        if (newPriceId && newPriceId !== selectedPriceId) {
            setSelectedPriceId(newPriceId);
            setData('price_id', newPriceId);
        }
    }, [productData?.defaultPrice, productData?.prices, selectedPriceId, setData]);

    const handlePriceChange = (value: string) => {
        const priceId = value ? parseInt(value) : null;
        setSelectedPriceId(priceId);
        setData('price_id', priceId);
    };

    const handleQuantityChange = (value: string) => {
        const quantity = parseInt(value);
        setData('quantity', quantity);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        if (!productData || !data.price_id) return;

        post(
            route('store.products.store', {
                product: productData.slug,
            }),
        );
    };

    return (
        <div className="sm:flex sm:items-baseline sm:justify-between">
            <div className="lg:grid lg:auto-rows-min lg:grid-cols-12 lg:items-stretch lg:gap-x-8">
                <div className="lg:col-span-5 lg:col-start-8">
                    <Heading
                        title={productData.name}
                        description={
                            productData
                                ? (() => {
                                      const selectedPrice = productData.prices?.find((p) => p.id === selectedPriceId) || productData.defaultPrice;
                                      return selectedPrice?.amount
                                          ? `$${(selectedPrice.amount / 100).toFixed(2)} ${selectedPrice.currency}${selectedPrice.interval ? ` / ${selectedPrice.interval}` : ''}`
                                          : 'Price TBD';
                                  })()
                                : '$0.00'
                        }
                    />
                    <div className="-mt-2 flex items-center gap-4">
                        <StarRating rating={productData.averageRating || 0} showValue={true} />
                        <Deferred fallback={<div className="text-sm text-primary">Loading reviews...</div>} data="reviews">
                            <Dialog open={isRatingModalOpen} onOpenChange={setIsRatingModalOpen}>
                                <DialogTrigger asChild>
                                    <button className="text-sm text-primary hover:underline">
                                        See all {productData.reviewsCount || 0} {pluralize('review', productData.reviewsCount || 0)}
                                    </button>
                                </DialogTrigger>
                                <DialogContent className="max-h-[80vh] min-w-[90vh] overflow-y-auto">
                                    <DialogHeader>
                                        <DialogTitle>Product reviews</DialogTitle>
                                        <DialogDescription>View the latest product reviews and ratings.</DialogDescription>
                                    </DialogHeader>
                                    <div className="pt-4">
                                        <StoreProductReviewsList reviews={reviews || []} reviewsPagination={reviewsPagination} />

                                        {auth?.user && (
                                            <div className="border-t border-muted pt-6">
                                                <h3 className="mb-4 text-lg font-medium">Write a review</h3>
                                                <StoreProductRating
                                                    product={productData}
                                                    onRatingAdded={() => {
                                                        setIsRatingModalOpen(false);
                                                        router.reload({ only: ['product'] });
                                                    }}
                                                />
                                            </div>
                                        )}
                                    </div>
                                </DialogContent>
                            </Dialog>
                        </Deferred>
                    </div>
                </div>

                <div className="mt-8 lg:col-span-7 lg:col-start-1 lg:row-span-3 lg:row-start-1 lg:mt-0 lg:flex lg:flex-col">
                    <h2 className="sr-only">Images</h2>

                    <div className="grid grid-cols-1 lg:flex-1 lg:grid-cols-2 lg:gap-8">
                        {productData?.featuredImageUrl ? (
                            <img
                                alt={productData.name}
                                src={productData.featuredImageUrl}
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
                                {productData?.featuredImageUrl ? (
                                    <img
                                        alt={`${productData.name} thumbnail`}
                                        src={productData.featuredImageUrl}
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
                    {productData?.description && (
                        <>
                            <HeadingSmall title="Description" />
                            <RichEditorContent className="mt-2 text-sm text-muted-foreground" content={productData.description} />
                        </>
                    )}

                    {productData && (
                        <div className="mt-8 space-y-4">
                            {productData.prices && productData.prices.length > 0 && (
                                <div className="space-y-2">
                                    <div className="flex items-center gap-4">
                                        <label htmlFor="price" className="text-sm font-medium">
                                            Price:
                                        </label>
                                        <Select value={selectedPriceId?.toString() || ''} onValueChange={handlePriceChange}>
                                            <SelectTrigger className="w-full">
                                                <SelectValue placeholder="Select price" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {productData.prices.map((price) => (
                                                    <SelectItem key={price.id} value={price.id.toString()}>
                                                        {price.name} - ${(price.amount / 100).toFixed(2)} {price.currency}
                                                        {price.interval && ` / ${price.interval}`}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    {errors.price_id && <div className="text-sm text-destructive">{errors.price_id}</div>}
                                </div>
                            )}

                            <div className="space-y-2">
                                <div className="flex items-center gap-4">
                                    <label htmlFor="quantity" className="text-sm font-medium">
                                        Quantity:
                                    </label>
                                    <Select value={data.quantity.toString()} onValueChange={handleQuantityChange}>
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
                                {errors.quantity && <div className="text-sm text-destructive">{errors.quantity}</div>}
                            </div>
                        </div>
                    )}

                    <form onSubmit={handleSubmit}>
                        <Button
                            type="submit"
                            disabled={processing || !productData || !data.price_id}
                            className="mt-8 flex w-full items-center justify-center"
                        >
                            {processing ? 'Adding...' : 'Add to cart'}
                        </Button>
                    </form>

                    <div className="mt-8 border-t border-accent pt-8"></div>

                    <section aria-labelledby="policies-heading">
                        <h2 id="policies-heading" className="sr-only">
                            Our Policies
                        </h2>

                        {/*<dl className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">*/}
                        {/*    {policies.map((policy) => (*/}
                        {/*        <div key={policy.name} className="rounded-lg border border-border bg-accent p-6 text-center">*/}
                        {/*            <dt>*/}
                        {/*                <policy.icon aria-hidden="true" className="mx-auto size-6 shrink-0 text-sidebar-accent-foreground" />*/}
                        {/*                <div className="mt-2">*/}
                        {/*                    <HeadingSmall title={policy.name} description={policy.description} />*/}
                        {/*                </div>*/}
                        {/*            </dt>*/}
                        {/*        </div>*/}
                        {/*    ))}*/}
                        {/*</dl>*/}
                    </section>
                </div>
            </div>
        </div>
    );
}
