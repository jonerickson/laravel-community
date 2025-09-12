import { useApiRequest } from '@/hooks/use-api-request';
import { router } from '@inertiajs/react';
import { CardElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { toast } from 'sonner';

interface SetupIntent {
    id: string;
    client_secret: string;
    status: string;
    customer: string;
    payment_method_types: string[];
    usage: string;
}

interface AddPaymentMethodData {
    holderName: string;
}

export function usePaymentMethods() {
    const stripe = useStripe();
    const elements = useElements();
    const { loading: setupLoading, execute: executeSetupIntent } = useApiRequest<SetupIntent>();
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

        const setupIntentResponse = await executeSetupIntent(
            {
                url: route('api.payment-methods.create'),
                method: 'GET',
            },
            {},
        );

        if (!setupIntentResponse?.client_secret) {
            throw new Error('Failed to get setup intent from server');
        }

        const { client_secret } = setupIntentResponse;

        const {
            error: stripeError,
            setupIntent: { payment_method },
        } = await stripe.confirmCardSetup(client_secret, {
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

        await executeStore(
            {
                url: route('api.payment-methods.store'),
                method: 'POST',
                data: {
                    method: payment_method,
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
