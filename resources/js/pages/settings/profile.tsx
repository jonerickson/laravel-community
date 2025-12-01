import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

import { CustomFieldInput } from '@/components/custom-field-input';
import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RichTextEditor } from '@/components/ui/rich-text-editor';
import UpdatePassword from '@/components/update-password';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { LoaderCircle, User } from 'lucide-react';
import { route } from 'ziggy-js';

interface ProfilePageProps {
    fields: App.Data.FieldData[];
}

type ProfileForm = {
    name: string;
    email: string;
    signature: string;
    avatar: File | null;
    fields: Record<number, string>;
};

export default function Profile({ fields }: ProfilePageProps) {
    const { auth } = usePage<App.Data.SharedData>().props;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [previewUrl, setPreviewUrl] = useState<string | null>(null);
    const getInitials = useInitials();
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

    const initialFields: Record<number, string> = {};
    fields.forEach((field) => {
        initialFields[field.id] = field.value || '';
    });

    const { data, setData, post, errors, processing, recentlySuccessful } = useForm<ProfileForm>({
        name: auth.user?.name || '',
        email: auth.user?.email || '',
        signature: auth.user?.signature || '',
        avatar: null,
        fields: initialFields,
    });

    if (!auth.user) {
        return null;
    }

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
                <div className="space-y-6">
                    <div className="flex items-center justify-between gap-2">
                        <HeadingSmall title="Profile information" description="Update your username and email address" />
                        {auth && auth.user && auth.user.referenceId && (
                            <Button variant="outline" asChild>
                                <a target="_blank" href={route('users.show', auth.user.referenceId)}>
                                    <User />
                                    View Profile
                                </a>
                            </Button>
                        )}
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label>Profile Picture</Label>
                            <div className="flex items-center gap-4">
                                <Avatar className="h-20 w-20">
                                    {auth.user.avatarUrl && <AvatarImage src={previewUrl || auth.user.avatarUrl || undefined} alt={auth.user.name} />}
                                    <AvatarFallback className="text-lg">{getInitials(auth.user.name || '')}</AvatarFallback>
                                </Avatar>
                                <div className="flex flex-col gap-2">
                                    <Button type="button" variant="outline" onClick={() => fileInputRef.current?.click()}>
                                        Choose photo
                                    </Button>
                                    <p className="text-sm text-muted-foreground">JPG, PNG up to 2MB</p>
                                </div>
                                <input ref={fileInputRef} type="file" accept="image/*" onChange={handleAvatarChange} className="hidden" />
                            </div>
                            <InputError message={errors.avatar} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="name">Username</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                placeholder="Username"
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

                        {fields.length > 0 && (
                            <>
                                <div className="border-t pt-6">
                                    <HeadingSmall title="Custom fields" description="Additional profile information" />
                                </div>

                                {fields.map((field) => (
                                    <CustomFieldInput
                                        key={field.id}
                                        field={field}
                                        value={data.fields[field.id] || ''}
                                        onChange={(value) =>
                                            setData('fields', {
                                                ...data.fields,
                                                [field.id]: value,
                                            })
                                        }
                                        error={errors[`fields.${field.id}` as keyof typeof errors]}
                                    />
                                ))}
                            </>
                        )}

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>
                                {processing && <LoaderCircle className="animate-spin" />}
                                {processing ? 'Saving...' : 'Save profile'}
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

                <UpdatePassword />

                <DeleteUser />
            </SettingsLayout>
        </AppLayout>
    );
}
