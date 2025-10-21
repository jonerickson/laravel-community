import { Toaster } from '@/components/ui/sonner';
import { useFingerprint } from '@/hooks';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';

export default function OnboardingLayout({
    children,
    title,
    description,
    ...props
}: {
    children: React.ReactNode;
    title: string;
    description: string;
}) {
    useFlashMessages();
    useFingerprint();

    return (
        <AuthLayoutTemplate title={title} description={description} {...props}>
            {children}
            <Toaster />
        </AuthLayoutTemplate>
    );
}
