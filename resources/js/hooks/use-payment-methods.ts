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

        const { error: stripeError } = await stripe.confirmCardSetup(client_secret, {
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

        router.reload({ only: ['paymentMethods'] });
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
                onSuccess: () => router.reload({ only: ['paymentMethods'] }),
                onError: (err) => {
                    console.error('Error deleting payment method:', err);
                    toast.error(err.message || 'Unable to delete payment method. Please try again.');
                },
            },
        );
    };

    return {
        addPaymentMethod,
        deletePaymentMethod,
        loading: setupLoading || deleteLoading,
        addLoading: setupLoading,
        deleteLoading,
    };
}
