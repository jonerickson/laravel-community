import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { Product } from '@/types';
import { ApiError, apiRequest } from '@/utils/api';
import axios from 'axios';
import { Star } from 'lucide-react';
import { useState } from 'react';

interface ProductRatingProps {
    product: Product;
    onRatingAdded?: () => void;
}

export function StoreProductRating({ product, onRatingAdded }: ProductRatingProps) {
    const [rating, setRating] = useState(0);
    const [hoverRating, setHoverRating] = useState(0);
    const [comment, setComment] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (rating === 0) {
            alert('Please select a rating');
            return;
        }

        setIsSubmitting(true);
        try {
            await apiRequest(
                axios.post(route('api.comments.store'), {
                    commentable_type: 'App\\Models\\Product',
                    commentable_id: product.id,
                    content: comment,
                    rating: rating,
                }),
            );

            setRating(0);
            setComment('');

            onRatingAdded?.();
        } catch (error) {
            console.error('Failed to submit rating:', error);
            const apiError = error as ApiError;
            alert(apiError.message || 'Failed to submit rating');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="space-y-4">
            <div className="space-y-3">
                <div>
                    <label className="mb-2 block text-sm font-medium">Add Rating</label>
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
                                {rating} star{rating !== 1 ? 's' : ''}
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
                {isSubmitting ? 'Submitting...' : 'Submit review'}
            </Button>
        </div>
    );
}
