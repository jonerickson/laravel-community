import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import RichEditorContent from '@/components/rich-editor-content';
import { StarRating } from '@/components/star-rating';
import { StoreProductReviewsList } from '@/components/store-product-reviews-list';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { abbreviateNumber, cn, currency, pluralize } from '@/lib/utils';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, MessageSquare, Star } from 'lucide-react';
import { useState } from 'react';
import SharedData = App.Data.SharedData;

interface ReviewsPageProps {
    subscription: App.Data.ProductData;
    reviews: App.Data.PaginatedData<App.Data.CommentData>;
}

export default function Reviews({ subscription, reviews }: ReviewsPageProps) {
    const { name: siteName, auth, logoUrl } = usePage<SharedData>().props;
    const [rating, setRating] = useState(0);
    const [hoverRating, setHoverRating] = useState(0);

    const { data, setData, post, processing, reset, errors } = useForm({
        content: '',
        rating: 0,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        post(route('store.subscriptions.reviews.store', subscription.referenceId), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setRating(0);
            },
        });
    };

    const defaultPrice = subscription.prices.find((price: App.Data.PriceData) => price.isDefault) || subscription.prices[0];

    return (
        <AppLayout>
            <Head title={`${subscription.name} - Reviews`}>
                <meta name="description" content={subscription.description || ''} />
                <meta property="og:title" content={`${subscription.name} - Reviews - ${siteName}`} />
                <meta property="og:description" content={subscription.description || ''} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={subscription.featuredImageUrl || logoUrl} />
            </Head>

            <div className="mx-auto w-full max-w-4xl space-y-6">
                <Button variant="ghost" size="sm" onClick={() => router.visit(route('store.subscriptions'))} className="mb-4">
                    <ArrowLeft />
                    Back to subscriptions
                </Button>

                <Card>
                    <CardHeader>
                        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div className="flex-1">
                                <CardTitle className="text-2xl">{subscription.name}</CardTitle>
                                {subscription.description && (
                                    <CardDescription className="mt-2">
                                        <RichEditorContent content={subscription.description} />
                                    </CardDescription>
                                )}
                                {defaultPrice && (
                                    <div className="mt-4 flex items-baseline gap-1">
                                        <span className="text-2xl font-bold">{currency(defaultPrice.amount)}</span>
                                        <span className="text-muted-foreground">/ {defaultPrice.interval}</span>
                                    </div>
                                )}
                            </div>
                            <div className="flex flex-col items-start gap-2 md:items-end">
                                <StarRating rating={subscription.averageRating || 0} showValue={true} size="lg" />
                                <span className="text-sm text-muted-foreground">
                                    {abbreviateNumber(reviews.data.length)} {pluralize('review', reviews.data.length)}
                                </span>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {auth?.user && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Write a review</CardTitle>
                            <CardDescription>Share your experience with this subscription</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <label className="text-sm font-medium">Rating</label>
                                    <div className="flex items-center gap-1">
                                        {Array.from({ length: 5 }, (_, index) => {
                                            const starValue = index + 1;
                                            const isActive = (hoverRating || rating) >= starValue;

                                            return (
                                                <button
                                                    key={index}
                                                    type="button"
                                                    className="p-1 transition-transform hover:scale-110"
                                                    onMouseEnter={() => setHoverRating(starValue)}
                                                    onMouseLeave={() => setHoverRating(0)}
                                                    onClick={() => {
                                                        setRating(starValue);
                                                        setData('rating', starValue);
                                                    }}
                                                >
                                                    <Star
                                                        className={cn(
                                                            'h-7 w-7',
                                                            isActive
                                                                ? 'fill-yellow-400 text-yellow-400'
                                                                : 'text-muted-foreground hover:text-yellow-400',
                                                        )}
                                                    />
                                                </button>
                                            );
                                        })}
                                        {rating > 0 && (
                                            <span className="ml-2 text-sm text-muted-foreground">
                                                {rating} {pluralize('star', rating)}
                                            </span>
                                        )}
                                    </div>
                                    {errors.rating && <p className="text-sm text-destructive">{errors.rating}</p>}
                                </div>

                                <div className="space-y-2">
                                    <label htmlFor="content" className="text-sm font-medium">
                                        Review
                                    </label>
                                    <Textarea
                                        id="content"
                                        value={data.content}
                                        onChange={(e) => setData('content', e.target.value)}
                                        placeholder="Share your thoughts about this subscription..."
                                        rows={4}
                                        className={cn('mt-1', {
                                            'border-destructive': errors.content,
                                        })}
                                    />
                                    {errors.content && <p className="text-sm text-destructive">{errors.content}</p>}
                                </div>

                                <Button type="submit" disabled={processing || rating === 0} className="w-full">
                                    {processing && <LoaderCircle className="animate-spin" />}
                                    {processing ? 'Submitting...' : 'Submit review'}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <Heading title="Customer reviews" description={`See what others are saying about ${subscription.name}`} />
                    </CardHeader>
                    <CardContent>
                        {reviews && reviews.data.length > 0 ? (
                            <StoreProductReviewsList reviews={reviews} scrollProp="reviews" />
                        ) : (
                            <EmptyState icon={<MessageSquare />} title="No reviews yet" description="Be the first to review this subscription!" />
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
