import { useApiRequest } from '@/hooks/use-api-request';
import { router, useForm } from '@inertiajs/react';
import { CardElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { route } from 'ziggy-js';

interface AddPaymentMethodData {
    holderName: string;
}

export function usePaymentMethods() {
    const stripe = useStripe();
    const elements = useElements();
    const { loading: setupLoading, execute: executeSetupIntent } = useApiRequest<App.Data.PaymentSetupIntentData>();

    const storeForm = useForm({
        method: '',
    });

    const deleteForm = useForm({
        method: '',
    });

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
                url: route('api.payment-methods'),
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

        return new Promise((resolve, reject) => {
            storeForm.transform(() => ({
                method: setupIntent.payment_method as string,
            }));

            storeForm.post(route('settings.payment-methods.store'), {
                onSuccess: () => {
                    router.reload({ only: ['paymentMethods'] });
                    resolve(undefined);
                },
                onError: () => {
                    reject(new Error('Failed to store payment method'));
                },
            });
        });
    };

    const deletePaymentMethod = async (paymentMethodId: string) => {
        return new Promise<void>((resolve, reject) => {
            deleteForm.transform(() => ({
                method: paymentMethodId,
            }));

            deleteForm.delete(route('settings.payment-methods.destroy'), {
                onSuccess: () => {
                    router.reload({ only: ['paymentMethods'] });
                    resolve();
                },
                onError: () => {
                    reject(new Error('Failed to delete payment method'));
                },
            });
        });
    };

    return {
        addPaymentMethod,
        deletePaymentMethod,
        loading: setupLoading || storeForm.processing || deleteForm.processing,
        addLoading: setupLoading || storeForm.processing,
        deleteLoading: deleteForm.processing,
    };
}
