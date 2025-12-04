import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import Loading from '@/components/loading';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { type BreadcrumbItem } from '@/types';
import { Transition } from '@headlessui/react';
import { Deferred, Head, useForm } from '@inertiajs/react';
import { ExternalLink, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';
import { route } from 'ziggy-js';

interface BillingProps {
    user: {
        billing_address?: string;
        billing_address_line_2?: string;
        billing_city?: string;
        billing_state?: string;
        billing_postal_code?: string;
        billing_country?: string;
        vat_id?: string;
        extra_billing_information?: string;
    };
    portalUrl: string | null;
}

type BillingForm = {
    billing_address: string;
    billing_address_line_2: string;
    billing_city: string;
    billing_state: string;
    billing_postal_code: string;
    billing_country: string;
    vat_id: string;
    extra_billing_information: string;
};

const countries = [
    { value: 'US', label: 'United States' },
    { value: 'CA', label: 'Canada' },
    { value: 'GB', label: 'United Kingdom' },
    { value: 'DE', label: 'Germany' },
    { value: 'FR', label: 'France' },
    { value: 'AU', label: 'Australia' },
    { value: 'JP', label: 'Japan' },
    { value: 'ES', label: 'Spain' },
    { value: 'IT', label: 'Italy' },
    { value: 'NL', label: 'Netherlands' },
];

export default function Billing({ user, portalUrl }: BillingProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Settings',
            href: route('settings'),
        },
        {
            title: 'Billing Information',
            href: route('settings.billing'),
        },
    ];

    const { data, setData, post, errors, processing, recentlySuccessful } = useForm<BillingForm>({
        billing_address: user.billing_address || '',
        billing_address_line_2: user.billing_address_line_2 || '',
        billing_city: user.billing_city || '',
        billing_state: user.billing_state || '',
        billing_postal_code: user.billing_postal_code || '',
        billing_country: user.billing_country || '',
        vat_id: user.vat_id || '',
        extra_billing_information: user.extra_billing_information || '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('settings.billing.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing information" />

            <SettingsLayout>
                <div className="space-y-6">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <HeadingSmall
                            title="Billing information"
                            description="Update your account billing information for invoices and tax purposes"
                        />
                        <Deferred fallback={<Loading variant="button" />} data="portalUrl">
                            {portalUrl && (
                                <Button variant="outline" asChild>
                                    <a href={portalUrl} target="_blank" rel="noopener noreferrer">
                                        <ExternalLink />
                                        Billing Portal
                                    </a>
                                </Button>
                            )}
                        </Deferred>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="billing_address">Address Line 1</Label>
                            <Input
                                id="billing_address"
                                value={data.billing_address}
                                onChange={(e) => setData('billing_address', e.target.value)}
                                placeholder="123 Main Street"
                                autoComplete="address-line1"
                            />
                            <InputError message={errors.billing_address} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="billing_address_line_2">Address Line 2 (Optional)</Label>
                            <Input
                                id="billing_address_line_2"
                                value={data.billing_address_line_2}
                                onChange={(e) => setData('billing_address_line_2', e.target.value)}
                                placeholder="Apartment, suite, etc."
                                autoComplete="address-line2"
                            />
                            <InputError message={errors.billing_address_line_2} />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="billing_city">City</Label>
                                <Input
                                    id="billing_city"
                                    value={data.billing_city}
                                    onChange={(e) => setData('billing_city', e.target.value)}
                                    placeholder="New York"
                                    autoComplete="address-level2"
                                />
                                <InputError message={errors.billing_city} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="billing_state">State/Province</Label>
                                <Input
                                    id="billing_state"
                                    value={data.billing_state}
                                    onChange={(e) => setData('billing_state', e.target.value)}
                                    placeholder="NY"
                                    autoComplete="address-level1"
                                />
                                <InputError message={errors.billing_state} />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="billing_postal_code">Postal Code</Label>
                                <Input
                                    id="billing_postal_code"
                                    value={data.billing_postal_code}
                                    onChange={(e) => setData('billing_postal_code', e.target.value)}
                                    placeholder="10001"
                                    autoComplete="postal-code"
                                />
                                <InputError message={errors.billing_postal_code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="billing_country">Country</Label>
                                <Select value={data.billing_country} onValueChange={(value) => setData('billing_country', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select country" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {countries.map((country) => (
                                            <SelectItem key={country.value} value={country.value}>
                                                {country.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.billing_country} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="vat_id">VAT ID (Optional)</Label>
                            <Input id="vat_id" value={data.vat_id} onChange={(e) => setData('vat_id', e.target.value)} placeholder="EU123456789" />
                            <p className="text-sm text-muted-foreground">For EU customers only. Include your VAT number for tax exemption.</p>
                            <InputError message={errors.vat_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="extra_billing_information">Additional Information (Optional)</Label>
                            <Textarea
                                id="extra_billing_information"
                                value={data.extra_billing_information}
                                onChange={(e) => setData('extra_billing_information', e.target.value)}
                                placeholder="Any additional billing information or special instructions..."
                                rows={3}
                            />
                            <p className="text-sm text-muted-foreground">
                                Additional information to include on invoices, such as company details or purchase order numbers.
                            </p>
                            <InputError message={errors.extra_billing_information} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={processing}>
                                {processing && <LoaderCircle className="animate-spin" />}
                                {processing ? 'Saving...' : 'Save'}
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
            </SettingsLayout>
        </AppLayout>
    );
}
