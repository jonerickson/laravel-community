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
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn, currency } from '@/lib/utils';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { AlertCircle, Check, ChevronDown, Crown, LoaderCircle, Package, RefreshCw, Rocket, Shield, Star, Users, X, Zap } from 'lucide-react';
import { useEffect, useState } from 'react';
import ReactConfetti from 'react-confetti';
import SharedData = App.Data.SharedData;

interface SubscriptionsProps {
    subscriptionProducts: App.Data.ProductData[];
    subscriptionReviews: Record<number, App.Data.CommentData[]>;
    currentSubscription: App.Data.SubscriptionData | null;
    portalUrl?: string | null;
    offerAvailable: boolean;
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
    isExpanded: boolean;
    onToggleExpanded: () => void;
    portalUrl?: string | null;
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
    isExpanded,
    onToggleExpanded,
    portalUrl,
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

    const features = plan.metadata?.features || [];
    const displayedFeatures = isExpanded ? features : features.slice(0, 5);
    const hasMoreFeatures = features.length > 5;

    return (
        <Card
            className={cn(
                'relative flex w-full flex-col',
                isCurrentPlan &&
                    currentSubscription?.status &&
                    ['past_due', 'incomplete'].includes(currentSubscription.status) &&
                    'ring-2 ring-destructive',
                isCurrentPlan &&
                    (!currentSubscription?.status || !['past_due', 'incomplete'].includes(currentSubscription.status)) &&
                    'ring-2 ring-success',
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
            {isCurrentPlan && currentSubscription?.status && ['past_due', 'incomplete'].includes(currentSubscription.status) && (
                <div className="absolute -top-4 left-1/2 z-10 -translate-x-1/2">
                    <Badge variant="default" className="bg-destructive text-destructive-foreground">
                        Payment required
                    </Badge>
                </div>
            )}
            {isCurrentPlan && (!currentSubscription?.status || !['past_due', 'incomplete'].includes(currentSubscription.status)) && (
                <div className="absolute -top-4 left-1/2 z-10 -translate-x-1/2">
                    <Badge variant="default" className="bg-success text-success-foreground">
                        Current
                    </Badge>
                </div>
            )}
            <CardHeader className="pb-4 text-center">
                {plan.featuredImageUrl ? (
                    <img alt={plan.name} src={plan.featuredImageUrl} className="mb-4 aspect-[16/9] w-full rounded-2xl bg-muted object-cover" />
                ) : (
                    <div className={`mx-auto mb-4 rounded-full bg-gradient-to-r p-3 ${color} w-fit text-white`}>
                        <Icon className="h-8 w-8" />
                    </div>
                )}
                <CardTitle className="text-2xl font-bold">{plan.name}</CardTitle>
                <CardDescription className="text-base">
                    <Link
                        href={route('store.subscriptions.reviews', plan.referenceId)}
                        className="my-4 flex w-full items-center justify-center text-center"
                    >
                        <StarRating rating={plan.averageRating || 0} showValue={true} />
                    </Link>
                    {plan.description && <RichEditorContent content={plan.description} />}
                </CardDescription>

                <div className="mt-6">
                    <div className="flex items-baseline justify-center">
                        <span className="text-4xl font-bold">{currency(price, false)}</span>
                        <span className="ml-1 text-muted-foreground">/ {billingCycle}</span>
                    </div>
                    {plan.trialDays > 0 && (
                        <div className="mt-2">
                            <Badge variant="secondary">{plan.trialDays}-day free trial</Badge>
                        </div>
                    )}
                    {billingCycle === 'year' && yearlyDiscount > 0 && (
                        <div className="mt-2">
                            <Badge variant="secondary">Save {yearlyDiscount}% annually</Badge>
                        </div>
                    )}
                </div>
            </CardHeader>

            <CardContent className="flex flex-1 flex-col space-y-6">
                {features.length > 0 && (
                    <div className="space-y-3">
                        <h4 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Features Included</h4>
                        <ul className="space-y-2">
                            {displayedFeatures.map((feature: string, index: number) => (
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
                    {hasMoreFeatures && (
                        <Button variant="ghost" size="sm" onClick={onToggleExpanded} className="w-full">
                            {isExpanded ? (
                                <>
                                    View less
                                    <ChevronDown className="ml-2 size-4 rotate-180 transition-transform" />
                                </>
                            ) : (
                                <>
                                    View {features.length - 5} more
                                    <ChevronDown className="ml-2 size-4 transition-transform" />
                                </>
                            )}
                        </Button>
                    )}
                    {isCurrentPlan ? (
                        <>
                            <Button className="w-full" variant="outline" disabled>
                                <Check />
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

                            {currentSubscription?.status && ['past_due', 'incomplete'].includes(currentSubscription.status) && (
                                <div className="rounded-md bg-destructive/10 p-3 text-center">
                                    <p className="text-sm font-medium text-destructive">Payment Issue</p>
                                    <p className="text-xs text-muted-foreground">
                                        {currentSubscription.status === 'past_due'
                                            ? 'Your payment is past due. Please pay any open invoice or update your payment method to continue your subscription.'
                                            : 'Your payment is incomplete. Please complete your payment to activate your subscription.'}
                                    </p>
                                </div>
                            )}

                            {currentSubscription?.status && ['past_due', 'incomplete'].includes(currentSubscription.status) && portalUrl && (
                                <Button className="w-full" variant="default" size="sm" asChild>
                                    <a href={portalUrl} target="_blank" rel="noopener noreferrer">
                                        <AlertCircle />
                                        Pay invoice
                                    </a>
                                </Button>
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
                                                    <LoaderCircle className="animate-spin" />
                                                    Continuing...
                                                </>
                                            ) : (
                                                <>
                                                    <RefreshCw />
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
                                                    <LoaderCircle className="animate-spin" />
                                                    Cancelling...
                                                </>
                                            ) : (
                                                <>
                                                    <X />
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
                                    <LoaderCircle className="animate-spin" />
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

export default function Subscriptions({
    subscriptionProducts,
    subscriptionReviews,
    currentSubscription,
    portalUrl,
    offerAvailable,
}: SubscriptionsProps) {
    const { name: siteName, logoUrl } = usePage<SharedData>().props;
    const [billingCycle, setBillingCycle] = useState<App.Enums.SubscriptionInterval>('month');
    const [showConfetti, setShowConfetti] = useState(false);
    const [confettiPieces, setConfettiPieces] = useState(200);
    const [policiesAgreed, setPoliciesAgreed] = useState<Record<number, boolean>>({});
    const [expandedCards, setExpandedCards] = useState<Record<number, boolean>>({});
    const [processingPriceId, setProcessingPriceId] = useState<number | null>(null);
    const [cancellingPriceId, setCancellingPriceId] = useState<number | null>(null);
    const [continuingPriceId, setContinuingPriceId] = useState<number | null>(null);
    const [showCancelReasonDialog, setShowCancelReasonDialog] = useState(false);
    const [showExclusiveOfferDialog, setShowExclusiveOfferDialog] = useState(false);
    const [showCancelDialog, setShowCancelDialog] = useState(false);
    const [pendingCancelPriceId, setPendingCancelPriceId] = useState<number | null>(null);
    const [cancellationReason, setCancellationReason] = useState('');
    const [showChangeDialog, setShowChangeDialog] = useState(false);
    const [pendingChangePlan, setPendingChangePlan] = useState<{ planId: number; priceId: number; planName: string } | null>(null);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('complete') === 'true') {
            setShowConfetti(true);
            setConfettiPieces(200);

            const fadeTimer = setTimeout(() => {
                setConfettiPieces(0);
            }, 3500);

            const cleanupTimer = setTimeout(() => {
                setShowConfetti(false);
                router.get(
                    route('store.subscriptions'),
                    {},
                    {
                        preserveState: true,
                        preserveScroll: true,
                    },
                );
            }, 7500);

            return () => {
                clearTimeout(fadeTimer);
                clearTimeout(cleanupTimer);
            };
        }
    }, []);

    const { post: subscribeToPrice, transform: transformSubscribe } = useForm({
        price_id: 0,
    });

    const { delete: cancelSubscription, transform: transformCancel } = useForm({
        price_id: 0,
        immediate: false,
    });

    const { put: continueSubscription, transform: transformContinue } = useForm({
        action: 'continue',
        price_id: 0,
    });

    const { put: acceptCancellationOffer, processing: offerProcessing } = useForm({
        action: 'offer',
    });

    const availableIntervals = (['day', 'week', 'month', 'year'] as const).filter((cycle) => {
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
        setCancellationReason('');
        setShowCancelReasonDialog(true);
    };

    const handleReasonSubmit = () => {
        setShowCancelReasonDialog(false);

        if (offerAvailable && currentSubscription?.status && ['active', 'past_due'].includes(currentSubscription.status)) {
            setShowExclusiveOfferDialog(true);
        } else {
            setShowCancelDialog(true);
        }
    };

    const handleAcceptOffer = async () => {
        acceptCancellationOffer(route('store.subscriptions.update'), {
            onFinish: () => {
                setShowExclusiveOfferDialog(false);
                setShowCancelReasonDialog(false);
            },
        });
    };

    const handleRejectOffer = () => {
        setShowExclusiveOfferDialog(false);
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
            reason: cancellationReason,
        }));

        cancelSubscription(route('store.subscriptions.destroy'), {
            onFinish: () => {
                setCancellingPriceId(null);
                setPendingCancelPriceId(null);
                setCancellationReason('');
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

    const toggleExpanded = (planId: number) => {
        setExpandedCards((prev) => ({
            ...prev,
            [planId]: !prev[planId],
        }));
    };

    return (
        <AppLayout>
            <Head title="Subscriptions">
                <meta name="description" content="Select the perfect subscription plan for your needs. Upgrade or downgrade anytime" />
                <meta property="og:title" content={`Subscriptions - ${siteName}`} />
                <meta property="og:description" content="Select the perfect subscription plan for your needs. Upgrade or downgrade anytime" />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="text-center">
                <Heading title="Choose your plan" description="Select the perfect subscription plan for your needs. Upgrade or downgrade anytime." />
            </div>

            <ReactConfetti run={showConfetti} numberOfPieces={confettiPieces} recycle={confettiPieces > 0} />

            {subscriptionProducts.length > 0 ? (
                <div className="z-20 -mt-4 flex flex-col gap-6">
                    {availableIntervals.length > 1 && (
                        <div className="flex justify-center pb-4">
                            <Tabs value={billingCycle} onValueChange={(value) => setBillingCycle(value as App.Enums.SubscriptionInterval)}>
                                <TabsList
                                    className={cn(
                                        'grid w-full max-w-2xl',
                                        availableIntervals.length === 2 && 'grid-cols-2',
                                        availableIntervals.length === 3 && 'grid-cols-3',
                                        availableIntervals.length === 4 && 'grid-cols-4',
                                    )}
                                >
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
                            const isExpanded = expandedCards[plan.id] || false;

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
                                        isExpanded={isExpanded}
                                        onToggleExpanded={() => toggleExpanded(plan.id)}
                                        portalUrl={portalUrl}
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
                <div className="-mt-6">
                    <EmptyState
                        icon={<Package />}
                        title="No subscription plans available"
                        description="We're currently working on our subscription offerings. Check back soon for exciting plans and features!"
                    />
                </div>
            )}

            <Dialog open={showCancelReasonDialog} onOpenChange={setShowCancelReasonDialog}>
                <DialogContent className="mx-4 max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Before you go...</DialogTitle>
                        <DialogDescription className="text-sm">
                            We're sorry to see you go. Please help us improve by telling us why you're cancelling.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <Textarea
                            placeholder="Tell us why you're cancelling..."
                            value={cancellationReason}
                            onChange={(e) => setCancellationReason(e.target.value)}
                            className="min-h-[100px]"
                            maxLength={500}
                            required
                        />
                        <p className="text-right text-xs text-muted-foreground">{cancellationReason.length}/500</p>
                    </div>
                    <DialogFooter>
                        <div className="flex w-full flex-col gap-2">
                            <Button onClick={handleReasonSubmit} className="w-full" disabled={!cancellationReason.trim()}>
                                Continue
                            </Button>
                            <Button variant="ghost" onClick={() => setShowCancelReasonDialog(false)} className="w-full">
                                Keep subscription
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog open={showExclusiveOfferDialog} onOpenChange={setShowExclusiveOfferDialog}>
                <DialogContent className="mx-4 max-w-lg">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">Exclusive offer just for you!</DialogTitle>
                        <DialogDescription className="text-sm">Before you cancel, we'd like to offer you something special.</DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4 py-4">
                        <div className="rounded-lg border-2 border-success bg-success/10 p-6 text-center">
                            <h3 className="mb-2 text-2xl font-bold text-success">Get 20% Off!</h3>
                            <p className="text-sm text-muted-foreground">
                                As a valued customer, we want to give you 20% off your next renewal. No strings attached.
                            </p>
                        </div>
                        <div className="space-y-2 text-sm text-muted-foreground">
                            <div className="flex items-start gap-2">
                                <Check className="mt-0.5 size-4 flex-shrink-0 text-success" />
                                <span>Your next billing cycle will be 20% off</span>
                            </div>
                            <div className="flex items-start gap-2">
                                <Check className="mt-0.5 size-4 flex-shrink-0 text-success" />
                                <span>Your card on file will be charged</span>
                            </div>
                            <div className="flex items-start gap-2">
                                <Check className="mt-0.5 size-4 flex-shrink-0 text-success" />
                                <span>Cancel anytime if you're still not satisfied</span>
                            </div>
                            <div className="flex items-start gap-2">
                                <Check className="mt-0.5 size-4 flex-shrink-0 text-success" />
                                <span>This offer is only valid now</span>
                            </div>
                        </div>
                    </div>
                    <DialogFooter>
                        <div className="flex w-full flex-col gap-2">
                            <Button onClick={handleAcceptOffer} disabled={offerProcessing} className="w-full">
                                {offerProcessing ? (
                                    <>
                                        <LoaderCircle className="animate-spin" />
                                        Applying offer...
                                    </>
                                ) : (
                                    <>Claim my offer</>
                                )}
                            </Button>
                            <Button variant="ghost" onClick={handleRejectOffer} disabled={offerProcessing} className="w-full">
                                No thanks, continue cancelling
                            </Button>
                        </div>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

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
                                    <p>Keep access until your current billing cycle ends</p>
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
                            <Button variant="secondary" onClick={() => confirmCancel(false)} className="w-full">
                                Cancel at end of cycle
                            </Button>
                            <Button variant="destructive" onClick={() => confirmCancel(true)} className="w-full">
                                Cancel immediately
                            </Button>
                            <Button variant="ghost" onClick={() => setShowCancelDialog(false)} className="w-full">
                                Keep subscription
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
                            <div className="space-y-2 rounded-md bg-info-foreground p-3">
                                <p className="text-xs text-info">
                                    When you change your plan, the update happens right away. You’ll be charged only for the difference between your
                                    current plan and the new plan, based on how much time is left in your current billing period.
                                </p>
                                <p className="text-xs text-info">
                                    <span className="font-bold">Note about trials:</span> If you’re currently on a free trial, the trial will end and
                                    you’ll be charged the full price of the new plan immediately.
                                </p>
                                <p className="text-xs text-info">
                                    <span className="font-bold">Note about past-due/open invoices:</span> If you have an unpaid or past-due invoice,
                                    it will be canceled. A new billing cycle will start, and your plan will continue once payment for the new invoice
                                    is successful.
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
