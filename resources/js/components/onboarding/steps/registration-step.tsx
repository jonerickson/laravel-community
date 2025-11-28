import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type RegistrationStepProps = {
    data: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
        policy: Record<number, boolean>;
    };
    errors: Partial<Record<keyof RegistrationStepProps['data'], string>> & Record<string, string>;
    processing: boolean;
    onChange: (field: keyof RegistrationStepProps['data'], value: string | Record<number, boolean>) => void;
    onNext: () => void;
    policies: App.Data.PolicyData[];
};

export function RegistrationStep({ data, errors, processing, onChange, onNext, policies }: RegistrationStepProps) {
    return (
        <div className="flex flex-col gap-6">
            <div className="grid gap-6">
                <div className="grid gap-2">
                    <Label htmlFor="name">Username</Label>
                    <div className="relative">
                        <Input
                            id="name"
                            type="text"
                            required
                            autoFocus
                            autoComplete="name"
                            value={data.name}
                            onChange={(e) => onChange('name', e.target.value)}
                            disabled={processing}
                            placeholder="John Doe"
                        />
                    </div>
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => onChange('email', e.target.value)}
                        disabled={processing}
                        placeholder="john@example.com"
                    />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => onChange('password', e.target.value)}
                        disabled={processing}
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => onChange('password_confirmation', e.target.value)}
                        disabled={processing}
                        placeholder="••••••••"
                    />
                    <InputError message={errors.password_confirmation} />
                </div>
            </div>

            {policies.length > 0 && (
                <div className="grid gap-4">
                    <div className="grid gap-3">
                        <span className="text-sm text-primary">By creating an account, you agree to the following policies:</span>
                        {policies.map((policy) => (
                            <div key={policy.id} className="flex items-center gap-3">
                                <Checkbox
                                    id={`policy-${policy.id}`}
                                    checked={data.policy?.[policy.id] ?? false}
                                    onCheckedChange={(checked) => {
                                        onChange('policy', {
                                            ...data.policy,
                                            [policy.id]: checked === true,
                                        });
                                    }}
                                    disabled={processing}
                                />
                                <Label htmlFor={`policy-${policy.id}`} className="cursor-pointer text-sm leading-normal font-normal">
                                    I agree to the{' '}
                                    <a
                                        href={route('policies.show', { category: policy.category?.slug, policy: policy.slug })}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-primary hover:underline"
                                    >
                                        <span className="font-bold">{policy.title}</span>
                                    </a>
                                </Label>
                            </div>
                        ))}
                    </div>
                    <InputError message={errors['policy.0'] || errors['policy.1'] || errors['policy.2'] || errors['policy.3']} />
                </div>
            )}

            <Button type="button" className="w-full" onClick={onNext} disabled={processing}>
                {processing && <LoaderCircle className="animate-spin" />}
                Continue
            </Button>
        </div>
    );
}
