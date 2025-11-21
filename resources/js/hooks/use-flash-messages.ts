import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

/**
 * Hook to automatically display flash messages as toast notifications
 *
 * Usage in Laravel controllers:
 * - return redirect()->back()->with('message', 'Success!')->with('messageVariant', 'success');
 * - return redirect()->back()->with(['message' => 'Error occurred', 'messageVariant' => 'error']);
 *
 * Supported variants: success, error, danger, warning, info
 */
export function useFlashMessages() {
    const { flash } = usePage<App.Data.SharedData>().props;
    const lastMessageRef = useRef<string | null>(null);

    useEffect(() => {
        if (flash?.message && flash.message !== lastMessageRef.current) {
            lastMessageRef.current = flash.message;

            const variant = flash.messageVariant;

            switch (variant) {
                case 'success':
                    toast.success(flash.message);
                    break;
                case 'error':
                case 'danger':
                    toast.error(flash.message);
                    break;
                case 'warning':
                    toast.warning(flash.message);
                    break;
                case 'info':
                    toast.info(flash.message);
                    break;
                default:
                    toast.info(flash.message);
                    break;
            }
        }
    }, [flash]);
}
