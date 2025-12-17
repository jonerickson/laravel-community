import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { getPriceDisplay } from '@/utils/price-display';
import { stripCharacters } from '@/utils/truncate';
import { Link } from '@inertiajs/react';
import { NewspaperIcon, SparklesIcon, TrendingUpIcon } from 'lucide-react';

interface DashboardProductCardProps {
    product: App.Data.ProductData;
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
        <Card className={cn('group flex h-full flex-col overflow-hidden transition-all hover:shadow-lg', className, config.borderCss)}>
            <div className={cn('absolute inset-0 bg-gradient-to-br opacity-50', config.gradient)} />

            <CardHeader className="pb-1">
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

            <CardContent className="flex-1 space-y-6">
                <div className="aspect-[4/3] overflow-hidden rounded-md bg-muted">
                    {product.featuredImageUrl ? (
                        <img
                            src={product.featuredImageUrl}
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
                    <div className="flex items-center gap-2">
                        <h3 className="line-clamp-1 text-sm leading-tight font-semibold">{product.name}</h3>
                        {product.isFeatured && (
                            <Badge variant="default" className="bg-info text-xs text-info-foreground">
                                Featured
                            </Badge>
                        )}
                        {product.inventoryItem && product.inventoryItem.isLowStock && <Badge variant="warning">Low Stock</Badge>}
                        {product.isMarketplaceProduct && <Badge variant="secondary">Community Provided</Badge>}
                    </div>

                    {product.description && <p className="line-clamp-2 text-sm text-muted-foreground">{stripCharacters(product.description)}</p>}

                    <div className="flex items-center justify-between">
                        <span className="text-base font-bold">{getPriceDisplay(product)}</span>
                    </div>
                </div>
            </CardContent>

            <CardFooter className="pt-1">
                <Button asChild className="w-full">
                    <Link href={route('store.products.show', { product: product.slug })}>View product</Link>
                </Button>
            </CardFooter>
        </Card>
    );
}
