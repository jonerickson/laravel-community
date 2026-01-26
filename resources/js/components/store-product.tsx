import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { ProductImageGallery } from '@/components/product-image-gallery';
import RichEditorContent from '@/components/rich-editor-content';
import { StarRating } from '@/components/star-rating';
import { StoreProductRatingDialog } from '@/components/store-product-rating-dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Kbd } from '@/components/ui/kbd';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { UserInfo } from '@/components/user-info';
import { cn, currency } from '@/lib/utils';
import { stripCharacters } from '@/utils/truncate';
import { Deferred, useForm } from '@inertiajs/react';
import { AlertTriangle, LoaderCircle, Package } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface ProductProps {
    product: App.Data.ProductData;
    reviews: App.Data.PaginatedData<App.Data.CommentData>;
}

export default function Product({ product: productData, reviews }: ProductProps) {
    const [selectedPriceId, setSelectedPriceId] = useState<number | null>(productData?.defaultPrice?.id || null);
    const [isMac, setIsMac] = useState(false);
    const [descriptionExpanded, setDescriptionExpanded] = useState(false);
    const [descriptionOverflows, setDescriptionOverflows] = useState(false);
    const descriptionRef = useRef<HTMLDivElement>(null);
    const formRef = useRef<HTMLFormElement>(null);
    const { data, setData, post, processing, errors } = useForm({
        price_id: selectedPriceId,
        quantity: 1,
    });

    useEffect(() => {
        setIsMac(navigator.platform?.includes('Mac'));
    }, []);

    useEffect(() => {
        const el = descriptionRef.current;
        if (el) {
            setDescriptionOverflows(el.scrollHeight > el.clientHeight);
        }
    }, [productData.description]);

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

    useEffect(() => {
        const handleKeyDown = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') {
                const submitButton = formRef.current?.querySelector('button[type="submit"]') as HTMLButtonElement | null;
                if (submitButton?.disabled) return;

                e.preventDefault();
                formRef.current?.requestSubmit();
            }
        };

        window.addEventListener('keydown', handleKeyDown);

        return () => window.removeEventListener('keydown', handleKeyDown);
    }, []);

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
                                      return selectedPrice
                                          ? `${currency(selectedPrice.amount)} ${selectedPrice.interval ? ` / ${selectedPrice.interval}` : ''}`
                                          : 'Price TBD';
                                  })()
                                : '$0.00'
                        }
                    />

                    {productData.isMarketplaceProduct && productData.seller && (
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <UserInfo user={productData.seller} showGroups />
                        </div>
                    )}

                    <div className="mt-2 flex items-center gap-4">
                        <StarRating rating={productData.averageRating || 0} showValue={true} />
                        <Deferred fallback={<div className="text-sm text-primary">Loading reviews...</div>} data="reviews">
                            <StoreProductRatingDialog product={productData} reviews={reviews} />
                        </Deferred>
                    </div>

                    {productData.inventoryItem?.trackInventory && (
                        <div className="mt-2 flex items-center gap-2">
                            <Package className="h-4 w-4 text-muted-foreground" />
                            {!productData.inventoryItem.isOutOfStock && productData.inventoryItem.quantityAvailable > 0 ? (
                                <Badge variant="outline" className="border-success text-success">
                                    {productData.inventoryItem.quantityAvailable} in stock
                                </Badge>
                            ) : productData.inventoryItem.allowBackorder ? (
                                <Badge variant="outline" className="border-warning text-warning">
                                    Available on backorder
                                </Badge>
                            ) : (
                                <Badge variant="outline" className="border-destructive text-destructive">
                                    Out of stock
                                </Badge>
                            )}
                        </div>
                    )}
                </div>

                <div className="mt-6 lg:col-span-7 lg:col-start-1 lg:row-span-3 lg:row-start-1 lg:mt-0 lg:flex lg:flex-col">
                    <h2 className="sr-only">Images</h2>
                    <ProductImageGallery product={productData} />
                </div>

                <div className="lg:col-span-5 lg:flex lg:h-full lg:flex-col">
                    {productData?.description && stripCharacters(productData.description).length > 0 && (
                        <div className="mt-6">
                            <HeadingSmall title="Description" />
                            <div className="relative">
                                <div ref={descriptionRef} className={cn(!descriptionExpanded && 'max-h-40 overflow-hidden')}>
                                    <RichEditorContent className="text-sm text-muted-foreground" content={productData.description} />
                                </div>
                                {descriptionOverflows && !descriptionExpanded && (
                                    <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-background to-transparent" />
                                )}
                            </div>
                            {descriptionOverflows && (
                                <button
                                    type="button"
                                    className="mt-2 text-sm font-medium text-primary hover:underline"
                                    onClick={() => setDescriptionExpanded(!descriptionExpanded)}
                                >
                                    {descriptionExpanded ? 'See less' : 'See more'}
                                </button>
                            )}
                        </div>
                    )}

                    {productData.inventoryItem?.trackInventory &&
                        productData.inventoryItem.isOutOfStock &&
                        !productData.inventoryItem.allowBackorder && (
                            <Alert variant="destructive" className="mt-6">
                                <AlertTriangle className="h-4 w-4" />
                                <AlertTitle>Out Of Stock</AlertTitle>
                                <AlertDescription>This product is currently out of stock and unavailable for purchase.</AlertDescription>
                            </Alert>
                        )}

                    {productData && (
                        <div className="mt-6 space-y-4">
                            {productData.prices && productData.prices.length > 0 && (
                                <div className="space-y-2">
                                    <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:gap-4">
                                        <label htmlFor="price" className="text-sm font-medium">
                                            Price:
                                        </label>
                                        <Select
                                            value={selectedPriceId?.toString() || ''}
                                            onValueChange={handlePriceChange}
                                            disabled={
                                                productData.inventoryItem?.trackInventory &&
                                                productData.inventoryItem.isOutOfStock &&
                                                !productData.inventoryItem.allowBackorder
                                            }
                                        >
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
                                                    productData.inventoryItem?.trackInventory &&
                                                    !productData.inventoryItem.isOutOfStock &&
                                                    !productData.inventoryItem.allowBackorder
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

                    <form ref={formRef} onSubmit={handleSubmit}>
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
                            {processing && <LoaderCircle className="animate-spin" />}
                            {processing
                                ? 'Adding...'
                                : productData.inventoryItem?.trackInventory &&
                                    productData.inventoryItem.isOutOfStock &&
                                    !productData.inventoryItem.allowBackorder
                                  ? 'Out of stock'
                                  : 'Add to cart'}
                            {!processing && <Kbd className="bg-primary-foreground/10 text-primary-foreground">{isMac ? '⌘' : 'Ctrl'} ↵</Kbd>}
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
