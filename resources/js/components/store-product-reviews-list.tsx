import { StarRating } from '@/components/star-rating';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { InfiniteScroll } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';

interface ProductReviewsListProps {
    reviews: App.Data.PaginatedData<App.Data.CommentData>;
}

export function StoreProductReviewsList({ reviews }: ProductReviewsListProps) {
    if (!reviews || reviews.data.length === 0) {
        return (
            <div className="py-8 text-center text-sm text-muted-foreground">
                <p>No reviews yet. Be the first to review this product!</p>
            </div>
        );
    }

    return (
        <InfiniteScroll data="reviews">
            <div className="space-y-6 divide-y divide-muted">
                {reviews.data.map((review) => (
                    <div key={review.id} className="pb-6">
                        <div className="flex items-start gap-3">
                            {review.author && (
                                <Avatar className="h-10 w-10">
                                    {review.author.avatarUrl && <AvatarImage src={review.author.avatarUrl} />}
                                    <AvatarFallback>{review.author.name.charAt(0).toUpperCase()}</AvatarFallback>
                                </Avatar>
                            )}
                            <div className="min-w-0 flex-1">
                                <div className="mb-1 flex items-center gap-2">
                                    <h4 className="text-sm font-medium">{review.author?.name || review.user?.name || 'Anonymous'}</h4>
                                    {review.rating && <StarRating rating={review.rating} size="sm" />}
                                </div>
                                <p className="mb-2 text-xs text-muted-foreground">
                                    {review.createdAt ? formatDistanceToNow(new Date(review.createdAt), { addSuffix: true }) : 'N/A'}
                                </p>
                                {review.content && <span className="text-sm leading-relaxed text-foreground">{review.content}</span>}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </InfiniteScroll>
    );
}
