import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { FormEventHandler } from 'react';

export default function SetPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, reset, errors } = useForm<Required<{ password: string; password_confirmation: string }>>({
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('set-password.verify'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout
            title="Set account password"
            description="Your account does not currently have a password set. Pleae provide a password to continue using the platform."
        >
            <Head title="Set account password" />

            {status && <div className="text-center text-sm font-medium text-green-600">{status}</div>}

            <form onSubmit={submit}>
                <div className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="password">New password</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            placeholder="Password"
                            autoComplete="new-password"
                            value={data.password}
                            autoFocus
                            required
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirm new password</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            placeholder="Password"
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            required
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                        />
                    </div>

                    <div className="flex items-center">
                        <Button className="w-full" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Save password
                        </Button>
                    </div>

                    <TextLink href={route('logout')} method="post" className="mx-auto block text-sm">
                        Log out
                    </TextLink>
                </div>
            </form>
        </AuthLayout>
    );
}
