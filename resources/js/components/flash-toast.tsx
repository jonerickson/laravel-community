import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { AlertCircle, CheckCircle, Info, XCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

type ToastVariant = 'default' | 'success' | 'warning' | 'destructive';

const variantIcons = {
    default: Info,
    success: CheckCircle,
    warning: AlertCircle,
    destructive: XCircle,
};

export default function FlashToast() {
    const { flash } = usePage<SharedData>().props;
    const [visible, setVisible] = useState(false);

    const message = flash?.message;
    const variant = (flash?.messageVariant as ToastVariant) || 'success';

    useEffect(() => {
        if (message) {
            setVisible(true);

            const timer = setTimeout(() => {
                setVisible(false);
            }, 5000);

            return () => clearTimeout(timer);
        }
    }, [message]);

    if (!message || !visible) {
        return null;
    }

    const Icon = variantIcons[variant];

    return (
        <div className="fixed right-4 bottom-4 z-50 w-full max-w-sm bg-white">
            <Alert variant={variant} className="grid grid-cols-[auto_1fr_auto] items-center gap-3 shadow-lg">
                <Icon className="-mt-1 h-4 w-4" />
                <AlertDescription className="col-start-2 col-end-3 m-0">{message}</AlertDescription>
                <Button variant="ghost" size="sm" className="col-start-3 col-end-4 ml-auto h-8 w-8 p-0" onClick={() => setVisible(false)}>
                    <XCircle className="h-4 w-4" />
                </Button>
            </Alert>
        </div>
    );
}
