import { Head, useForm } from '@inertiajs/react';
import { IconBrandDiscord } from '@tabler/icons-react';
import { LoaderCircle, Mail } from 'lucide-react';
import { FormEventHandler, useEffect } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type LoginForm = {
    email: string;
    password: string;
    remember: boolean;
    redirect?: string;
};

interface LoginProps {
    status?: string;
    error?: string;
    canResetPassword: boolean;
    discordEnabled: boolean;
}

export default function AuthLogin({ status, error, canResetPassword, discordEnabled }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        const urlParams = new URLSearchParams(window.location.search);
        const redirectUrl = urlParams.get('redirect');

        if (redirectUrl) {
            setData('redirect', redirectUrl);
        }
    }, [setData]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title="Log in to your account" description="Enter your email and password below to log in">
            <Head title="Log in" />

            {error && (
                <div className="rounded-md bg-destructive/10 px-6 py-4 text-center text-sm font-medium text-balance text-destructive">{error}</div>
            )}

            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            required
                            autoFocus
                            tabIndex={1}
                            autoComplete="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="email@example.com"
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <div className="flex items-center">
                            <Label htmlFor="password">Password</Label>
                            {canResetPassword && (
                                <TextLink href={route('password.request')} className="ml-auto text-sm" tabIndex={5}>
                                    Forgot password?
                                </TextLink>
                            )}
                        </div>
                        <Input
                            id="password"
                            type="password"
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Password"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center space-x-3">
                        <Checkbox
                            id="remember"
                            name="remember"
                            checked={data.remember}
                            onClick={() => setData('remember', !data.remember)}
                            tabIndex={3}
                        />
                        <Label htmlFor="remember">Remember me</Label>
                    </div>

                    <Button type="submit" className="mt-4 w-full" tabIndex={4} disabled={processing}>
                        {processing && <LoaderCircle className="animate-spin" />}
                        {processing ? 'Logging in...' : 'Log in'}
                    </Button>

                    <div className="flex items-center">
                        <div className="flex-grow border-t border-muted-foreground/20" />
                        <span className="mx-4 text-sm text-muted-foreground">Or login with</span>
                        <div className="flex-grow border-t border-muted-foreground/20" />
                    </div>

                    <div className="flex flex-col gap-4">
                        <Button className="w-full" tabIndex={4} size="icon" variant="outline" asChild>
                            <a href={route('magic-link.request')}>
                                <Mail />
                                Email me a login link
                            </a>
                        </Button>

                        {discordEnabled && (
                            <Button className="w-full bg-[#424549] text-white" tabIndex={4} size="icon" asChild>
                                <a href={route('oauth.redirect', { provider: 'discord' })}>
                                    <IconBrandDiscord />
                                    Login with Discord
                                </a>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="text-center text-sm text-muted-foreground">
                    Don't have an account?{' '}
                    <TextLink href={route('onboarding')} tabIndex={5}>
                        Sign up
                    </TextLink>
                </div>
            </form>

            {status && <div className="mb-4 text-center text-sm font-medium text-success">{status}</div>}
        </AuthLayout>
    );
}
