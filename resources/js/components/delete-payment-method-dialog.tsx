import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { usePaymentMethods } from '@/hooks/use-payment-methods';
import { LoaderCircle } from 'lucide-react';
import { useState } from 'react';

interface DeletePaymentMethodDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    paymentMethod: App.Data.PaymentMethodData | null;
}

export default function DeletePaymentMethodDialog({ open, onOpenChange, paymentMethod }: DeletePaymentMethodDialogProps) {
    const { deletePaymentMethod, deleteLoading: loading } = usePaymentMethods();
    const [error, setError] = useState<string | null>(null);

    const handleDelete = async () => {
        if (!paymentMethod) return;

        setError(null);

        try {
            await deletePaymentMethod(paymentMethod.id);
            onOpenChange(false);
        } catch (err) {
            console.error('Error deleting payment method:', err);
            setError((err as Error).message || 'An unexpected error occurred');
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

        if (paymentMethod.holderEmail) {
            return `${paymentMethod.type} (${paymentMethod.holderEmail})`;
        }

        return paymentMethod.type;
    };

    return (
        <Dialog open={open} onOpenChange={handleClose}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Delete payment method</DialogTitle>
                    <DialogDescription>Are you sure you want to delete this payment method? This action cannot be undone.</DialogDescription>
                </DialogHeader>

                <div>
                    {paymentMethod && (
                        <div className="rounded-md border p-3">
                            <p className="font-medium">{getPaymentMethodDescription()}</p>
                        </div>
                    )}

                    {error && <InputError message={error} />}
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" onClick={handleClose} disabled={loading}>
                        Cancel
                    </Button>
                    <Button type="button" variant="destructive" onClick={handleDelete} disabled={loading}>
                        {loading && <LoaderCircle className="animate-spin" />}
                        {loading ? 'Deleting...' : 'Delete payment method'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
