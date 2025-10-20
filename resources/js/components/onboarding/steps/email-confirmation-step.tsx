import { LoaderCircle, Mail, RefreshCw } from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/empty-state';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

type EmailConfirmationStepProps = {
    email: string;
    verified: boolean;
    processing: boolean;
    onResend: () => void;
    onNext: () => void;
    onPrevious?: () => void;
};

export function EmailConfirmationStep({ verified, processing, onResend, onNext, onPrevious }: EmailConfirmationStepProps) {
    const [resendCooldown, setResendCooldown] = useState(0);

    const handleResend = () => {
        onResend();
        setResendCooldown(60);

        const interval = setInterval(() => {
            setResendCooldown((prev) => {
                if (prev <= 1) {
                    clearInterval(interval);
                    return 0;
                }
                return prev - 1;
            });
        }, 1000);
    };

    return (
        <div className="flex flex-col gap-6">
            <EmptyState icon={<Mail />} title="Check your email" description="We've sent a verification link to your email address on file." />

            {verified ? (
                <Alert variant="success">
                    <AlertTitle>Email verified</AlertTitle>
                    <AlertDescription>Your email has been successfully verified.</AlertDescription>
                </Alert>
            ) : (
                <div className="rounded-lg border bg-muted/50 p-4">
                    <p className="text-sm text-muted-foreground">
                        <strong className="font-medium text-foreground">Didn't receive the email?</strong>
                        <br />
                        Check your spam folder or click the button below to resend the verification email.
                    </p>
                </div>
            )}

            <div className="flex flex-col gap-3">
                {!verified && (
                    <Button type="button" variant="outline" onClick={handleResend} disabled={resendCooldown > 0 || processing}>
                        {processing ? <LoaderCircle className="size-4 animate-spin" /> : <RefreshCw className="size-4" />}
                        {resendCooldown > 0 ? `Resend in ${resendCooldown}s` : 'Resend verification email'}
                    </Button>
                )}

                <div className="flex gap-3">
                    {onPrevious && (
                        <Button type="button" variant="outline" onClick={onPrevious} className="flex-1">
                            Back
                        </Button>
                    )}
                    <Button type="button" onClick={onNext} disabled={!verified || processing} className={onPrevious ? 'flex-1' : 'w-full'}>
                        {processing && <LoaderCircle className="size-4 animate-spin" />}
                        Continue
                    </Button>
                </div>
            </div>
        </div>
    );
}
