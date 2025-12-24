import { StoreProductRating } from '@/components/store-product-rating';
import { StoreProductReviewsList } from '@/components/store-product-reviews-list';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { abbreviateNumber, pluralize } from '@/lib/utils';
import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import SharedData = App.Data.SharedData;

interface ProductRatingProps {
    product: App.Data.ProductData;
    reviews: App.Data.PaginatedData<App.Data.CommentData>;
    scrollProp?: string;
    onRatingAdded?: () => void;
}

export function StoreProductRatingDialog({ product, reviews, scrollProp = 'reviews', onRatingAdded }: ProductRatingProps) {
    const { auth } = usePage<SharedData>().props;
    const [isRatingModalOpen, setIsRatingModalOpen] = useState(false);

    const handleRatingAdded = () => {
        setIsRatingModalOpen(false);
        if (onRatingAdded) {
            onRatingAdded();
        } else {
            router.reload({ only: ['product', 'reviews'] });
        }
    };

    return (
        <Dialog open={isRatingModalOpen} onOpenChange={setIsRatingModalOpen}>
            <DialogTrigger asChild>
                <button className="text-sm text-primary hover:underline">
                    {reviews.data.length === 0
                        ? 'Write a review'
                        : `See all ${abbreviateNumber(reviews.data.length)} ${pluralize('review', reviews.data.length)}`}
                </button>
            </DialogTrigger>
            <DialogContent className="max-h-[80vh] overflow-y-auto sm:max-w-5xl">
                <DialogHeader>
                    <DialogTitle>Product reviews</DialogTitle>
                    <DialogDescription>View the latest product reviews and ratings.</DialogDescription>
                </DialogHeader>
                <div className="pt-4">
                    <StoreProductReviewsList reviews={reviews} scrollProp={scrollProp} />

                    {auth?.user && (
                        <div className="border-t border-muted pt-6">
                            <h3 className="mb-4 text-lg font-medium">Write a review</h3>
                            <StoreProductRating product={product} onRatingAdded={handleRatingAdded} />
                        </div>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
