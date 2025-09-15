import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, ProductPrice } from '@/types';
import { apiRequest } from '@/utils/api';
import { Head } from '@inertiajs/react';
import axios from 'axios';
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

type BillingCycle = 'daily' | 'weekly' | 'monthly' | 'yearly';

interface SubscriptionPlan {
    id: number;
    name: string;
    description: string;
    slug: string;
    featured_image_url?: string;
    prices: Record<string, ProductPrice>;
    features: string[];
    popular?: boolean;
    current?: boolean;
    categories: string[];
}

interface SubscriptionsProps {
    subscriptionProducts: SubscriptionPlan[];
}

const getIconForPlan = (plan: SubscriptionPlan): React.ElementType => {
    const planName = plan.name.toLowerCase();
    if (planName.includes('starter') || planName.includes('basic')) return Star;
    if (planName.includes('professional') || planName.includes('pro')) return Zap;
    if (planName.includes('enterprise') || planName.includes('business')) return Crown;
    return Rocket;
};

const getColorForPlan = (plan: SubscriptionPlan): string => {
    const planName = plan.name.toLowerCase();
    if (planName.includes('starter') || planName.includes('basic')) return 'from-blue-500 to-blue-600';
    if (planName.includes('professional') || planName.includes('pro')) return 'from-purple-500 to-purple-600';
    if (planName.includes('enterprise') || planName.includes('business')) return 'from-yellow-500 to-yellow-600';
    return 'from-primary to-primary/50';
};

interface PricingCardProps {
    plan: SubscriptionPlan;
    billingCycle: BillingCycle;
    onSubscribe: (planId: number | null, priceId: number | null) => void;
    loading?: boolean;
}

function PricingCard({ plan, billingCycle, onSubscribe, loading = false }: PricingCardProps) {
    const Icon = getIconForPlan(plan);
    const color = getColorForPlan(plan);

    const intervalMap: Record<BillingCycle, string> = {
        daily: 'day',
        weekly: 'week',
        monthly: 'month',
        yearly: 'year',
    };

    const interval = intervalMap[billingCycle];
    const priceData = plan.prices[interval];
    const price = priceData ? priceData.amount : 0; // Convert from cents
    const priceId = priceData?.id || null;

    const monthlyPrice = plan.prices.month;
    const yearlyPrice = plan.prices.year;
    const yearlyDiscount =
        billingCycle === 'yearly' && monthlyPrice && yearlyPrice ? Math.round((1 - yearlyPrice.amount / 12 / monthlyPrice.amount) * 100) : 0;

    return (
        <Card
            className={`relative flex w-full max-w-sm flex-col ${plan.popular ? 'border-2 border-info shadow-lg' : ''} ${plan.current ? 'ring-2 ring-success' : ''}`}
        >
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
                    {billingCycle === 'yearly' && yearlyDiscount > 0 && (
                        <div className="mt-2">
                            <Badge variant="secondary">Save {yearlyDiscount}% annually</Badge>
                        </div>
                    )}
                </div>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col space-y-6">
                {plan.features.length > 0 && (
                    <div className="space-y-3">
                        <h4 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Features Included</h4>
                        <ul className="space-y-2">
                            {plan.features.map((feature, index) => (
                                <li key={index} className="flex items-start">
                                    <Check className="mt-0.5 mr-3 size-4 flex-shrink-0 text-success" />
                                    <span className="text-sm">{feature}</span>
                                </li>
                            ))}
                        </ul>
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
                            className={`w-full ${plan.popular ? 'bg-info hover:bg-info/80' : ''}`}
                            onClick={() => onSubscribe(plan.id, priceId)}
                            disabled={loading || !priceId}
                        >
                            {loading ? (
                                <>
                                    <div className="mr-2 size-4 animate-spin rounded-full border-2 border-current border-b-transparent" />
                                    Processing...
                                </>
                            ) : plan.popular ? (
                                <>
                                    <Rocket className="mr-2 size-4" />
                                    Upgrade now
                                </>
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
    const [billingCycle, setBillingCycle] = useState<BillingCycle>('monthly');
    const [loadingPlan, setLoadingPlan] = useState<number | null>(null);

    const intervalMap: Record<BillingCycle, string> = {
        daily: 'day',
        weekly: 'week',
        monthly: 'month',
        yearly: 'year',
    };

    const availableIntervals = (Object.keys(intervalMap) as BillingCycle[]).filter((cycle) => {
        const interval = intervalMap[cycle];
        return subscriptionProducts.some((plan) => plan.prices[interval]);
    });

    const handleSubscribe = async (planId: number | null, priceId: number | null) => {
        if (!priceId) {
            toast.error('No pricing available for this billing cycle.');
            return;
        }

        setLoadingPlan(planId);

        try {
            const response = (await apiRequest(
                axios.post(route('api.subscriptions.checkout'), {
                    price_id: priceId,
                }),
            )) as string | null | undefined;

            if (response) {
                window.location.href = response;
            } else {
                console.error('Failed to create checkout session');
                toast.error('Failed to create checkout session. Please try again.');
            }
        } catch (err) {
            console.error('Failed to initiate subscription checkout:', err);
            toast.error('Failed to initiate subscription checkout. Please try again.');
        } finally {
            setLoadingPlan(null);
        }
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
                                <Tabs value={billingCycle} onValueChange={(value) => setBillingCycle(value as BillingCycle)}>
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
                            {subscriptionProducts.map((plan: SubscriptionPlan) => (
                                <div key={plan.id} className="flex justify-center">
                                    <PricingCard
                                        plan={plan}
                                        billingCycle={billingCycle}
                                        onSubscribe={handleSubscribe}
                                        loading={loadingPlan === plan.id}
                                    />
                                </div>
                            ))}
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Shield className="mx-auto mb-4 h-12 w-12 text-info" />
                                    <h3 className="mb-2 font-semibold">Secure payments</h3>
                                    <p className="text-sm text-muted-foreground">
                                        All payments are processed securely through Stripe with industry-standard encryption.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Users className="mx-auto mb-4 h-12 w-12 text-success" />
                                    <h3 className="mb-2 font-semibold">24/7 support</h3>
                                    <p className="text-sm text-muted-foreground">
                                        Get help when you need it with our dedicated support team available around the clock.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Rocket className="mx-auto mb-4 h-12 w-12 text-destructive" />
                                    <h3 className="mb-2 font-semibold">Cancel anytime</h3>
                                    <p className="text-sm text-muted-foreground">
                                        No long-term commitments. Cancel your subscription at any time with just a few clicks.
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                ) : (
                    <div className="mb-12">
                        <EmptyState
                            icon={<Package className="h-12 w-12" />}
                            title="No subscription plans available"
                            description="We're currently working on our subscription offerings. Check back soon for exciting plans and features!"
                        />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
