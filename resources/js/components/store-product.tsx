import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import RichEditorContent from '@/components/rich-editor-content';
import { StarRating } from '@/components/star-rating';
import { StoreProductRatingDialog } from '@/components/store-product-rating-dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { currency } from '@/lib/utils';
import { Deferred, useForm } from '@inertiajs/react';
import { AlertTriangle, ImageIcon, Package } from 'lucide-react';
import { useEffect, useState } from 'react';

interface ProductProps {
    product: App.Data.ProductData;
    reviews: App.Data.PaginatedData<App.Data.CommentData>;
}

export default function Product({ product: productData, reviews }: ProductProps) {
    const [selectedPriceId, setSelectedPriceId] = useState<number | null>(productData?.defaultPrice?.id || null);

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
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <div className="sm:flex sm:items-baseline sm:justify-between">
            <div className="w-full lg:grid lg:auto-rows-min lg:grid-cols-12 lg:items-stretch lg:gap-x-8">
                <div className="lg:col-span-5 lg:col-start-8">
                    <Heading
                        title={productData.name}
                        description={
                            productData
                                ? (() => {
                                      const selectedPrice = productData.prices?.find((p) => p.id === selectedPriceId) || productData.defaultPrice;
                                      return selectedPrice?.amount
                                          ? `${currency(selectedPrice.amount)} ${selectedPrice.interval ? ` / ${selectedPrice.interval}` : ''}`
                                          : 'Price TBD';
                                  })()
                                : '$0.00'
                        }
                    />
                    <div className="flex items-center gap-4">
                        <StarRating rating={productData.averageRating || 0} showValue={true} />
                        <Deferred fallback={<div className="text-sm text-primary">Loading reviews...</div>} data="reviews">
                            <StoreProductRatingDialog product={productData} reviews={reviews} />
                        </Deferred>
                    </div>

                    {productData.inventoryItem?.trackInventory && (
                        <div className="mt-4 flex items-center gap-2">
                            <Package className="h-4 w-4 text-muted-foreground" />
                            {!productData.inventoryItem.isOutOfStock ? (
                                <Badge variant="outline" className="border-green-500 text-green-700 dark:text-green-400">
                                    {productData.inventoryItem.quantityAvailable} in stock
                                </Badge>
                            ) : productData.inventoryItem.allowBackorder ? (
                                <Badge variant="outline" className="border-yellow-500 text-yellow-700 dark:text-yellow-400">
                                    Available on backorder
                                </Badge>
                            ) : (
                                <Badge variant="outline" className="border-red-500 text-red-700 dark:text-red-400">
                                    Out of stock
                                </Badge>
                            )}
                        </div>
                    )}
                </div>

                <div className="mt-6 lg:col-span-7 lg:col-start-1 lg:row-span-3 lg:row-start-1 lg:mt-0 lg:flex lg:flex-col">
                    <h2 className="sr-only">Images</h2>

                    <div className="relative grid grid-cols-1 lg:flex-1 lg:grid-cols-2 lg:gap-8">
                        {productData?.featuredImageUrl ? (
                            <img
                                alt={productData.name}
                                src={productData.featuredImageUrl}
                                className="col-span-2 row-span-2 h-full w-full rounded-lg object-cover"
                            />
                        ) : (
                            <div className="col-span-2 row-span-2 flex h-full w-full items-center justify-center rounded-lg bg-muted py-12">
                                <ImageIcon className="h-24 w-24 text-muted-foreground" />
                            </div>
                        )}
                    </div>

                    <div className="mt-4">
                        <div className="flex gap-2 overflow-x-auto">
                            <div className="relative aspect-square h-16 w-16 flex-shrink-0 rounded border border-border">
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

                <div className="lg:col-span-5 lg:flex lg:h-full lg:flex-col">
                    {productData?.description && (
                        <div className="mt-6">
                            <HeadingSmall title="Description" />
                            <RichEditorContent className="text-sm text-muted-foreground" content={productData.description} />
                        </div>
                    )}

                    {productData && (
                        <div className="mt-8 space-y-4">
                            {productData.prices && productData.prices.length > 0 && (
                                <div className="space-y-2">
                                    <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-4">
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
                                                        {price.name} - {currency(price.amount)}
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
                                <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-4">
                                    <label htmlFor="quantity" className="text-sm font-medium">
                                        Quantity:
                                    </label>
                                    <Select
                                        value={data.quantity.toString()}
                                        onValueChange={handleQuantityChange}
                                        disabled={
                                            productData.inventoryItem?.trackInventory &&
                                            productData.inventoryItem.isOutOfStock &&
                                            !productData.inventoryItem.allowBackorder
                                        }
                                    >
                                        <SelectTrigger className="lg:w-[180px]">
                                            <SelectValue placeholder="Quantity" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {(() => {
                                                const maxQuantity =
                                                    productData.inventoryItem?.trackInventory && !productData.inventoryItem.isOutOfStock
                                                        ? Math.min(10, productData.inventoryItem.quantityAvailable)
                                                        : 10;
                                                return Array.from({ length: maxQuantity }, (_, i) => i + 1).map((num) => (
                                                    <SelectItem key={num} value={num.toString()}>
                                                        {num}
                                                    </SelectItem>
                                                ));
                                            })()}
                                        </SelectContent>
                                    </Select>
                                </div>
                                {errors.quantity && <div className="text-sm text-destructive">{errors.quantity}</div>}
                            </div>
                        </div>
                    )}

                    {productData.inventoryItem?.trackInventory &&
                        productData.inventoryItem.isOutOfStock &&
                        !productData.inventoryItem.allowBackorder && (
                            <Alert variant="destructive" className="mt-6">
                                <AlertTriangle className="h-4 w-4" />
                                <AlertDescription>This product is currently out of stock and unavailable for purchase.</AlertDescription>
                            </Alert>
                        )}

                    <form onSubmit={handleSubmit}>
                        <Button
                            type="submit"
                            disabled={
                                processing ||
                                !productData ||
                                !data.price_id ||
                                (productData.inventoryItem?.trackInventory &&
                                    productData.inventoryItem.isOutOfStock &&
                                    !productData.inventoryItem.allowBackorder)
                            }
                            className="mt-8 flex w-full items-center justify-center"
                        >
                            {processing
                                ? 'Adding...'
                                : productData.inventoryItem?.trackInventory &&
                                    productData.inventoryItem.isOutOfStock &&
                                    !productData.inventoryItem.allowBackorder
                                  ? 'Out of stock'
                                  : 'Add to cart'}
                        </Button>
                    </form>

                    <div className="mt-8 border-t border-accent pt-8"></div>

                    <section aria-labelledby="policies-heading">
                        <h2 id="policies-heading" className="sr-only">
                            Our Policies
                        </h2>
                    </section>
                </div>
            </div>
        </div>
    );
}
