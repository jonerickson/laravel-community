import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { FormEventHandler } from 'react';

export default function SetEmail({ status }: { status?: string }) {
    const { data, setData, post, processing, reset, errors } = useForm<Required<{ email: string }>>({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('set-email.verify'), {
            onFinish: () => reset('email'),
        });
    };

    return (
        <AuthLayout
            title="Set account email"
            description="Your account does not currently have an email assigned. Pleae provide an email address for your account and then click the verification link that will be sent to you."
        >
            <Head title="Set account email" />

            {status && <div className="text-center text-sm font-medium text-green-600">{status}</div>}

            <form onSubmit={submit}>
                <div className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            placeholder="Email"
                            autoComplete="email"
                            value={data.email}
                            autoFocus
                            required
                            onChange={(e) => setData('email', e.target.value)}
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="flex items-center">
                        <Button className="w-full" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Email verification link
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
