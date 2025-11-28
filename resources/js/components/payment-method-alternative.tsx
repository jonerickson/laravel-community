import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { CreditCard, DollarSign, Link as LinkIcon, MoreVertical, Smartphone, Star, Trash2 } from 'lucide-react';

interface PaymentMethodAlternativeProps {
    paymentMethod: App.Data.PaymentMethodData;
    onSetDefault: () => void;
    onDelete: () => void;
}

export default function PaymentMethodAlternative({ paymentMethod, onSetDefault, onDelete }: PaymentMethodAlternativeProps) {
    const getMethodInfo = (type: string) => {
        switch (type) {
            case 'cashapp':
                return {
                    name: 'Cash App Pay',
                    icon: DollarSign,
                    color: 'text-success',
                    bgColor: 'bg-success/10 border-success/10',
                };
            case 'link':
                return {
                    name: 'Link',
                    icon: LinkIcon,
                    color: 'text-info',
                    bgColor: 'bg-info/10 border-info/10',
                };
            case 'apple_pay':
                return {
                    name: 'Apple Pay',
                    icon: Smartphone,
                    color: 'text-primary',
                    bgColor: 'bg-primary/10 bg-primary/10',
                };
            case 'google_pay':
                return {
                    name: 'Google Pay',
                    icon: Smartphone,
                    color: 'text-primary',
                    bgColor: 'bg-primary/10 bg-primary/10',
                };
            default:
                return {
                    name: type.charAt(0).toUpperCase() + type.slice(1),
                    icon: CreditCard,
                    color: 'text-info',
                    bgColor: 'bg-info-foreground border-info/10',
                };
        }
    };

    const methodInfo = getMethodInfo(paymentMethod.type);
    const Icon = methodInfo.icon;

    return (
        <Card className={`w-full max-w-sm border-2 ${methodInfo.bgColor} p-0`}>
            <CardContent className="p-4">
                <div className="flex items-center justify-between gap-2">
                    <div className="flex min-w-0 flex-1 items-center space-x-3">
                        <div className={`rounded-full p-2 ${methodInfo.bgColor} flex-shrink-0`}>
                            <Icon className={`h-6 w-6 ${methodInfo.color}`} />
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <h3 className={`truncate font-semibold ${methodInfo.color}`}>{methodInfo.name}</h3>
                                {paymentMethod.isDefault && (
                                    <Badge variant="secondary" className="flex-shrink-0">
                                        <Star className="mr-1 size-3" />
                                        Default
                                    </Badge>
                                )}
                            </div>
                            {paymentMethod.holderEmail && <p className="truncate text-sm text-muted-foreground">{paymentMethod.holderEmail}</p>}
                        </div>
                    </div>
                    <div className="flex-shrink-0">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="ghost" size="icon" className="h-8 w-8">
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
                </div>
            </CardContent>
        </Card>
    );
}
