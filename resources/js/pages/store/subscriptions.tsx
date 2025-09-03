import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Check, Crown, Package, Rocket, Shield, Star, Users, Zap } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
    {
        title: 'Subscriptions',
        href: '/subscriptions',
    },
];

type BillingCycle = 'monthly' | 'yearly';

interface SubscriptionPlan {
    id: number;
    name: string;
    description: string;
    slug: string;
    featured_image_url?: string;
    pricing: {
        monthly: number;
        yearly: number;
    };
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
    onSubscribe: (planId: number) => void;
}

function PricingCard({ plan, billingCycle, onSubscribe }: PricingCardProps) {
    const Icon = getIconForPlan(plan);
    const color = getColorForPlan(plan);
    const price = plan.pricing[billingCycle];
    const yearlyDiscount =
        billingCycle === 'yearly' && plan.pricing.monthly > 0 ? Math.round((1 - plan.pricing.yearly / 12 / plan.pricing.monthly) * 100) : 0;

    return (
        <Card
            className={`relative w-full max-w-sm ${plan.popular ? 'border-2 border-chart-1 shadow-lg' : ''} ${plan.current ? 'ring-2 ring-green-500' : ''}`}
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
                        <span className="ml-1 text-muted-foreground">/{billingCycle === 'monthly' ? 'month' : 'year'}</span>
                    </div>
                    {billingCycle === 'yearly' && yearlyDiscount > 0 && (
                        <div className="mt-2">
                            <Badge variant="secondary">Save {yearlyDiscount}% annually</Badge>
                        </div>
                    )}
                    {billingCycle === 'monthly' && plan.pricing.yearly > 0 && (
                        <p className="mt-2 text-sm text-muted-foreground">${plan.pricing.yearly.toFixed(2)} billed annually</p>
                    )}
                </div>
            </CardHeader>

            <CardContent className="space-y-6">
                {plan.features.length > 0 && (
                    <div className="space-y-3">
                        <h4 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Features Included</h4>
                        <ul className="space-y-2">
                            {plan.features.map((feature, index) => (
                                <li key={index} className="flex items-start">
                                    <Check className="mt-0.5 mr-3 h-4 w-4 flex-shrink-0 text-success" />
                                    <span className="text-sm">{feature}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <div className="pt-4">
                    {plan.current ? (
                        <Button className="w-full" variant="outline" disabled>
                            <Check className="mr-2 h-4 w-4" />
                            Current Plan
                        </Button>
                    ) : (
                        <Button className={`w-full ${plan.popular ? 'bg-chart-1 hover:bg-chart-1/80' : ''}`} onClick={() => onSubscribe(plan.id)}>
                            {plan.popular ? (
                                <>
                                    <Rocket className="mr-2 h-4 w-4" />
                                    Upgrade Now
                                </>
                            ) : (
                                'Choose Plan'
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

    const handleSubscribe = (planId: number) => {
        console.log(`Subscribing to ${planId} with ${billingCycle} billing`);
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
                        <div className="flex justify-center">
                            <Tabs value={billingCycle} onValueChange={(value) => setBillingCycle(value as BillingCycle)}>
                                <TabsList className="grid w-full max-w-md grid-cols-2">
                                    <TabsTrigger value="monthly" className="relative">
                                        Monthly
                                    </TabsTrigger>
                                    <TabsTrigger value="yearly" className="relative">
                                        Yearly
                                    </TabsTrigger>
                                </TabsList>
                            </Tabs>
                        </div>
                        <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                            {subscriptionProducts.map((plan: SubscriptionPlan) => (
                                <div key={plan.id} className="flex justify-center">
                                    <PricingCard plan={plan} billingCycle={billingCycle} onSubscribe={handleSubscribe} />
                                </div>
                            ))}
                        </div>

                        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Shield className="mx-auto mb-4 h-12 w-12 text-blue-500" />
                                    <h3 className="mb-2 font-semibold">Secure payments</h3>
                                    <p className="text-sm text-muted-foreground">
                                        All payments are processed securely through Stripe with industry-standard encryption.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Users className="mx-auto mb-4 h-12 w-12 text-green-500" />
                                    <h3 className="mb-2 font-semibold">24/7 support</h3>
                                    <p className="text-sm text-muted-foreground">
                                        Get help when you need it with our dedicated support team available around the clock.
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6 text-center">
                                    <Rocket className="mx-auto mb-4 h-12 w-12 text-purple-500" />
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
