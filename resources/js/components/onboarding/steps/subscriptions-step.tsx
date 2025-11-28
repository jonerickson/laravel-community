import { useState } from 'react';

import RichEditorContent from '@/components/rich-editor-content';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { currency } from '@/lib/utils';
import { LoaderCircle } from 'lucide-react';

type SubscriptionsStepProps = {
    subscriptions: App.Data.ProductData[];
    hasSubscription: boolean;
    processing: boolean;
    onStartSubscription: (priceId: number) => void;
    onNext: () => void;
    onPrevious: () => void;
    title?: string;
    description?: string;
};

export function SubscriptionsStep({
    subscriptions,
    hasSubscription,
    processing,
    onStartSubscription,
    onNext,
    onPrevious,
    title,
    description,
}: SubscriptionsStepProps) {
    const [processingSubscriptionId, setProcessingSubscriptionId] = useState<number | null>(null);

    const handleSelectPlan = (productId: number, priceId: number) => {
        setProcessingSubscriptionId(productId);
        onStartSubscription(priceId);
    };
    return (
        <div className="flex flex-col gap-6">
            {(title || description) && (
                <div className="flex flex-col gap-2">
                    {title && <h3 className="text-lg font-semibold">{title}</h3>}
                    {description && <p className="text-sm text-muted-foreground">{description}</p>}
                </div>
            )}

            {hasSubscription ? (
                <div className="rounded-lg border bg-card p-8 text-center">
                    <div className="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-primary/10">
                        <svg className="size-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 className="mb-2 text-xl font-semibold">You're all set!</h3>
                    <p className="text-sm text-muted-foreground">
                        You already have an active subscription. You can manage your subscription from your account settings.
                    </p>
                </div>
            ) : subscriptions.length > 0 ? (
                <div className="grid grid-cols-1 gap-4">
                    {subscriptions.map((subscription) => {
                        const defaultPrice = subscription.prices.find((price: App.Data.PriceData) => price.isDefault) || subscription.prices[0];

                        return (
                            <Card key={subscription.id}>
                                <CardHeader className="text-center">
                                    <CardTitle className="text-xl">{subscription.name}</CardTitle>
                                    {subscription.description && (
                                        <CardDescription>
                                            <RichEditorContent content={subscription.description} />
                                        </CardDescription>
                                    )}
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="text-center">
                                        {defaultPrice && (
                                            <div className="text-3xl font-bold">
                                                {currency(defaultPrice.amount, false)}
                                                <span className="text-base font-normal text-muted-foreground"> / {defaultPrice.interval}</span>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                                <CardFooter>
                                    <Button
                                        type="button"
                                        onClick={() => handleSelectPlan(subscription.id, defaultPrice?.id ?? 0)}
                                        disabled={processing || !defaultPrice}
                                        className="w-full"
                                    >
                                        {processing && processingSubscriptionId === subscription.id ? (
                                            <>
                                                <LoaderCircle className="animate-spin" />
                                                Processing...
                                            </>
                                        ) : !defaultPrice ? (
                                            'Not available'
                                        ) : (
                                            'Choose plan'
                                        )}
                                    </Button>
                                </CardFooter>
                            </Card>
                        );
                    })}
                </div>
            ) : (
                <div className="rounded-lg border bg-card p-6 text-left">
                    <p className="text-sm text-muted-foreground">
                        <strong className="font-medium text-foreground">No available subscriptions</strong>
                        <br />
                        Check back later for more product options.
                    </p>
                </div>
            )}

            <div className="flex flex-col gap-3 sm:flex-row">
                <Button type="button" variant="outline" onClick={onPrevious} className="flex-1">
                    Back
                </Button>
                {hasSubscription ? (
                    <Button type="button" onClick={onNext} className="flex-1">
                        Finish onboarding
                    </Button>
                ) : (
                    <Button type="button" onClick={onNext} className="flex-1">
                        Skip & finish onboarding
                    </Button>
                )}
            </div>
        </div>
    );
}
