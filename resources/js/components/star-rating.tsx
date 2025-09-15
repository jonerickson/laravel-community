import { cn } from '@/lib/utils';
import { Star } from 'lucide-react';

interface StarRatingProps {
    rating: number;
    maxRating?: number;
    size?: 'sm' | 'md' | 'lg';
    showValue?: boolean;
    className?: string;
}

const sizeClasses = {
    sm: 'size-3',
    md: 'size-4',
    lg: 'size-5',
};

export function StarRating({ rating, maxRating = 5, size = 'md', showValue = false, className }: StarRatingProps) {
    const roundedRating = Math.round(rating * 2) / 2;

    return (
        <div className={cn('flex items-center gap-1', className)}>
            {showValue && <span className="text-sm font-medium text-muted-foreground">{rating.toFixed(1)}</span>}
            <div className="flex items-center">
                {Array.from({ length: maxRating }, (_, index) => {
                    const starValue = index + 1;
                    const isFullStar = roundedRating >= starValue;
                    const isHalfStar = roundedRating >= starValue - 0.5 && roundedRating < starValue;

                    return (
                        <Star
                            key={index}
                            className={cn(
                                sizeClasses[size],
                                'shrink-0',
                                isFullStar
                                    ? 'fill-yellow-400 text-yellow-400'
                                    : isHalfStar
                                      ? 'fill-yellow-400/50 text-yellow-400'
                                      : 'text-muted-foreground',
                            )}
                        />
                    );
                })}
            </div>
        </div>
    );
}
