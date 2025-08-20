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
        badgeVariant: 'default' as const,
        badgeText: 'New',
        gradient: 'from-blue-500/10 to-purple-500/10',
    },
    popular: {
        icon: TrendingUpIcon,
        title: 'Most Popular',
        badgeVariant: 'secondary' as const,
        badgeText: 'Popular',
        gradient: 'from-green-500/10 to-emerald-500/10',
    },
    featured: {
        icon: SparklesIcon,
        title: 'Featured Product',
        badgeVariant: 'outline' as const,
        badgeText: 'Featured',
        gradient: 'from-orange-500/10 to-red-500/10',
    },
};

export default function DashboardProductCard({ product, type, className }: DashboardProductCardProps) {
    const config = cardConfig[type];
    const IconComponent = config.icon;

    return (
        <Card className={cn('group relative overflow-hidden transition-all hover:shadow-lg', className)}>
            <div className={cn('absolute inset-0 bg-gradient-to-br opacity-50', config.gradient)} />

            <CardHeader className="relative pb-2">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <IconComponent className="h-4 w-4 text-muted-foreground" />
                        <span className="text-sm font-medium text-muted-foreground">{config.title}</span>
                    </div>
                    <Badge variant={config.badgeVariant} className="text-xs">
                        {config.badgeText}
                    </Badge>
                </div>
            </CardHeader>

            <CardContent className="relative space-y-3">
                <div className="aspect-square overflow-hidden rounded-lg bg-muted">
                    {product.image ? (
                        <img
                            src={product.image}
                            alt={product.name}
                            className="h-full w-full object-cover transition-transform group-hover:scale-105"
                        />
                    ) : (
                        <div className="flex h-full w-full items-center justify-center text-muted-foreground">
                            <IconComponent className="h-12 w-12" />
                        </div>
                    )}
                </div>

                <div className="space-y-2">
                    <h3 className="line-clamp-2 leading-tight font-semibold">{product.name}</h3>

                    {product.description && <p className="line-clamp-2 text-sm text-muted-foreground">{product.description}</p>}

                    <div className="flex items-center justify-between">
                        <span className="text-lg font-bold">${product.price?.toFixed(2) || '0.00'}</span>

                        {product.rating && (
                            <div className="flex items-center gap-1">
                                <StarIcon className="h-4 w-4 fill-yellow-400 text-yellow-400" />
                                <span className="text-sm text-muted-foreground">{product.rating.toFixed(1)}</span>
                            </div>
                        )}
                    </div>

                    {product.category && (
                        <Badge variant="outline" className="text-xs">
                            {product.category.name}
                        </Badge>
                    )}
                </div>
            </CardContent>

            <CardFooter className="relative pt-2">
                <Button asChild className="w-full" size="sm">
                    <Link href={route('store.products.show', { product: product.slug })}>View Product</Link>
                </Button>
            </CardFooter>
        </Card>
    );
}
