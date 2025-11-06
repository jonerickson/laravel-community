import { AbstractBackgroundPattern } from '@/components/ui/abstract-background-pattern';
import { Toaster } from '@/components/ui/sonner';
import { useFingerprint } from '@/hooks';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({ children, title, description, ...props }: { children: React.ReactNode; title: string; description: string }) {
    useFlashMessages();
    useFingerprint();

    return (
        <div className="relative min-h-screen overflow-hidden">
            <div className="pointer-events-none absolute inset-0 z-10">
                <AbstractBackgroundPattern />
            </div>
            <AuthLayoutTemplate title={title} description={description} {...props}>
                {children}
                <Toaster />
            </AuthLayoutTemplate>
        </div>
    );
}
