import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Settings',
        href: route('settings'),
    },
    {
        title: 'Account Information',
        href: route('settings.profile.edit'),
    },
];

type ProfileForm = {
    name: string;
    email: string;
    signature: string;
    avatar: File | null;
};

export default function Profile() {
    const { auth } = usePage<SharedData>().props;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const getInitials = useInitials();

    const { data, setData, post, errors, processing, recentlySuccessful } = useForm<ProfileForm>({
        name: auth.user.name,
        email: auth.user.email,
        signature: auth.user.signature || '',
        avatar: null,
    });

    const handleAvatarChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] || null;
        setData('avatar', file);

        if (file) {
            const url = URL.createObjectURL(file);
            setPreviewUrl(url);
        } else {
            setPreviewUrl(null);
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('settings.profile.update'), {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Profile settings" />

            <SettingsLayout>
                <div className="space-y-6 md:max-w-2xl">
                    <HeadingSmall title="Profile information" description="Update your name and email address" />

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label>Profile Picture</Label>
                            <div className="flex items-center gap-4">
                                <Avatar className="h-20 w-20">
                                    <AvatarImage src={previewUrl || auth.user.avatar || undefined} alt={auth.user.name} />
                                    <AvatarFallback className="text-lg">{getInitials(auth.user.name)}</AvatarFallback>
                                </Avatar>
                                <div className="flex flex-col gap-2">
                                    <Button type="button" variant="outline" onClick={() => fileInputRef.current?.click()}>
                                        Choose Photo
                                    </Button>
                                    <p className="text-sm text-muted-foreground">JPG, PNG up to 2MB</p>
                                </div>
                                <input ref={fileInputRef} type="file" accept="image/*" onChange={handleAvatarChange} className="hidden" />
                            </div>
                            <InputError message={errors.avatar} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Full name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email Address</Label>
                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                required
                                autoComplete="username"
                                disabled
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="signature">Signature</Label>
                            <RichTextEditor
                                content={data.signature}
                                onChange={(content) => setData('signature', content)}
                                placeholder="Your forum signature (optional)"
                                className="mt-1"
                            />
                            <InputError message={errors.signature} />
                            <p className="text-sm text-muted-foreground">
                                This signature will appear under your posts in forums. Keep it concise and professional.
                            </p>
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>{processing ? 'Saving...' : 'Save'}</Button>
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

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
