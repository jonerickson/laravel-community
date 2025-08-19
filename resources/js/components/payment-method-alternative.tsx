import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Banknote, CreditCard, DollarSign, Link as LinkIcon, MoreVertical, Smartphone, Star, Trash2 } from 'lucide-react';

interface PaymentMethodAlternativeProps {
    type: string;
    email?: string;
    isDefault: boolean;
    onSetDefault: () => void;
    onDelete: () => void;
}

export default function PaymentMethodAlternative({ type, email, isDefault, onSetDefault, onDelete }: PaymentMethodAlternativeProps) {
    const getMethodInfo = (type: string) => {
        switch (type) {
            case 'cashapp':
                return {
                    name: 'Cash App Pay',
                    icon: DollarSign,
                    color: 'text-green-600',
                    bgColor: 'bg-green-50 border-green-200',
                };
            case 'link':
                return {
                    name: 'Link',
                    icon: LinkIcon,
                    color: 'text-blue-600',
                    bgColor: 'bg-blue-50 border-blue-200',
                };
            case 'paypal':
                return {
                    name: 'PayPal',
                    icon: Banknote,
                    color: 'text-blue-600',
                    bgColor: 'bg-blue-50 border-blue-200',
                };
            case 'apple_pay':
                return {
                    name: 'Apple Pay',
                    icon: Smartphone,
                    color: 'text-gray-800',
                    bgColor: 'bg-gray-50 border-gray-200',
                };
            case 'google_pay':
                return {
                    name: 'Google Pay',
                    icon: Smartphone,
                    color: 'text-blue-600',
                    bgColor: 'bg-blue-50 border-blue-200',
                };
            default:
                return {
                    name: type.charAt(0).toUpperCase() + type.slice(1),
                    icon: CreditCard,
                    color: 'text-gray-600',
                    bgColor: 'bg-gray-50 border-gray-200',
                };
        }
    };

    const methodInfo = getMethodInfo(type);
    const Icon = methodInfo.icon;

    return (
        <Card className={`w-full max-w-sm border-2 ${methodInfo.bgColor} p-0`}>
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-3">
                        <div className={`rounded-full p-2 ${methodInfo.bgColor}`}>
                            <Icon className={`h-6 w-6 ${methodInfo.color}`} />
                        </div>
                        <div>
                            <div className="flow-row flex items-center gap-2">
                                <h3 className="font-semibold">{methodInfo.name}</h3>
                                {isDefault && (
                                    <Badge variant="secondary">
                                        <Star className="mr-1 h-3 w-3" />
                                        Default
                                    </Badge>
                                )}
                            </div>
                            {email && <p className="text-sm text-muted-foreground">{email}</p>}
                        </div>
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
