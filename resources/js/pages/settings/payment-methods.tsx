import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
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
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { CreditCard, Plus } from 'lucide-react';
import { route } from 'ziggy-js';

const stripePromise = loadStripe(import.meta.env.VITE_STRIPE_KEY);

interface PaymentMethodsPageProps {
    paymentMethods: App.Data.PaymentMethodData[];
}

export default function PaymentMethods({ paymentMethods: initialPaymentMethods }: PaymentMethodsPageProps) {
    const [paymentMethods, setPaymentMethods] = useState<App.Data.PaymentMethodData[]>(initialPaymentMethods);
    const [showAddDialog, setShowAddDialog] = useState(false);
    const [showDeleteDialog, setShowDeleteDialog] = useState(false);
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<App.Data.PaymentMethodData | null>(null);
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

    const updateForm = useForm({
        method: '',
        is_default: true,
    });

    useEffect(() => {
        setPaymentMethods(initialPaymentMethods);
    }, [initialPaymentMethods]);

    const handleSetDefault = (id: string) => {
        updateForm.transform(() => ({
            method: id,
            is_default: true,
        }));

        updateForm.patch(route('settings.payment-methods.update'), {
            onSuccess: () => {
                router.reload({ only: ['paymentMethods'] });
            },
        });
    };

    const handleDeleteClick = (paymentMethod: App.Data.PaymentMethodData) => {
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
                    <div className="space-y-6">
                        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <HeadingSmall title="Payment methods" description="Manage your payment methods for purchases and subscriptions" />
                            <Button variant="outline" onClick={() => setShowAddDialog(true)}>
                                <Plus />
                                Add Payment Method
                            </Button>
                        </div>

                        {cards.length > 0 && (
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">Credit & Debit Cards</h3>
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {cards.map((card) => (
                                        <PaymentMethodCard
                                            paymentMethod={card}
                                            onSetDefault={() => handleSetDefault(card.id)}
                                            onDelete={() => handleDeleteClick(card)}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}

                        {alternativeMethods.length > 0 && (
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold">Digital Wallets & Alternative Methods</h3>
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {alternativeMethods.map((method) => (
                                        <PaymentMethodAlternative
                                            paymentMethod={method}
                                            onSetDefault={() => handleSetDefault(method.id)}
                                            onDelete={() => handleDeleteClick(method)}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}

                        {cards.length === 0 && alternativeMethods.length === 0 && (
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
