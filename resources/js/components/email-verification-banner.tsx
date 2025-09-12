import { useForm, usePage } from '@inertiajs/react';
import { Mail, X } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { toast } from 'sonner';

export function EmailVerificationBanner() {
    const { auth } = usePage<SharedData>().props;
    const [dismissed, setDismissed] = useState(false);
    const { post, processing } = useForm({});

    const handleResendVerification = () => {
        post(route('verification.send'), {
            onSuccess: () => toast.success('Email verification successfully resent.'),
        });
    };

    if (!auth.mustVerifyEmail || dismissed) {
        return null;
    }

    return (
        <div className="border-b border-warning/30 bg-warning-foreground px-4 py-3">
            <div className="mx-auto flex max-w-7xl items-center justify-between">
                <div className="flex items-center gap-3">
                    <Mail className="h-5 w-5 text-amber-800" />
                    <div className="text-sm">
                        <span className="font-medium text-amber-800">Please verify your email address.</span>
                        <span className="ml-2 text-amber-700">Check your inbox for a verification link.</span>
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={handleResendVerification}
                        disabled={processing}
                        className="border-warning/30 bg-warning/10 text-amber-800 hover:bg-warning/10"
                    >
                        {processing ? 'Sending...' : 'Resend'}
                    </Button>
                    <Button variant="ghost" size="sm" onClick={() => setDismissed(true)} className="text-amber-800 hover:bg-warning/10">
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        </div>
    );
}
