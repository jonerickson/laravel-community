import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { ApiError, apiRequest } from '@/utils/api';
import { router } from '@inertiajs/react';
import { CardElement, useElements, useStripe } from '@stripe/react-stripe-js';
import axios from 'axios';
import { useState } from 'react';

interface SetupIntent {
    id: string;
    client_secret: string;
    status: string;
    customer: string;
    payment_method_types: string[];
    usage: string;
}

interface AddPaymentMethodDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function AddPaymentMethodDialog({ open, onOpenChange }: AddPaymentMethodDialogProps) {
    const stripe = useStripe();
    const elements = useElements();
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [holderName, setHolderName] = useState('');

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!stripe || !elements) {
            setError('Stripe has not loaded yet. Please try again.');
            return;
        }

        if (!holderName.trim()) {
            setError('Please enter the cardholder name.');
            return;
        }

        setLoading(true);
        setError(null);

        try {
            const setupIntentResponse = await apiRequest<SetupIntent>(axios.get(route('api.payment-methods.create')));

            if (!setupIntentResponse?.client_secret) {
                throw new Error('Failed to get setup intent from server');
            }

            const { client_secret } = setupIntentResponse;

            const cardElement = elements.getElement(CardElement);

            if (!cardElement) {
                throw new Error('Card element not found');
            }

            const { error: stripeError } = await stripe.confirmCardSetup(client_secret, {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: holderName,
                    },
                },
            });

            if (stripeError) {
                throw new Error(stripeError.message || 'Failed to add payment method');
            }

            onOpenChange(false);
            setHolderName('');
            router.reload({ only: ['paymentMethods'] });
        } catch (err) {
            console.error('Error adding payment method:', err);
            const apiError = err as ApiError;
            setError(apiError.message || 'An unexpected error occurred');
        } finally {
            setLoading(false);
        }
    };

    const handleClose = () => {
        if (loading) return;
        onOpenChange(false);
        setError(null);
        setHolderName('');
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Add Payment Method</DialogTitle>
                    <DialogDescription>Add a new credit or debit card to your account.</DialogDescription>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div className="space-y-2">
                        <Input
                            id="holder-name"
                            type="text"
                            value={holderName}
                            onChange={(e) => setHolderName(e.target.value)}
                            placeholder="Cardholder name"
                            disabled={loading}
                            required
                        />
                    </div>

                    <div className="space-y-2">
                        <div className="rounded-md border border-input px-3 py-2">
                            <CardElement
                                options={{
                                    style: {
                                        base: {
                                            fontSize: '16px',
                                            color: '#424770',
                                            '::placeholder': {
                                                color: '#aab7c4',
                                            },
                                        },
                                        invalid: {
                                            color: '#9e2146',
                                        },
                                    },
                                }}
                            />
                        </div>
                    </div>

                    {error && <div className="text-sm text-destructive">{error}</div>}

                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" onClick={handleClose} disabled={loading}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={loading || !stripe || !elements}>
                            {loading ? 'Adding...' : 'Add payment method'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
