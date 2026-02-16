import { Toaster } from '@/components/ui/sonner';
import { useFingerprint } from '@/hooks';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AuthLayoutTemplate from '@/layouts/auth/auth-split-layout';

export default function OnboardingLayout({
    children,
    title,
    description,
    sidebarImageUrl,
    ...props
}: {
    children: React.ReactNode;
    title: string;
    description: string;
    sidebarImageUrl?: string | null;
}) {
    useFlashMessages();
    useFingerprint();

    return (
        <AuthLayoutTemplate title={title} description={description} sidebarImageUrl={sidebarImageUrl} {...props}>
            {children}
            <Toaster />
        </AuthLayoutTemplate>
    );
}
