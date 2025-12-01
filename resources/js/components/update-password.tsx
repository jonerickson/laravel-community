import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LoaderCircle } from 'lucide-react';

type PasswordForm = {
    current_password: string;
    password: string;
    password_confirmation: string;
};

export default function UpdatePassword() {
    const { auth } = usePage<App.Data.SharedData>().props;

    const { data, setData, put, errors, processing, recentlySuccessful, reset } = useForm<PasswordForm>({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const hasPassword = auth.user?.hasPassword ?? true;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('settings.password.update'), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
            },
        });
    };

    return (
        <div className="space-y-6">
            <HeadingSmall
                title={hasPassword ? 'Change password' : 'Set password'}
                description={hasPassword ? 'Update your password to keep your account secure' : 'Set a password for your account'}
            />

            <form onSubmit={submit} className="space-y-6">
                {hasPassword && (
                    <div className="grid gap-2">
                        <Label htmlFor="current_password">Current password</Label>
                        <Input
                            id="current_password"
                            type="password"
                            value={data.current_password}
                            onChange={(e) => setData('current_password', e.target.value)}
                            required
                            autoComplete="current-password"
                        />
                        <InputError message={errors.current_password} />
                    </div>
                )}

                <div className="grid gap-2">
                    <Label htmlFor="password">New password</Label>
                    <Input
                        id="password"
                        type="password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        required
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Confirm new password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        required
                        autoComplete="new-password"
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <div className="flex items-center gap-4">
                    <Button disabled={processing}>
                        {processing && <LoaderCircle className="animate-spin" />}
                        {processing ? 'Updating...' : hasPassword ? 'Update password' : 'Set password'}
                    </Button>
                    <Transition
                        show={recentlySuccessful}
                        enter="transition ease-in-out"
                        enterFrom="opacity-0"
                        leave="transition ease-in-out"
                        leaveTo="opacity-0"
                    >
                        <p className="text-sm text-neutral-600">Saved</p>
                    </Transition>
                </div>
            </form>
        </div>
    );
}
