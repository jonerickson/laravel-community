import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { CreditCard, MoreVertical, Star, Trash2 } from 'lucide-react';

interface PaymentMethodCardProps {
    paymentMethod: App.Data.PaymentMethodData;
    onSetDefault: () => void;
    onDelete: () => void;
}

export default function PaymentMethodCard({ paymentMethod, onSetDefault, onDelete }: PaymentMethodCardProps) {
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
                <div className={`relative h-48 w-full bg-gradient-to-br ${getBrandColor(paymentMethod.brand || '')} p-6 text-white shadow-lg`}>
                    <div className="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-white/10"></div>
                    <div className="absolute -top-4 -right-4 h-20 w-20 rounded-full bg-white/5"></div>

                    <div className="mb-8 flex items-start justify-between">
                        <CreditCard className="h-8 w-8" />
                        <div className="text-lg font-bold tracking-wider">{getBrandLogo(paymentMethod.brand || '')}</div>
                    </div>

                    <div className="mb-6 font-mono text-lg tracking-widest text-nowrap">•••• •••• •••• {paymentMethod.last4}</div>

                    <div className="flex justify-between text-sm">
                        <div>
                            <div className="text-xs text-white/70">CARDHOLDER</div>
                            <div className="font-medium">{paymentMethod.holderName}</div>
                        </div>
                        <div>
                            <div className="text-xs text-white/70">EXPIRES</div>
                            <div className="font-medium">
                                {String(paymentMethod.expMonth).padStart(2, '0')}/{String(paymentMethod.expYear).slice(-2)}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-between p-4">
                    <div className="flex items-center gap-2">
                        <div className="text-sm text-muted-foreground">
                            {(paymentMethod.brand || '').charAt(0).toUpperCase() + (paymentMethod.brand || '').slice(1)} ending in{' '}
                            {paymentMethod.last4}
                        </div>
                        {paymentMethod.isDefault && (
                            <Badge variant="secondary">
                                <Star className="mr-1 size-3" />
                                Default
                            </Badge>
                        )}
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                                <MoreVertical className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {!paymentMethod.isDefault && (
                                <DropdownMenuItem onClick={onSetDefault}>
                                    <Star />
                                    Set as default
                                </DropdownMenuItem>
                            )}
                            <DropdownMenuItem onClick={onDelete} className="text-destructive">
                                <Trash2 className="text-destructive" />
                                Remove
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </CardContent>
        </Card>
    );
}
