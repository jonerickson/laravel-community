import { LoaderCircle } from 'lucide-react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type RegistrationStepProps = {
    data: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    };
    errors: Partial<Record<keyof RegistrationStepProps['data'], string>>;
    processing: boolean;
    onChange: (field: keyof RegistrationStepProps['data'], value: string) => void;
    onNext: () => void;
};

export function RegistrationStep({ data, errors, processing, onChange, onNext }: RegistrationStepProps) {
    return (
        <div className="flex flex-col gap-6">
            <div className="grid gap-6">
                <div className="grid gap-2">
                    <Label htmlFor="name">Full name</Label>
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

            <Button type="button" className="w-full" onClick={onNext} disabled={processing}>
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                Continue
            </Button>
        </div>
    );
}
