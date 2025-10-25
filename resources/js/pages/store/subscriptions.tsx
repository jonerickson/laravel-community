import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { StarRating } from '@/components/star-rating';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { cn, currency } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import { Check, Crown, Package, RefreshCw, Rocket, Shield, Star, Users, X, Zap } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface SubscriptionsProps {
    subscriptionProducts: App.Data.ProductData[];
    subscriptionReviews: Record<number, App.Data.CommentData[]>;
    currentSubscription: App.Data.SubscriptionData | null;
}

const getIconForPlan = (plan: App.Data.ProductData): React.ElementType => {
    const planName = plan.name.toLowerCase();
    if (planName.includes('starter') || planName.includes('basic')) return Star;
    if (planName.includes('professional') || planName.includes('pro')) return Zap;
    if (planName.includes('enterprise') || planName.includes('business')) return Crown;
    return Rocket;
};

const getColorForPlan = (plan: App.Data.ProductData): string => {
    const planName = plan.name.toLowerCase();
    if (planName.includes('starter') || planName.includes('basic')) return 'from-blue-500 to-blue-600';
    if (planName.includes('professional') || planName.includes('pro')) return 'from-purple-500 to-purple-600';
    if (planName.includes('enterprise') || planName.includes('business')) return 'from-yellow-500 to-yellow-600';
    return 'from-primary to-primary/50';
};

interface PricingCardProps {
    plan: App.Data.ProductData;
    reviews?: App.Data.CommentData[];
    billingCycle: App.Enums.SubscriptionInterval;
    onSubscribe: (planId: number | null, priceId: number | null) => void;
    onCancel: (priceId: number) => void;
    onContinue: (priceId: number) => void;
    isCurrentPlan: boolean;
    isSubscribing: boolean;
    isCancelling: boolean;
    isContinuing: boolean;
    policiesAgreed: Record<number, boolean>;
    onPolicyAgreementChange: (planId: number, agreed: boolean) => void;
    currentSubscription: App.Data.SubscriptionData | null;
}

function PricingCard({
    plan,
    billingCycle,
    onSubscribe,
    onCancel,
    onContinue,
    isCurrentPlan,
    isSubscribing,
    isCancelling,
    isContinuing,
    policiesAgreed,
    onPolicyAgreementChange,
    currentSubscription,
}: PricingCardProps) {
    const Icon = getIconForPlan(plan);
    const color = getColorForPlan(plan);
    const priceData = plan.prices.find((price: App.Data.PriceData) => price.interval === billingCycle);
    const price = priceData ? priceData.amount : 0;
    const priceId = priceData?.id || null;

    const monthlyPrice = plan.prices.find((price: App.Data.PriceData) => price.interval === 'month');
    const yearlyPrice = plan.prices.find((price: App.Data.PriceData) => price.interval === 'year');
    const yearlyDiscount =
        billingCycle === 'year' && monthlyPrice && yearlyPrice ? Math.round((1 - yearlyPrice.amount / 12 / monthlyPrice.amount) * 100) : 0;

    return (
        <Card
            className={cn(
                'relative flex w-full flex-col',
                isCurrentPlan && 'ring-2 ring-success',
                plan.isFeatured && !isCurrentPlan && 'ring-2 ring-info',
            )}
        >
            {plan.isFeatured && !isCurrentPlan && (
                <div className="absolute -top-4 left-1/2 z-10 -translate-x-1/2">
                    <Badge variant="default" className="bg-info text-info-foreground">
                        Featured
                    </Badge>
                </div>
            )}
            {isCurrentPlan && (
                <div className="absolute -top-4 left-1/2 z-10 -translate-x-1/2">
                    <Badge variant="default" className="bg-success text-success-foreground">
                        Current
                    </Badge>
                </div>
            )}
            <CardHeader className="pb-4 text-center">
                <div className={`mx-auto mb-4 rounded-full bg-gradient-to-r p-3 ${color} w-fit text-white`}>
                    <Icon className="h-8 w-8" />
                </div>
                <CardTitle className="text-2xl font-bold">{plan.name}</CardTitle>
                <CardDescription className="text-base">
                    <Link href={route('store.subscriptions.reviews', plan.id)} className="my-4 flex w-full items-center justify-center text-center">
                        <StarRating rating={plan.averageRating || 0} showValue={true} />
                    </Link>
                    <RichEditorContent content={plan.description} />
                </CardDescription>

                <div className="mt-6">
                    <div className="flex items-baseline justify-center">
                        <span className="text-4xl font-bold">{currency(price, false)}</span>
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

                {plan.policies && plan.policies.length > 0 && !isCurrentPlan && (
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

                <div className="mt-auto space-y-2 pt-4">
                    {isCurrentPlan ? (
                        <>
                            <Button className="w-full" variant="outline" disabled>
                                <Check className="mr-2 size-4" />
                                Current plan
                            </Button>

                            {currentSubscription?.trialEndsAt && new Date(currentSubscription.trialEndsAt) > new Date() && (
                                <div className="rounded-md bg-info-foreground p-3 text-center">
                                    <p className="text-sm font-medium text-info">Trial Active</p>
                                    <p className="text-xs text-muted-foreground">
                                        Trial ends {new Date(currentSubscription.trialEndsAt).toLocaleDateString()}
                                    </p>
                                </div>
                            )}

                            {currentSubscription?.endsAt && new Date(currentSubscription.endsAt) > new Date() && (
                                <div className="rounded-md bg-warning/10 p-3 text-center">
                                    <p className="text-sm font-medium text-warning">Subscription Ending</p>
                                    <p className="text-xs text-muted-foreground">Ends {new Date(currentSubscription.endsAt).toLocaleDateString()}</p>
                                </div>
                            )}

                            {priceId && (
                                <>
                                    {currentSubscription?.endsAt && new Date(currentSubscription.endsAt) > new Date() ? (
                                        <Button
                                            className="w-full"
                                            variant="secondary"
                                            size="sm"
                                            onClick={() => onContinue(priceId)}
                                            disabled={isContinuing}
                                        >
                                            {isContinuing ? (
                                                <>
                                                    <div className="mr-2 size-4 animate-spin rounded-full border-2 border-current border-b-transparent" />
                                                    Continuing...
                                                </>
                                            ) : (
                                                <>
                                                    <RefreshCw className="mr-2 size-4" />
                                                    Continue subscription
                                                </>
                                            )}
                                        </Button>
                                    ) : (
                                        <Button
                                            className="w-full"
                                            variant="destructive"
                                            size="sm"
                                            onClick={() => onCancel(priceId)}
                                            disabled={isCancelling}
                                        >
                                            {isCancelling ? (
                                                <>
                                                    <div className="mr-2 size-4 animate-spin rounded-full border-2 border-current border-b-transparent" />
                                                    Cancelling...
                                                </>
                                            ) : (
                                                <>
                                                    <X className="mr-2 size-4" />
                                                    Cancel subscription
                                                </>
                                            )}
                                        </Button>
                                    )}
                                </>
                            )}
                        </>
                    ) : (
                        <Button
                            className="w-full"
                            onClick={() => onSubscribe(plan.id, priceId)}
                            disabled={isSubscribing || !priceId || (plan.policies && plan.policies.length > 0 && !policiesAgreed[plan.id])}
                        >
                            {isSubscribing ? (
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

export default function Subscriptions({ subscriptionProducts, subscriptionReviews, currentSubscription }: SubscriptionsProps) {
    const [billingCycle, setBillingCycle] = useState<App.Enums.SubscriptionInterval>('month');
    const [policiesAgreed, setPoliciesAgreed] = useState<Record<number, boolean>>({});
    const [processingPriceId, setProcessingPriceId] = useState<number | null>(null);
    const [cancellingPriceId, setCancellingPriceId] = useState<number | null>(null);
    const [continuingPriceId, setContinuingPriceId] = useState<number | null>(null);
    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const [pendingCancelPriceId, setPendingCancelPriceId] = useState<number | null>(null);
    const [showChangeDialog, setShowChangeDialog] = useState(false);
    const [pendingChangePlan, setPendingChangePlan] = useState<{ planId: number; priceId: number; planName: string } | null>(null);

    const { post: subscribeToPrice, transform: transformSubscribe } = useForm({
        price_id: 0,
    });

    const { delete: cancelSubscription, transform: transformCancel } = useForm({
        price_id: 0,
        immediate: false,
    });

    const { put: continueSubscription, transform: transformContinue } = useForm({
        price_id: 0,
    });

    const availableIntervals = Object.values(['day', 'week', 'month', 'year']).filter((cycle) => {
        return subscriptionProducts.some((plan) => plan.prices.some((price: App.Data.PriceData) => price.interval === cycle));
    });

    const handlePolicyAgreementChange = (planId: number, agreed: boolean) => {
        setPoliciesAgreed((prev) => ({
            ...prev,
            [planId]: agreed,
        }));
    };

    const handleSubscribe = (planId: number | null, priceId: number | null) => {
        if (!priceId || !planId) {
            toast.error('No pricing available for this billing cycle.');
            return;
        }

        if (currentSubscription) {
            const plan = subscriptionProducts.find((p) => p.id === planId);
            if (plan && currentSubscription.externalProductId !== plan.externalProductId) {
                setPendingChangePlan({ planId, priceId, planName: plan.name });
                setShowChangeDialog(true);
                return;
            }
        }

        processSubscriptionChange(priceId);
    };

    const processSubscriptionChange = (priceId: number) => {
        setProcessingPriceId(priceId);

        transformSubscribe((data) => ({
            ...data,
            price_id: priceId,
        }));

        subscribeToPrice(route('store.subscriptions.store'), {
            onFinish: () => {
                setProcessingPriceId(null);
            },
        });
    };

    const confirmSubscriptionChange = () => {
        if (!pendingChangePlan) return;

        setShowChangeDialog(false);
        processSubscriptionChange(pendingChangePlan.priceId);
        setPendingChangePlan(null);
    };

    const handleCancel = (priceId: number) => {
        setPendingCancelPriceId(priceId);
        setShowCancelDialog(true);
    };

    const confirmCancel = (immediate: boolean) => {
        if (!pendingCancelPriceId) return;

        setShowCancelDialog(false);
        setCancellingPriceId(pendingCancelPriceId);

        transformCancel((data) => ({
            ...data,
            price_id: pendingCancelPriceId,
            immediate,
        }));

        cancelSubscription(route('store.subscriptions.destroy'), {
            onFinish: () => {
                setCancellingPriceId(null);
                setPendingCancelPriceId(null);
            },
        });
    };

    const handleContinue = (priceId: number) => {
        setContinuingPriceId(priceId);

        transformContinue((data) => ({
            ...data,
            price_id: priceId,
        }));

        continueSubscription(route('store.subscriptions.update'), {
            onFinish: () => {
                setContinuingPriceId(null);
            },
        });
    };

    return (
        <AppLayout background={true}>
            <Head title="Subscriptions" />

            <div className="text-center">
                <Heading title="Choose your plan" description="Select the perfect subscription plan for your needs. Upgrade or downgrade anytime." />
            </div>

            {subscriptionProducts.length > 0 ? (
                <div className="z-20 -mt-4 flex flex-col gap-6">
                    {availableIntervals.length > 1 && (
                        <div className="flex justify-center pb-4">
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
                    <div
                        className={cn(
                            'grid grid-cols-1 gap-6',
                            subscriptionProducts.length === 1
                                ? 'md:grid-cols-1'
                                : subscriptionProducts.length === 2
                                  ? 'md:grid-cols-2'
                                  : subscriptionProducts.length === 4
                                    ? 'md:grid-cols-2 lg:grid-cols-4'
                                    : 'md:grid-cols-2 lg:grid-cols-3',
                        )}
                    >
                        {subscriptionProducts.map((plan: App.Data.ProductData) => {
                            const priceData = plan.prices.find((price: App.Data.PriceData) => price.interval === billingCycle);
                            const priceId = priceData?.id || null;
                            const isCurrentPlan = currentSubscription?.externalProductId === plan.externalProductId;
                            const isSubscribing = processingPriceId === priceId && priceId !== null;
                            const isCancelling = cancellingPriceId === priceId && priceId !== null;
                            const isContinuing = continuingPriceId === priceId && priceId !== null;

                            return (
                                <div key={plan.id} className="flex justify-center">
                                    <PricingCard
                                        plan={plan}
                                        reviews={subscriptionReviews[plan.id]}
                                        billingCycle={billingCycle}
                                        onSubscribe={handleSubscribe}
                                        onCancel={handleCancel}
                                        onContinue={handleContinue}
                                        isCurrentPlan={isCurrentPlan}
                                        isSubscribing={isSubscribing}
                                        isCancelling={isCancelling}
                                        isContinuing={isContinuing}
                                        policiesAgreed={policiesAgreed}
                                        onPolicyAgreementChange={handlePolicyAgreementChange}
                                        currentSubscription={currentSubscription}
                                    />
                                </div>
                            );
                        })}
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
                    icon={<Package />}
                    title="No subscription plans available"
                    description="We're currently working on our subscription offerings. Check back soon for exciting plans and features!"
                />
            )}

            <Dialog open={showCancelDialog} onOpenChange={setShowCancelDialog}>
                <DialogContent className="mx-4 max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Cancel subscription</DialogTitle>
                        <DialogDescription className="text-sm">
                            Please confirm you would like to cancel your current subscription. Choose when you'd like your subscription to end.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-3">
                            <h4 className="text-sm font-medium">Cancellation options:</h4>
                            <div className="space-y-3 text-sm text-muted-foreground">
                                <div className="flex flex-col space-y-1">
                                    <p>
                                        <strong>End of billing cycle:</strong>
                                    </p>
                                    <p>Keep access until your current billing period ends</p>
                                </div>
                                <div className="flex flex-col space-y-1">
                                    <p>
                                        <strong>Cancel immediately:</strong>
                                    </p>
                                    <p>End access right now and stop future charges</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <div className="flex w-full flex-col gap-2">
                            <Button variant="outline" onClick={() => setShowCancelDialog(false)} className="w-full">
                                Keep subscription
                            </Button>
                            <Button variant="secondary" onClick={() => confirmCancel(false)} className="w-full">
                                Cancel at end of cycle
                            </Button>
                            <Button variant="destructive" onClick={() => confirmCancel(true)} className="w-full">
                                Cancel immediately
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={showChangeDialog} onOpenChange={setShowChangeDialog}>
                <DialogContent className="mx-4 max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Change subscription plan</DialogTitle>
                        <DialogDescription className="text-sm">
                            You're about to change your subscription plan. Your billing will be adjusted accordingly.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="space-y-3">
                            <h4 className="text-sm font-medium">Subscription change details:</h4>
                            <div className="space-y-3 text-sm text-muted-foreground">
                                <div className="flex justify-between">
                                    <span>Current plan:</span>
                                    <span className="font-medium text-foreground">{currentSubscription?.product?.name}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>New plan:</span>
                                    <span className="font-medium text-foreground">{pendingChangePlan?.planName}</span>
                                </div>
                            </div>
                            <div className="rounded-md bg-info-foreground p-3">
                                <p className="text-xs text-info">
                                    Your billing will be prorated based on the time remaining in your current billing cycle. The change will take
                                    effect immediately.
                                </p>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <div className="flex w-full flex-col gap-2">
                            <Button variant="outline" onClick={() => setShowChangeDialog(false)} className="w-full">
                                Keep current plan
                            </Button>
                            <Button onClick={confirmSubscriptionChange} className="w-full">
                                Confirm plan change
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
