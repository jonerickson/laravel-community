import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { useApiRequest } from '@/hooks/use-api-request';
import { cn, pluralize } from '@/lib/utils';
import { LoaderCircle, Star } from 'lucide-react';
import { useState } from 'react';

interface ProductRatingProps {
    product: App.Data.ProductData;
    onRatingAdded?: () => void;
}

export function StoreProductRating({ product, onRatingAdded }: ProductRatingProps) {
    const [rating, setRating] = useState(0);
    const [hoverRating, setHoverRating] = useState(0);
    const [comment, setComment] = useState('');
    const { loading: isSubmitting, execute: executeSubmitRating } = useApiRequest();

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        await executeSubmitRating(
            {
                url: route('api.comments.store'),
                method: 'POST',
                data: {
                    commentable_type: 'product',
                    commentable_id: product.id,
                    content: comment,
                    rating: rating,
                },
            },
            {
                onSuccess: () => {
                    setRating(0);
                    setComment('');
                    onRatingAdded?.();
                },
            },
        );
    };

    return (
        <div className="space-y-4">
            <div className="space-y-3">
                <div>
                    <label className="mb-2 block text-sm font-medium">Add rating</label>
                    <div className="flex items-center gap-1">
                        {Array.from({ length: 5 }, (_, index) => {
                            const starValue = index + 1;
                            const isActive = (hoverRating || rating) >= starValue;

                            return (
                                <button
                                    key={index}
                                    type="button"
                                    className="p-1 transition-transform hover:scale-110"
                                    onMouseEnter={() => setHoverRating(starValue)}
                                    onMouseLeave={() => setHoverRating(0)}
                                    onClick={() => setRating(starValue)}
                                >
                                    <Star
                                        className={cn(
                                            'h-6 w-6',
                                            isActive ? 'fill-yellow-400 text-yellow-400' : 'text-muted-foreground hover:text-yellow-400',
                                        )}
                                    />
                                </button>
                            );
                        })}
                        {rating > 0 && (
                            <span className="ml-2 text-sm text-muted-foreground">
                                {rating} {pluralize('star', rating)}
                            </span>
                        )}
                    </div>
                </div>

                <div>
                    <label htmlFor="comment" className="mb-2 block text-sm font-medium">
                        Review (optional)
                    </label>
                    <Textarea
                        id="comment"
                        value={comment}
                        onChange={(e) => setComment(e.target.value)}
                        placeholder="Share your thoughts about this product..."
                        rows={3}
                    />
                </div>
            </div>

            <Button onClick={handleSubmit} disabled={isSubmitting || rating === 0} className="w-full">
                {isSubmitting && <LoaderCircle className="animate-spin" />}
                {isSubmitting ? 'Submitting...' : 'Submit review'}
            </Button>
        </div>
    );
}
