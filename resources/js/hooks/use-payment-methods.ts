import { useApiRequest } from '@/hooks/use-api-request';
import { router } from '@inertiajs/react';
import { CardElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { toast } from 'sonner';
import { route } from 'ziggy-js';

interface AddPaymentMethodData {
    holderName: string;
}

export function usePaymentMethods() {
    const stripe = useStripe();
    const elements = useElements();
    const { loading: setupLoading, execute: executeSetupIntent } = useApiRequest<App.Data.PaymentSetupIntentData>();
    const { loading: deleteLoading, execute: executeDelete } = useApiRequest();
    const { loading: storeLoading, execute: executeStore } = useApiRequest();

    const addPaymentMethod = async (data: AddPaymentMethodData) => {
        if (!stripe || !elements) {
            throw new Error('Stripe has not loaded yet. Please try again.');
        }

        if (!data.holderName.trim()) {
            throw new Error('Please enter the cardholder name.');
        }

        const cardElement = elements.getElement(CardElement);
        if (!cardElement) {
            throw new Error('Card element not found');
        }

        const setupIntentData = await executeSetupIntent(
            {
                url: route('api.payment-methods.create'),
                method: 'GET',
            },
            {},
        );

        if (!setupIntentData?.clientSecret) {
            throw new Error('Failed to get setup intent from server');
        }

        const { clientSecret } = setupIntentData;

        const { error: stripeError, setupIntent } = await stripe.confirmCardSetup(clientSecret, {
            payment_method: {
                card: cardElement,
                billing_details: {
                    name: data.holderName,
                },
            },
        });

        if (stripeError) {
            throw new Error(stripeError.message || 'Failed to add payment method');
        }

        if (!setupIntent?.payment_method) {
            throw new Error('Failed to create payment method');
        }

        await executeStore(
            {
                url: route('api.payment-methods.store'),
                method: 'POST',
                data: {
                    method: setupIntent.payment_method,
                },
            },
            {
                onSuccess: () => {
                    router.reload({ only: ['paymentMethods'] });
                    toast.success('Payment method added successfully.');
                },
            },
        );
    };

    const deletePaymentMethod = async (paymentMethodId: string) => {
        await executeDelete(
            {
                url: route('api.payment-methods.destroy'),
                method: 'DELETE',
                data: {
                    method: paymentMethodId,
                },
            },
            {
                onSuccess: () => {
                    router.reload({ only: ['paymentMethods'] });
                    toast.success('Payment method removed successfully.');
                },
            },
        );
    };

    return {
        addPaymentMethod,
        deletePaymentMethod,
        loading: setupLoading || deleteLoading || storeLoading,
        addLoading: setupLoading,
        deleteLoading,
    };
}
