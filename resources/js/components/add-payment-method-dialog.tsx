import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { usePaymentMethods } from '@/hooks/use-payment-methods';
import { CardElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

interface AddPaymentMethodDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

export default function AddPaymentMethodDialog({ open, onOpenChange }: AddPaymentMethodDialogProps) {
    const stripe = useStripe();
    const elements = useElements();
    const { addPaymentMethod } = usePaymentMethods();
    const [error, setError] = useState<string | null>(null);
    const [holderName, setHolderName] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        setError(null);
        setLoading(true);

        try {
            await addPaymentMethod({ holderName });
            onOpenChange(false);
            setHolderName('');
        } catch (err) {
            console.error('Error adding payment method:', err);
            setError((err as Error).message || 'An unexpected error occurred.');
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
            <DialogContent className="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Add a payment method</DialogTitle>
                    <DialogDescription>
                        Add a new credit or debit card to your account. In addition, mobile pay such as Apple Pay or Google Pay may be available
                        during checkout.
                    </DialogDescription>
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

                    <div className="grid gap-2">
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
                        {error && <InputError message={error} />}
                    </div>

                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" onClick={handleClose} disabled={loading}>
                            Cancel
                        </Button>
                        <Button type="submit" disabled={loading || !stripe || !elements}>
                            {loading && <LoaderCircle className="animate-spin" />}
                            {loading ? 'Adding...' : 'Add payment method'}
                        </Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
