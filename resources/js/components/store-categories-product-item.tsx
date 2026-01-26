import HeadingSmall from '@/components/heading-small';
import { StarRating } from '@/components/star-rating';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { UserInfo } from '@/components/user-info';
import { useCartOperations } from '@/hooks/use-cart-operations';
import { getPriceDisplay } from '@/utils/price-display';
import { stripCharacters, truncate } from '@/utils/truncate';
import { Link, router } from '@inertiajs/react';
import { ImageIcon, LoaderCircle } from 'lucide-react';

export default function StoreCategoriesProductItem({ product }: { product: App.Data.ProductData }) {
    const { addItem, loading } = useCartOperations();

    const handleAddToCart = async () => {
        if (!product.defaultPrice) {
            router.visit(route('store.products.show', { product: product.slug }));
            return;
        }

        await addItem(product.id, product.defaultPrice.id, 1);
    };

    return (
        <div key={product.id} className="group relative flex flex-col">
            {product.featuredImageUrl ? (
                <img alt={product.name} src={product.featuredImageUrl} className="aspect-square rounded-lg object-cover" />
            ) : (
                <div className="flex aspect-square items-center justify-center rounded-lg bg-muted">
                    <ImageIcon className="size-12 text-muted-foreground" />
                </div>
            )}
            <div className="flex flex-1 flex-col gap-2 pt-4">
                <div className="flex-1 space-y-2">
                    {(product.isFeatured || product.isMarketplaceProduct || product.inventoryItem?.isLowStock) && (
                        <div className="flex flex-wrap items-center gap-2">
                            {product.isFeatured && (
                                <Badge variant="default" className="bg-info text-xs text-info-foreground">
                                    Featured
                                </Badge>
                            )}
                            {product.inventoryItem && product.inventoryItem.isLowStock && <Badge variant="warning">Low Stock</Badge>}
                            {product.isMarketplaceProduct && <Badge variant="secondary">Community Provided</Badge>}
                        </div>
                    )}
                    <HeadingSmall title={product.name} description={truncate(stripCharacters(product.description || ''))} />
                    {product.isMarketplaceProduct && product.seller && (
                        <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <UserInfo user={product.seller} showGroups={false} showAvatar={false} />
                        </div>
                    )}
                    <div className="mt-2">
                        <StarRating rating={product.averageRating || 0} size="sm" className="mb-1" />
                    </div>
                </div>
                <div className="space-y-2">
                    <p className="text-base font-medium text-primary">{getPriceDisplay(product)}</p>
                    <Button className="w-full" variant="outline" asChild>
                        <Link href={route('store.products.show', { product: product.slug })}>View</Link>
                    </Button>
                    <Button
                        className="w-full"
                        onClick={handleAddToCart}
                        disabled={
                            loading === product.id ||
                            (product.inventoryItem?.trackInventory && product.inventoryItem.isOutOfStock && !product.inventoryItem.allowBackorder)
                        }
                    >
                        {loading && <LoaderCircle className="animate-spin" />}
                        {loading === product.id
                            ? 'Adding...'
                            : product.defaultPrice
                              ? product.inventoryItem?.trackInventory && product.inventoryItem.isOutOfStock && !product.inventoryItem.allowBackorder
                                  ? 'Out of stock'
                                  : 'Add to cart'
                              : 'Select options'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
