import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, Mail } from 'lucide-react';

import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';

export function EmailVerificationBanner() {
    const { auth } = usePage<App.Data.SharedData>().props;
    const { post, processing } = useForm({});

    if (!auth || !auth.mustVerifyEmail) {
        return null;
    }

    const handleResendVerification = () => {
        post(route('verification.send'), {
            preserveScroll: true,
            onSuccess: () => toast.success('The email verification was successfully resent.'),
        });
    };

    return (
        <Alert variant="info">
            <Mail className="size-4" />
            <div className="flex items-center justify-between">
                <div>
                    <AlertTitle>Please verify your email address</AlertTitle>
                    <AlertDescription>Check your inbox and click the verification link in the email to verify your account.</AlertDescription>
                </div>
                <Button size="sm" variant="ghost" onClick={handleResendVerification} disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    {processing ? 'Sending...' : 'Resend verification'}
                </Button>
            </div>
        </Alert>
    );
}
