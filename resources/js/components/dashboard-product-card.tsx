import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { Product } from '@/types';
import { Link } from '@inertiajs/react';
import { NewspaperIcon, SparklesIcon, StarIcon, TrendingUpIcon } from 'lucide-react';

interface DashboardProductCardProps {
    product: Product;
    type: 'newest' | 'popular' | 'featured';
    className?: string;
}

const cardConfig = {
    newest: {
        icon: NewspaperIcon,
        title: 'Newest Arrival',
        titleCss: 'text-info',
        borderCss: 'border-info/20',
        badgeVariant: 'info' as const,
        badgeText: 'New',
        gradient: 'from-info/5 to-info/20',
    },
    popular: {
        icon: TrendingUpIcon,
        title: 'Most Popular',
        titleCss: 'text-success',
        borderCss: 'border-success/20',
        badgeVariant: 'success' as const,
        badgeText: 'Popular',
        gradient: 'from-success/5 to-success/20',
    },
    featured: {
        icon: SparklesIcon,
        title: 'Featured Product',
        titleCss: 'text-destructive',
        borderCss: 'border-destructive/20',
        badgeVariant: 'destructive' as const,
        badgeText: 'Featured',
        gradient: 'from-destructive/5 to-destructive/20',
    },
};

export default function DashboardProductCard({ product, type, className }: DashboardProductCardProps) {
    const config = cardConfig[type];
    const IconComponent = config.icon;

    return (
        <Card className={cn('group relative overflow-hidden transition-all hover:shadow-lg', className, config.borderCss)}>
            <div className={cn('absolute inset-0 bg-gradient-to-br opacity-50', config.gradient)} />

            <CardHeader className="relative pb-1">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-1.5">
                        <IconComponent className={`size-3.5 ${config.titleCss}`} />
                        <span className={`text-sm font-medium ${config.titleCss}`}>{config.title}</span>
                    </div>
                    <Badge variant={config.badgeVariant} className="px-1.5 py-0.5 text-xs">
                        {config.badgeText}
                    </Badge>
                </div>
            </CardHeader>

            <CardContent className="relative space-y-4">
                <div className="aspect-[4/3] overflow-hidden rounded-md bg-muted">
                    {product.featured_image_url ? (
                        <img
                            src={product.featured_image_url}
                            alt={product.name}
                            className="h-full w-full object-cover transition-transform group-hover:scale-105"
                        />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center text-muted-foreground">
                            <IconComponent className="size-8" />
                        </div>
                    )}
                </div>

                <div className="space-y-1.5">
                    <h3 className="line-clamp-1 text-sm leading-tight font-semibold">{product.name}</h3>

                    {product.description && <p className="line-clamp-2 text-sm text-muted-foreground">{product.description}</p>}

                    <div className="flex items-center justify-between">
                        <span className="text-base font-bold">${product.price?.toFixed(2) || '0.00'}</span>

                        {product.rating && (
                            <div className="flex items-center gap-1">
                                <StarIcon className="size-3 fill-yellow-400 text-yellow-400" />
                                <span className="text-xs text-muted-foreground">{product.rating.toFixed(1)}</span>
                            </div>
                        )}
                    </div>

                    {product.category && (
                        <Badge variant="outline" className="h-5 text-xs">
                            {product.category.name}
                        </Badge>
                    )}
                </div>
            </CardContent>

            <CardFooter className="relative pt-1">
                <Button asChild className="h-7 w-full" size="sm">
                    <Link href={route('store.products.show', { product: product.slug })}>View product</Link>
                </Button>
            </CardFooter>
        </Card>
    );
}
