import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useState } from 'react';

import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Banknote, CreditCard, DollarSign, Link as LinkIcon, MoreVertical, Plus, Smartphone, Star, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: '/settings',
    },
    {
        title: 'Payment Methods',
        href: '/settings/payment-methods',
    },
];

// Mock data - replace with actual Stripe data
const mockPaymentMethods = [
    {
        id: 'pm_1',
        type: 'card',
        brand: 'visa',
        last4: '4242',
        expMonth: 12,
        expYear: 2025,
        isDefault: true,
        holderName: 'John Doe',
    },
    {
        id: 'pm_2',
        type: 'card',
        brand: 'mastercard',
        last4: '5555',
        expMonth: 8,
        expYear: 2026,
        isDefault: false,
        holderName: 'John Doe',
    },
    {
        id: 'pm_3',
        type: 'cashapp',
        email: 'john.doe@example.com',
        isDefault: false,
    },
    {
        id: 'pm_4',
        type: 'link',
        email: 'john.doe@example.com',
        isDefault: false,
    },
];

interface CreditCardProps {
    brand: string;
    last4: string;
    expMonth: number;
    expYear: number;
    holderName: string;
    isDefault: boolean;
    onSetDefault: () => void;
    onDelete: () => void;
}

function CreditCardComponent({ brand, last4, expMonth, expYear, holderName, isDefault, onSetDefault, onDelete }: CreditCardProps) {
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
        // In a real app, you'd return actual brand logos
        return brand.toUpperCase();
    };

    return (
        <Card className="w-full max-w-sm overflow-hidden p-0">
            <CardContent className="p-0">
                <div className={`relative h-48 w-full bg-gradient-to-br ${getBrandColor(brand)} p-6 text-white shadow-lg`}>
                    {/* Card decorative elements */}
                    <div className="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-white/10"></div>
                    <div className="absolute -top-4 -right-4 h-20 w-20 rounded-full bg-white/5"></div>

                    {/* Brand logo */}
                    <div className="mb-8 flex items-start justify-between">
                        <CreditCard className="h-8 w-8" />
                        <div className="text-lg font-bold tracking-wider">{getBrandLogo(brand)}</div>
                    </div>

                    {/* Card number */}
                    <div className="mb-6 font-mono text-xl tracking-widest">•••• •••• •••• {last4}</div>

                    {/* Cardholder info */}
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

                {/* Card actions */}
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

interface AlternativePaymentMethodProps {
    type: string;
    email?: string;
    isDefault: boolean;
    onSetDefault: () => void;
    onDelete: () => void;
}

function AlternativePaymentMethod({ type, email, isDefault, onSetDefault, onDelete }: AlternativePaymentMethodProps) {
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

export default function PaymentMethods() {
    const [paymentMethods, setPaymentMethods] = useState(mockPaymentMethods);

    const handleSetDefault = (id: string) => {
        setPaymentMethods((prev) =>
            prev.map((pm) => ({
                ...pm,
                isDefault: pm.id === id,
            })),
        );
    };

    const handleDelete = (id: string) => {
        setPaymentMethods((prev) => prev.filter((pm) => pm.id !== id));
    };

    const cards = paymentMethods.filter((pm) => pm.type === 'card');
    const alternativeMethods = paymentMethods.filter((pm) => pm.type !== 'card');

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment Methods" />

            <SettingsLayout>
                <div className="space-y-8">
                    <div className="flex items-center justify-between">
                        <HeadingSmall title="Payment methods" description="Manage your payment methods for purchases and subscriptions" />
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Add Payment Method
                        </Button>
                    </div>

                    {/* Credit Cards Section */}
                    {cards.length > 0 && (
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold">Credit & Debit Cards</h3>
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {cards.map((card) => (
                                    <CreditCardComponent
                                        key={card.id}
                                        brand={card.brand}
                                        last4={card.last4}
                                        expMonth={card.expMonth}
                                        expYear={card.expYear}
                                        holderName={card.holderName}
                                        isDefault={card.isDefault}
                                        onSetDefault={() => handleSetDefault(card.id)}
                                        onDelete={() => handleDelete(card.id)}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Alternative Payment Methods Section */}
                    {alternativeMethods.length > 0 && (
                        <div className="space-y-4">
                            <h3 className="text-lg font-semibold">Digital Wallets & Alternative Methods</h3>
                            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {alternativeMethods.map((method) => (
                                    <AlternativePaymentMethod
                                        key={method.id}
                                        type={method.type}
                                        email={method.email}
                                        isDefault={method.isDefault}
                                        onSetDefault={() => handleSetDefault(method.id)}
                                        onDelete={() => handleDelete(method.id)}
                                    />
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Add New Payment Method Section */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Add a new payment method</CardTitle>
                            <CardDescription>Choose from various payment options supported by Stripe</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <Button variant="outline" className="h-20 flex-col">
                                    <CreditCard className="mb-2 h-6 w-6" />
                                    <span className="text-xs">Credit Card</span>
                                </Button>
                                <Button variant="outline" className="h-20 flex-col">
                                    <DollarSign className="mb-2 h-6 w-6" />
                                    <span className="text-xs">Cash App</span>
                                </Button>
                                <Button variant="outline" className="h-20 flex-col">
                                    <LinkIcon className="mb-2 h-6 w-6" />
                                    <span className="text-xs">Link</span>
                                </Button>
                                <Button variant="outline" className="h-20 flex-col">
                                    <Smartphone className="mb-2 h-6 w-6" />
                                    <span className="text-xs">Apple Pay</span>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>

                    {paymentMethods.length === 0 && (
                        <EmptyState
                            icon={<CreditCard className="h-12 w-12" />}
                            title="No payment methods"
                            description="Add a payment method to make purchases and manage subscriptions."
                            buttonText="Add Your First Payment Method"
                        />
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
