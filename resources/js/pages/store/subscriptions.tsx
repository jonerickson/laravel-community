import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Check, Crown, Rocket, Shield, Star, Users, Zap } from 'lucide-react';
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
    id: string;
    name: string;
    description: string;
    icon: React.ElementType;
    features: string[];
    pricing: {
        monthly: number;
        yearly: number;
    };
    popular?: boolean;
    current?: boolean;
    color: string;
}

const subscriptionPlans: SubscriptionPlan[] = [
    {
        id: 'starter',
        name: 'Starter',
        description: 'Perfect for individuals getting started',
        icon: Star,
        features: ['Access to basic features', 'Community support', '5 projects included', 'Basic analytics', 'Email support'],
        pricing: {
            monthly: 9.99,
            yearly: 99.99,
        },
        color: 'from-blue-500 to-blue-600',
    },
    {
        id: 'professional',
        name: 'Professional',
        description: 'Best for growing teams and professionals',
        icon: Zap,
        features: [
            'Everything in Starter',
            'Advanced analytics',
            'Unlimited projects',
            'Priority support',
            'Advanced integrations',
            'Custom branding',
            'Team collaboration',
        ],
        pricing: {
            monthly: 29.99,
            yearly: 299.99,
        },
        popular: true,
        current: true,
        color: 'from-purple-500 to-purple-600',
    },
    {
        id: 'enterprise',
        name: 'Enterprise',
        description: 'For large organizations with advanced needs',
        icon: Crown,
        features: [
            'Everything in Professional',
            'Dedicated account manager',
            'Custom integrations',
            'Advanced security features',
            'SLA guarantees',
            'On-premise deployment',
            'Custom training',
            'White-label solutions',
        ],
        pricing: {
            monthly: 99.99,
            yearly: 999.99,
        },
        color: 'from-gold-500 to-yellow-600',
    },
];

interface PricingCardProps {
    plan: SubscriptionPlan;
    billingCycle: BillingCycle;
    onSubscribe: (planId: string) => void;
}

function PricingCard({ plan, billingCycle, onSubscribe }: PricingCardProps) {
    const Icon = plan.icon;
    const price = plan.pricing[billingCycle];
    const yearlyDiscount = billingCycle === 'yearly' ? Math.round((1 - plan.pricing.yearly / 12 / plan.pricing.monthly) * 100) : 0;

    return (
        <Card
            className={`relative w-full max-w-sm ${plan.popular ? 'border-2 border-purple-500 shadow-lg' : ''} ${plan.current ? 'ring-2 ring-green-500' : ''}`}
        >
            <CardHeader className="pb-4 text-center">
                <div className={`mx-auto mb-4 rounded-full bg-gradient-to-r p-3 ${plan.color} w-fit text-white`}>
                    <Icon className="h-8 w-8" />
                </div>
                <CardTitle className="text-2xl font-bold">{plan.name}</CardTitle>
                <CardDescription className="text-base">{plan.description}</CardDescription>

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
                    {billingCycle === 'monthly' && <p className="mt-2 text-sm text-muted-foreground">${(price * 12).toFixed(2)} billed annually</p>}
                </div>
            </CardHeader>

            <CardContent className="space-y-6">
                <div className="space-y-3">
                    <h4 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Features Included</h4>
                    <ul className="space-y-2">
                        {plan.features.map((feature, index) => (
                            <li key={index} className="flex items-start">
                                <Check className="mt-0.5 mr-3 h-4 w-4 flex-shrink-0 text-green-500" />
                                <span className="text-sm">{feature}</span>
                            </li>
                        ))}
                    </ul>
                </div>

                <div className="pt-4">
                    {plan.current ? (
                        <Button className="w-full" variant="outline" disabled>
                            <Check className="mr-2 h-4 w-4" />
                            Current Plan
                        </Button>
                    ) : (
                        <Button className={`w-full ${plan.popular ? 'bg-purple-500 hover:bg-purple-600' : ''}`} onClick={() => onSubscribe(plan.id)}>
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

export default function Subscriptions() {
    const [billingCycle, setBillingCycle] = useState<BillingCycle>('monthly');

    const handleSubscribe = (planId: string) => {
        // Handle subscription logic here
        console.log(`Subscribing to ${planId} with ${billingCycle} billing`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Subscriptions" />

            <div className="mx-auto max-w-7xl px-4 py-8">
                {/* Header Section */}
                <div className="mb-12 text-center">
                    <Heading
                        title="Choose Your Plan"
                        description="Select the perfect subscription plan for your needs. Upgrade or downgrade anytime."
                    />

                    {/* Billing Cycle Toggle */}
                    <div className="mt-8 flex justify-center">
                        <Tabs value={billingCycle} onValueChange={(value) => setBillingCycle(value as BillingCycle)}>
                            <TabsList className="grid w-full max-w-md grid-cols-2">
                                <TabsTrigger value="monthly" className="relative">
                                    Monthly
                                </TabsTrigger>
                                <TabsTrigger value="yearly" className="relative">
                                    Yearly
                                    <Badge className="ml-2 bg-green-500 px-2 py-0.5 text-xs text-white">Save up to 17%</Badge>
                                </TabsTrigger>
                            </TabsList>
                        </Tabs>
                    </div>
                </div>

                {/* Pricing Cards */}
                <div className="mb-12 grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3">
                    {subscriptionPlans.map((plan) => (
                        <div key={plan.id} className="flex justify-center">
                            <PricingCard plan={plan} billingCycle={billingCycle} onSubscribe={handleSubscribe} />
                        </div>
                    ))}
                </div>

                {/* Additional Information */}
                <div className="mt-16 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <Card>
                        <CardContent className="p-6 text-center">
                            <Shield className="mx-auto mb-4 h-12 w-12 text-blue-500" />
                            <h3 className="mb-2 font-semibold">Secure Payments</h3>
                            <p className="text-sm text-muted-foreground">
                                All payments are processed securely through Stripe with industry-standard encryption.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6 text-center">
                            <Users className="mx-auto mb-4 h-12 w-12 text-green-500" />
                            <h3 className="mb-2 font-semibold">24/7 Support</h3>
                            <p className="text-sm text-muted-foreground">
                                Get help when you need it with our dedicated support team available around the clock.
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="p-6 text-center">
                            <Rocket className="mx-auto mb-4 h-12 w-12 text-purple-500" />
                            <h3 className="mb-2 font-semibold">Cancel Anytime</h3>
                            <p className="text-sm text-muted-foreground">
                                No long-term commitments. Cancel your subscription at any time with just a few clicks.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
