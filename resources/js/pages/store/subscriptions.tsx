import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useApiRequest } from '@/hooks';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Check, Crown, Package, Rocket, Shield, Star, Users, Zap } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: route('store.index'),
    },
    {
        title: 'Subscriptions',
        href: route('store.subscriptions'),
    },
];

interface SubscriptionsProps {
    subscriptionProducts: App.Data.SubscriptionData[];
}

const getIconForPlan = (plan: App.Data.SubscriptionData): React.ElementType => {
    const planName = plan.name.toLowerCase();
    if (planName.includes('starter') || planName.includes('basic')) return Star;
    if (planName.includes('professional') || planName.includes('pro')) return Zap;
    if (planName.includes('enterprise') || planName.includes('business')) return Crown;
    return Rocket;
};

const getColorForPlan = (plan: App.Data.SubscriptionData): string => {
    const planName = plan.name.toLowerCase();
    if (planName.includes('starter') || planName.includes('basic')) return 'from-blue-500 to-blue-600';
    if (planName.includes('professional') || planName.includes('pro')) return 'from-purple-500 to-purple-600';
    if (planName.includes('enterprise') || planName.includes('business')) return 'from-yellow-500 to-yellow-600';
    return 'from-primary to-primary/50';
};

interface PricingCardProps {
    plan: App.Data.SubscriptionData;
    billingCycle: App.Enums.SubscriptionInterval;
    onSubscribe: (planId: number | null, priceId: number | null) => void;
    loading?: boolean;
    policiesAgreed: Record<number, boolean>;
    onPolicyAgreementChange: (planId: number, agreed: boolean) => void;
}

function PricingCard({ plan, billingCycle, onSubscribe, loading = false, policiesAgreed, onPolicyAgreementChange }: PricingCardProps) {
    const Icon = getIconForPlan(plan);
    const color = getColorForPlan(plan);
    const priceData = plan.activePrices.find((price) => price.interval === billingCycle);
    const price = priceData ? priceData.amount : 0;
    const priceId = priceData?.id || null;

    const monthlyPrice = plan.activePrices.find((price) => price.interval === 'month');
    const yearlyPrice = plan.activePrices.find((price) => price.interval === 'year');
    const yearlyDiscount =
        billingCycle === 'year' && monthlyPrice && yearlyPrice ? Math.round((1 - yearlyPrice.amount / 12 / monthlyPrice.amount) * 100) : 0;

    return (
        <Card className={`relative flex w-full max-w-sm flex-col ${plan.current ? 'ring-2 ring-success' : ''}`}>
            <CardHeader className="pb-4 text-center">
                <div className={`mx-auto mb-4 rounded-full bg-gradient-to-r p-3 ${color} w-fit text-white`}>
                    <Icon className="h-8 w-8" />
                </div>
                <CardTitle className="text-2xl font-bold">{plan.name}</CardTitle>
                <CardDescription className="text-base">
                    <RichEditorContent content={plan.description} />
                </CardDescription>

                <div className="mt-6">
                    <div className="flex items-baseline justify-center">
                        <span className="text-4xl font-bold">${price}</span>
                        <span className="ml-1 text-muted-foreground">/ {billingCycle}</span>
                    </div>
                    {billingCycle === 'year' && yearlyDiscount > 0 && (
                        <div className="mt-2">
                            <Badge variant="secondary">Save {yearlyDiscount}% annually</Badge>
                        </div>
                    )}
                </div>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col space-y-6">
                {plan.metadata && plan.metadata.features?.length > 0 && (
                    <div className="space-y-3">
                        <h4 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Features Included</h4>
                        <ul className="space-y-2">
                            {plan.metadata.features.map((feature: string, index: number) => (
                                <li key={index} className="flex items-start">
                                    <Check className="mt-0.5 mr-3 size-4 flex-shrink-0 text-success" />
                                    <span className="text-sm">{feature}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {plan.policies && plan.policies.length > 0 && !plan.current && (
                    <div className="space-y-3 border-t border-border pt-4">
                        <h4 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Policies</h4>
                        <div className="space-y-2">
                            {plan.policies.map((policy) => (
                                <a
                                    key={policy.id}
                                    href={policy.category?.slug && policy.slug ? route('policies.show', [policy.category.slug, policy.slug]) : '#'}
                                    className="block text-xs text-blue-600 underline hover:text-blue-800"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {policy.title}
                                    {policy.version && ` (v${policy.version})`}
                                </a>
                            ))}
                        </div>
                        <div className="flex items-start space-x-2">
                            <Checkbox
                                id={`policies-agreement-${plan.id}`}
                                checked={policiesAgreed[plan.id] || false}
                                onCheckedChange={(checked) => onPolicyAgreementChange(plan.id, checked === true)}
                                className="mt-0.5"
                            />
                            <label htmlFor={`policies-agreement-${plan.id}`} className="cursor-pointer text-xs leading-relaxed text-muted-foreground">
                                I agree to the above policies and understand that I must comply with them.
                            </label>
                        </div>
                    </div>
                )}

                <div className="mt-auto pt-4">
                    {plan.current ? (
                        <Button className="w-full" variant="outline" disabled>
                            <Check className="mr-2 size-4" />
                            Current plan
                        </Button>
                    ) : (
                        <Button
                            className="w-full"
                            onClick={() => onSubscribe(plan.id, priceId)}
                            disabled={loading || !priceId || (plan.policies && plan.policies.length > 0 && !policiesAgreed[plan.id])}
                        >
                            {loading ? (
                                <>
                                    <div className="mr-2 size-4 animate-spin rounded-full border-2 border-current border-b-transparent" />
                                    Processing...
                                </>
                            ) : plan.policies && plan.policies.length > 0 && !policiesAgreed[plan.id] ? (
                                'Agree to policies to subscribe'
                            ) : !priceId ? (
                                'Not available'
                            ) : (
                                'Choose plan'
                            )}
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function Subscriptions({ subscriptionProducts }: SubscriptionsProps) {
    const [billingCycle, setBillingCycle] = useState<App.Enums.SubscriptionInterval>('month');
    const [loadingPlan, setLoadingPlan] = useState<number | null>(null);
    const [policiesAgreed, setPoliciesAgreed] = useState<Record<number, boolean>>({});
    const { execute: executeCheckout } = useApiRequest<App.Data.CheckoutData>();

    const availableIntervals = Object.values(['day', 'week', 'month', 'year']).filter((cycle) => {
        return subscriptionProducts.some((plan) => plan.activePrices.some((price) => price.interval === cycle));
    });

    const handlePolicyAgreementChange = (planId: number, agreed: boolean) => {
        setPoliciesAgreed((prev) => ({
            ...prev,
            [planId]: agreed,
        }));
    };

    const handleSubscribe = async (planId: number | null, priceId: number | null) => {
        if (!priceId) {
            toast.error('No pricing available for this billing cycle.');
            return;
        }

        setLoadingPlan(planId);

        await executeCheckout(
            {
                url: route('api.subscriptions.checkout'),
                method: 'POST',
                data: {
                    price_id: priceId,
                },
            },
            {
                onSuccess: (data) => {
                    window.location.href = data.checkoutUrl;
                },
                onSettled: () => setLoadingPlan(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Subscriptions" />

            <div className="mx-auto max-w-7xl py-4">
                <div className="mb-12 text-center">
                    <Heading
                        title="Choose your plan"
                        description="Select the perfect subscription plan for your needs. Upgrade or downgrade anytime."
                    />
                </div>

                {subscriptionProducts.length > 0 ? (
                    <div className="flex flex-col gap-8">
                        {availableIntervals.length > 1 && (
                            <div className="flex justify-center">
                                <Tabs value={billingCycle} onValueChange={(value) => setBillingCycle(value as App.Enums.SubscriptionInterval)}>
                                    <TabsList className={`grid w-full max-w-2xl grid-cols-${availableIntervals.length}`}>
                                        {availableIntervals.map((interval) => (
                                            <TabsTrigger key={interval} value={interval} className="relative">
                                                {interval.charAt(0).toUpperCase() + interval.slice(1)}
                                            </TabsTrigger>
                                        ))}
                                    </TabsList>
                                </Tabs>
                            </div>
                        )}
                        <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                            {subscriptionProducts.map((plan: App.Data.SubscriptionData) => (
                                <div key={plan.id} className="flex justify-center">
                                    <PricingCard
                                        plan={plan}
                                        billingCycle={billingCycle}
                                        onSubscribe={handleSubscribe}
                                        loading={loadingPlan === plan.id}
                                        policiesAgreed={policiesAgreed}
                                        onPolicyAgreementChange={handlePolicyAgreementChange}
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Shield className="mx-auto mb-4 size-12 text-info" />
                                    <h3 className="mb-2 font-semibold">Secure payments</h3>
                                    <p className="text-sm text-muted-foreground">
                                        All payments are processed securely through Stripe with industry-standard encryption.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Users className="mx-auto mb-4 size-12 text-success" />
                                    <h3 className="mb-2 font-semibold">24/7 support</h3>
                                    <p className="text-sm text-muted-foreground">
                                        Get help when you need it with our dedicated support team available around the clock.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Rocket className="mx-auto mb-4 size-12 text-destructive" />
                                    <h3 className="mb-2 font-semibold">Cancel anytime</h3>
                                    <p className="text-sm text-muted-foreground">
                                        No long-term commitments. Cancel your subscription at any time with just a few clicks.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <EmptyState
                        icon={<Package className="size-12" />}
                        title="No subscription plans available"
                        description="We're currently working on our subscription offerings. Check back soon for exciting plans and features!"
                    />
                )}
            </div>
        </AppLayout>
    );
}
