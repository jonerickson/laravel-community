import AppLogoIcon from '@/components/app-logo-icon';
import { AbstractBackgroundPattern } from '@/components/ui/abstract-background-pattern';
import { Link, usePage } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    title?: string;
    description?: string;
}

export default function AuthSplitLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    const { name } = usePage<App.Data.SharedData>().props;

    return (
        <div className="relative grid h-dvh lg:grid-cols-3">
            <div className="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r">
                <div className="absolute inset-0 bg-zinc-900" />
                <Link href={route('home')} className="relative z-20 flex items-center text-lg font-medium">
                    <AppLogoIcon className="mr-2 size-12 fill-current text-white" />
                    {name}
                </Link>
            </div>
            <div className="relative flex flex-col justify-center lg:col-span-2">
                <AbstractBackgroundPattern />
                <div className="w-full p-8 lg:mx-auto lg:max-w-3xl lg:px-12">
                    <div className="flex w-full flex-col justify-center space-y-6">
                        <Link href={route('home')} className="relative z-20 flex items-center justify-center lg:hidden">
                            <AppLogoIcon className="h-18 fill-current sm:h-12" />
                        </Link>
                        <div className="flex flex-col gap-2 text-center">
                            <h1 className="text-xl font-medium">{title}</h1>
                            <p className="text-sm text-balance text-muted-foreground">{description}</p>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
