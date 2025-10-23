import { AbstractBackgroundPattern } from '@/components/ui/abstract-background-pattern';
import { Toaster } from '@/components/ui/sonner';
import { useFingerprint } from '@/hooks';
import { useFlashMessages } from '@/hooks/use-flash-messages';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({ children, title, description, ...props }: { children: React.ReactNode; title: string; description: string }) {
    useFlashMessages();
    useFingerprint();

    return (
        <div className="relative overflow-hidden">
            <div className="pointer-events-none absolute top-0 -left-150 z-10 md:-left-75 lg:left-0">
                <AbstractBackgroundPattern className="h-[800px] w-[1000px] md:w-[1600px]" corner="bottom-left" />
            </div>
            <AuthLayoutTemplate title={title} description={description} {...props}>
                {children}
                <Toaster />
            </AuthLayoutTemplate>
        </div>
    );
}
