import { AbstractBackgroundPattern } from '@/components/ui/abstract-background-pattern';
import AuthLayoutTemplate from '@/layouts/auth/auth-simple-layout';

export default function AuthLayout({ children, title, description, ...props }: { children: React.ReactNode; title: string; description: string }) {
    return (
        <div className="relative overflow-hidden">
            <div className="pointer-events-none absolute right-0 bottom-0 z-10">
                <AbstractBackgroundPattern className="h-[800px] w-[1000px]" corner="bottom-right" />
            </div>
            <AuthLayoutTemplate title={title} description={description} {...props}>
                {children}
            </AuthLayoutTemplate>
        </div>
    );
}
