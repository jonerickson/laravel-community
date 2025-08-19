import HeadingSmall from '@/components/heading-small';
import { StarRating } from '@/components/star-rating';
import { Button } from '@/components/ui/button';
import { useCartOperations } from '@/hooks/use-cart-operations';
import { Product, ProductCategory } from '@/types';
import { truncate } from '@/utils/truncate';
import { Link } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';

export default function StoreCategoriesProductItem({ product, category }: { product: Product; category: ProductCategory }) {
    const { addItem, loading } = useCartOperations();

    const handleAddToCart = async () => {
        if (!product.default_price) return;

        await addItem(product.id, product.default_price.id, 1);
    };

    return (
        <div key={product.id} className="group relative flex flex-col p-4 sm:p-6">
            {product.featured_image_url ? (
                <img alt={product.name} src={product.featured_image_url} className="aspect-square rounded-lg object-cover" />
            ) : (
                <div className="flex aspect-square items-center justify-center rounded-lg bg-muted">
                    <ImageIcon className="h-12 w-12 text-muted-foreground" />
                </div>
            )}
            <div className="flex flex-1 flex-col pt-6 pb-4">
                <div className="flex-1">
                    <HeadingSmall title={product.name} description={truncate(product.description)} />
                    <div className="mt-3">
                        <StarRating rating={product.average_rating || 0} size="sm" className="mb-1" />
                    </div>
                </div>
                <div className="mt-4 space-y-2">
                    <p className="text-base font-medium text-primary">${product.default_price?.amount || '0.00'}</p>
                    <Button className="w-full" variant="outline" asChild>
                        <Link href={route('store.categories.products.show', { product: product.slug, category: category.slug })}>View</Link>
                    </Button>
                    <Button className="w-full" onClick={handleAddToCart} disabled={loading === product.id || !product.default_price}>
                        {loading === product.id ? 'Adding...' : 'Add to cart'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
