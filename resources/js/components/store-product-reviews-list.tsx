import { StarRating } from '@/components/star-rating';
import { StyledUserName } from '@/components/styled-user-name';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';
import { InfiniteScroll } from '@inertiajs/react';
import { formatDistanceToNow } from 'date-fns';

interface ProductReviewsListProps {
    reviews: App.Data.PaginatedData<App.Data.CommentData>;
    scrollProp?: string;
}

interface ReviewItemProps {
    review: App.Data.CommentData;
    isReply?: boolean;
}

function ReviewItem({ review, isReply = false }: ReviewItemProps) {
    return (
        <div className={cn({ 'mt-4 ml-12': isReply })}>
            <div
                className={cn('flex items-start gap-3', {
                    'rounded-lg border border-muted bg-muted/30 p-4': isReply,
                })}
            >
                {review.author && (
                    <Avatar className={cn(isReply ? 'h-8 w-8' : 'h-10 w-10')}>
                        {review.author.avatarUrl && <AvatarImage src={review.author.avatarUrl} />}
                        <AvatarFallback>{review.author.name.charAt(0).toUpperCase()}</AvatarFallback>
                    </Avatar>
                )}
                <div className="min-w-0 flex-1">
                    <div className="mb-1 flex items-center gap-2">
                        <StyledUserName user={review.author} className={cn('font-medium', isReply ? 'text-xs' : 'text-sm')} />
                        {review.rating && !isReply && <StarRating rating={review.rating} size="sm" />}
                    </div>
                    <p className="mb-2 text-xs text-muted-foreground">
                        {review.createdAt ? formatDistanceToNow(new Date(review.createdAt), { addSuffix: true }) : 'N/A'}
                    </p>
                    {review.content && (
                        <span className={cn('leading-relaxed text-foreground', isReply ? 'text-xs' : 'text-sm')}>{review.content}</span>
                    )}
                </div>
            </div>
            {review.replies && review.replies.length > 0 && (
                <div className="mt-4 space-y-4">
                    {review.replies.map((reply) => (
                        <ReviewItem key={reply.id} review={reply} isReply />
                    ))}
                </div>
            )}
        </div>
    );
}

export function StoreProductReviewsList({ reviews, scrollProp = 'reviews' }: ProductReviewsListProps) {
    if (!reviews || reviews.data.length === 0) {
        return (
            <div className="py-8 text-center text-sm text-muted-foreground">
                <p>No reviews yet. Be the first to review this product!</p>
            </div>
        );
    }

    return (
        <InfiniteScroll data={scrollProp}>
            <div className="space-y-6 divide-y divide-muted">
                {reviews.data.map((review) => (
                    <div key={review.id} className="pb-6">
                        <ReviewItem review={review} />
                    </div>
                ))}
            </div>
        </InfiniteScroll>
    );
}
