import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { ApiError, apiRequest } from '@/utils/api';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { useState } from 'react';

interface DeletePaymentMethodDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    paymentMethod: {
        id: string;
        type: string;
        brand?: string;
        last4?: string;
        email?: string;
    } | null;
}

export default function DeletePaymentMethodDialog({ open, onOpenChange, paymentMethod }: DeletePaymentMethodDialogProps) {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleDelete = async () => {
        if (!paymentMethod) return;

        setLoading(true);
        setError(null);

        try {
            await apiRequest(
                axios.delete('/api/payment-methods', {
                    data: {
                        method: paymentMethod.id,
                    },
                }),
            );

            // Success - close dialog and refresh page
            onOpenChange(false);
            router.reload({ only: ['paymentMethods'] });
        } catch (err) {
            console.error('Error deleting payment method:', err);
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
    };

    const getPaymentMethodDescription = () => {
        if (!paymentMethod) return '';

        if (paymentMethod.type === 'card') {
            return `${paymentMethod.brand?.toUpperCase()} ending in ${paymentMethod.last4}`;
        }

        if (paymentMethod.email) {
            return `${paymentMethod.type} (${paymentMethod.email})`;
        }

        return paymentMethod.type;
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete Payment Method</DialogTitle>
                    <DialogDescription>Are you sure you want to delete this payment method? This action cannot be undone.</DialogDescription>
                </DialogHeader>

                <div>
                    {paymentMethod && (
                        <div className="rounded-md border p-3">
                            <p className="font-medium">{getPaymentMethodDescription()}</p>
                        </div>
                    )}

                    {error && <div className="mt-1 text-sm text-destructive">{error}</div>}
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={handleClose} disabled={loading}>
                        Cancel
                    </Button>
                    <Button type="button" variant="destructive" onClick={handleDelete} disabled={loading}>
                        {loading ? 'Deleting...' : 'Delete payment method'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
