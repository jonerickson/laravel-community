import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Elements } from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';
import { useEffect, useState } from 'react';

import AddPaymentMethodDialog from '@/components/add-payment-method-dialog';
import DeletePaymentMethodDialog from '@/components/delete-payment-method-dialog';
import { EmptyState } from '@/components/empty-state';
import HeadingSmall from '@/components/heading-small';
import PaymentMethodAlternative from '@/components/payment-method-alternative';
import PaymentMethodCard from '@/components/payment-method-card';
import { Button } from '@/components/ui/button';
import { useApiRequest } from '@/hooks/use-api-request';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { CreditCard, Plus } from 'lucide-react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: route('settings'),
    },
    {
        title: 'Payment Methods',
        href: route('settings.payment-methods'),
    },
];

const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_KEY);

interface PaymentMethod {
    id: string;
    type: string;
    brand?: string;
    last4?: string;
    expMonth?: number;
    expYear?: number;
    holderName?: string;
    holderEmail?: string;
    isDefault: boolean;
}

interface PaymentMethodsPageProps {
    paymentMethods: PaymentMethod[];
}

export default function PaymentMethods({ paymentMethods: initialPaymentMethods }: PaymentMethodsPageProps) {
    const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>(initialPaymentMethods);
    const [showAddDialog, setShowAddDialog] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<PaymentMethod | null>(null);
    const { execute: executeSetDefault } = useApiRequest();

    useEffect(() => {
        setPaymentMethods(initialPaymentMethods);
    }, [initialPaymentMethods]);

    const handleSetDefault = async (id: string) => {
        await executeSetDefault(
            {
                url: route('api.payment-methods.update'),
                method: 'PATCH',
                data: {
                    method: id,
                    is_default: true,
                },
            },
            {
                onSuccess: () => {
                    router.reload({ only: ['paymentMethods'] });
                    toast.success('Payment method updated successfully.');
                },
            },
        );
    };

    const handleDeleteClick = (paymentMethod: PaymentMethod) => {
        setSelectedPaymentMethod(paymentMethod);
        setShowDeleteDialog(true);
    };

    const cards = paymentMethods.filter((pm) => pm.type === 'card');
    const alternativeMethods = paymentMethods.filter((pm) => pm.type !== 'card');

    return (
        <Elements stripe={stripePromise}>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Payment methods" />

                <SettingsLayout>
                    <div className="space-y-8">
                        <div className="flex items-center justify-between">
                            <HeadingSmall title="Payment methods" description="Manage your payment methods for purchases and subscriptions" />
                            <Button onClick={() => setShowAddDialog(true)}>
                                <Plus className="mr-2 size-4" />
                                Add Payment Method
                            </Button>
                        </div>

                        {cards.length > 0 && (
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">Credit & Debit Cards</h3>
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {cards.map((card) => (
                                        <PaymentMethodCard
                                            key={card.id}
                                            brand={card.brand || 'unknown'}
                                            last4={card.last4 || '0000'}
                                            expMonth={card.expMonth || 0}
                                            expYear={card.expYear || 0}
                                            holderName={card.holderName || 'Unknown'}
                                            isDefault={card.isDefault}
                                            onSetDefault={() => handleSetDefault(card.id)}
                                            onDelete={() => handleDeleteClick(card)}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}

                        {alternativeMethods.length > 0 ? (
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">Digital Wallets & Alternative Methods</h3>
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {alternativeMethods.map((method) => (
                                        <PaymentMethodAlternative
                                            key={method.id}
                                            type={method.type}
                                            email={method.holderEmail}
                                            isDefault={method.isDefault}
                                            onSetDefault={() => handleSetDefault(method.id)}
                                            onDelete={() => handleDeleteClick(method)}
                                        />
                                    ))}
                                </div>
                            </div>
                        ) : (
                            <EmptyState
                                icon={<CreditCard />}
                                title="No payment methods"
                                description="Add a payment method to make purchases and manage subscriptions."
                                buttonText="Add Your First Payment Method"
                                onButtonClick={() => setShowAddDialog(true)}
                            />
                        )}

                        <AddPaymentMethodDialog open={showAddDialog} onOpenChange={setShowAddDialog} />

                        <DeletePaymentMethodDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog} paymentMethod={selectedPaymentMethod} />
                    </div>
                </SettingsLayout>
            </AppLayout>
        </Elements>
    );
}
