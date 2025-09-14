import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { CreditCard, MoreVertical, Star, Trash2 } from 'lucide-react';

interface PaymentMethodCardProps {
    brand: string;
    last4: string;
    expMonth: number;
    expYear: number;
    holderName: string;
    isDefault: boolean;
    onSetDefault: () => void;
    onDelete: () => void;
}

export default function PaymentMethodCard({
    brand,
    last4,
    expMonth,
    expYear,
    holderName,
    isDefault,
    onSetDefault,
    onDelete,
}: PaymentMethodCardProps) {
    const getBrandColor = (brand: string) => {
        switch (brand.toLowerCase()) {
            case 'visa':
                return 'from-blue-600 to-blue-800';
            case 'mastercard':
                return 'from-red-600 to-red-800';
            case 'amex':
                return 'from-green-600 to-green-800';
            case 'discover':
                return 'from-orange-600 to-orange-800';
            default:
                return 'from-gray-600 to-gray-800';
        }
    };

    const getBrandLogo = (brand: string) => {
        return brand.toUpperCase();
    };

    return (
        <Card className="w-full overflow-hidden p-0 sm:max-w-sm">
            <CardContent className="p-0">
                <div className={`relative h-48 w-full bg-gradient-to-br ${getBrandColor(brand)} p-6 text-white shadow-lg`}>
                    <div className="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-white/10"></div>
                    <div className="absolute -top-4 -right-4 h-20 w-20 rounded-full bg-white/5"></div>

                    <div className="mb-8 flex items-start justify-between">
                        <CreditCard className="h-8 w-8" />
                        <div className="text-lg font-bold tracking-wider">{getBrandLogo(brand)}</div>
                    </div>

                    <div className="mb-6 font-mono text-lg tracking-widest text-nowrap">•••• •••• •••• {last4}</div>

                    <div className="flex justify-between text-sm">
                        <div>
                            <div className="text-xs text-white/70">CARDHOLDER</div>
                            <div className="font-medium">{holderName}</div>
                        </div>
                        <div>
                            <div className="text-xs text-white/70">EXPIRES</div>
                            <div className="font-medium">
                                {String(expMonth).padStart(2, '0')}/{String(expYear).slice(-2)}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-between p-4">
                    <div className="flex items-center gap-2">
                        <div className="text-sm text-muted-foreground">
                            {brand.charAt(0).toUpperCase() + brand.slice(1)} ending in {last4}
                        </div>
                        {isDefault && (
                            <Badge variant="secondary">
                                <Star className="mr-1 h-3 w-3" />
                                Default
                            </Badge>
                        )}
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                                <MoreVertical className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {!isDefault && (
                                <DropdownMenuItem onClick={onSetDefault}>
                                    <Star className="mr-2 h-4 w-4" />
                                    Set as default
                                </DropdownMenuItem>
                            )}
                            <DropdownMenuItem onClick={onDelete} className="text-destructive">
                                <Trash2 className="mr-2 h-4 w-4 text-destructive" />
                                Remove
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </CardContent>
        </Card>
    );
}
