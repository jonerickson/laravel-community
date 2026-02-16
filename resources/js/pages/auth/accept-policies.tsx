import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type AcceptPoliciesProps = {
    policies: App.Data.PolicyData[];
};

export default function AcceptPolicies({ policies }: AcceptPoliciesProps) {
    const { post, processing } = useForm({});
    const [agreed, setAgreed] = useState<Record<number, boolean>>({});

    const allAgreed = policies.every((policy) => agreed[policy.id]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('policies.accept.store'));
    };

    return (
        <AuthLayout title="Policy updates" description="We have updated our policies. Please review and accept the changes to continue.">
            <Head title="Accept updated policies" />

            <form onSubmit={submit} className="space-y-6">
                <div className="grid gap-3">
                    {policies.map((policy) => (
                        <div key={policy.id} className="flex items-center gap-3">
                            <Checkbox
                                id={`policy-${policy.id}`}
                                checked={agreed[policy.id] ?? false}
                                onCheckedChange={(checked) => {
                                    setAgreed((prev) => ({
                                        ...prev,
                                        [policy.id]: checked === true,
                                    }));
                                }}
                                disabled={processing}
                            />
                            <Label htmlFor={`policy-${policy.id}`} className="cursor-pointer text-sm leading-normal font-normal">
                                {policy.consentLabel ? (
                                    policy.consentLabel
                                ) : (
                                    <>
                                        I agree to the{' '}
                                        <a
                                            href={route('policies.show', { category: policy.category?.slug, policy: policy.slug })}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-primary hover:underline"
                                        >
                                            <span className="font-bold">{policy.title}</span>
                                        </a>
                                    </>
                                )}
                            </Label>
                        </div>
                    ))}
                </div>

                <Button className="w-full" disabled={processing || !allAgreed}>
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    Accept and continue
                </Button>

                <TextLink href={route('logout')} method="post" className="mx-auto block text-sm">
                    Log out
                </TextLink>
            </form>
        </AuthLayout>
    );
}
