import { AppFooter } from '@/components/app-footer';
import { AppHeader } from '@/components/app-header';
import { DiscordOnlineCount } from '@/components/discord-online-count';
import { EmptyState } from '@/components/empty-state';
import HeadingLarge from '@/components/heading-large';
import Loading from '@/components/loading';
import { RobloxMemberCount } from '@/components/roblox-member-count';
import { AbstractBackgroundPattern } from '@/components/ui/abstract-background-pattern';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { abbreviateNumber, cn, currency } from '@/lib/utils';
import { stripCharacters } from '@/utils/truncate';
import { Deferred, Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    CalendarSync,
    Check,
    ChevronDown,
    Gamepad2,
    Globe,
    MessageSquare,
    Package,
    Rocket,
    Shield,
    ShoppingCart,
    Star,
    UserPlus,
    Users,
} from 'lucide-react';
import { useState } from 'react';

interface HomeProps {
    subscriptions: App.Data.ProductData[];
}

const features = [
    {
        icon: Users,
        title: 'Member Management',
        description: 'Comprehensive member management with detailed profiles, statistics tracking, and powerful community moderation tools.',
        color: 'text-gaming-blue',
    },
    {
        icon: MessageSquare,
        title: 'Community Forums',
        description: 'Built-in forum system with real-time chat, announcements, and engaging discussion spaces for your community.',
        color: 'text-gaming-purple',
    },
    {
        icon: BarChart3,
        title: 'Analytics Dashboard',
        description:
            'Advanced analytics providing deep insights into member engagement and community growth to enhance our high-fidelity gaming experiences.',
        color: 'text-gaming-green',
    },
    {
        icon: Gamepad2,
        title: 'Game Integration',
        description: 'Advanced game development integration supporting high-fidelity experiences across platforms with custom server capabilities.',
        color: 'text-gaming-blue',
    },
    {
        icon: Shield,
        title: 'Security & Moderation',
        description: 'Advanced security systems ensuring fair play and safe gaming environments with robust anti-cheat and monitoring capabilities.',
        color: 'text-gaming-purple',
    },
    {
        icon: Globe,
        title: 'Global Infrastructure',
        description:
            'Worldwide server network delivering high-fidelity gaming experiences with low latency and high availability for members globally.',
        color: 'text-gaming-green',
    },
];

export default function Home({ subscriptions = [] }: HomeProps) {
    const page = usePage<App.Data.SharedData>();
    const { name, auth, memberCount, postCount, discordCount, discordOnlineCount, robloxCount, slogan, logoUrl } = page.props;

    return (
        <div className="min-h-screen bg-background text-foreground">
            <Head title="Home">
                <meta name="description" content={slogan || ''} />
                <meta property="og:title" content={name} />
                <meta property="og:description" content={slogan || ''} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <AppHeader />

            <main>
                <section className="relative overflow-x-hidden py-20 md:py-32">
                    <div className="pointer-events-none absolute inset-0">
                        <AbstractBackgroundPattern />
                    </div>

                    <div className="relative z-20 container mx-auto px-6 text-center sm:px-4">
                        <div className="mx-auto max-w-5xl">
                            <div className="mb-8 flex flex-wrap items-center justify-center gap-2">
                                <div className="inline-flex items-center rounded-full border border-border/40 bg-background px-3 py-1 text-sm text-muted-foreground">
                                    <Rocket className="text-gaming-blue mr-2 h-4 w-4" />
                                    Now powering {abbreviateNumber(memberCount)}+ members
                                </div>
                                {!!discordOnlineCount && (
                                    <div className="inline-flex items-center rounded-full border border-border/40 bg-background px-3 py-1 text-sm text-muted-foreground">
                                        <DiscordOnlineCount />
                                    </div>
                                )}
                                {!!robloxCount && (
                                    <div className="inline-flex items-center rounded-full border border-border/40 bg-background px-3 py-1 text-sm text-muted-foreground">
                                        <RobloxMemberCount />
                                    </div>
                                )}
                            </div>

                            <h1 className="mb-6 text-4xl font-bold tracking-tight text-balance md:text-6xl lg:text-7xl">
                                The number one online <span className="gradient-text-gaming">gaming community</span> platform
                            </h1>

                            <p className="mx-auto mb-8 max-w-2xl text-lg text-balance text-muted-foreground md:text-xl">
                                {name} creates an open gaming environment accessible to everyone, delivering high-fidelity games through our
                                exceptional game development expertise and building online communities.
                            </p>

                            <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                <Button size="lg">
                                    {auth.user ? (
                                        <Link href={route('dashboard')} className="flex items-center gap-2">
                                            <Rocket className="size-4" />
                                            My Dashboard
                                        </Link>
                                    ) : (
                                        <Link href={route('onboarding')} className="flex items-center gap-2">
                                            <UserPlus className="size-4" />
                                            Join The Community
                                        </Link>
                                    )}
                                </Button>
                                <Button variant="outline" size="lg">
                                    <Link href={route('store.index')} className="flex items-center gap-2">
                                        <ShoppingCart className="size-4" />
                                        Browse Store
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="border-y border-border/40 py-16">
                    <div className="container mx-auto px-6 sm:px-4">
                        <div className="grid grid-cols-2 gap-8 md:grid-cols-3 lg:grid-cols-6">
                            <div className="text-center">
                                <div className="text-gaming-purple mb-2 text-3xl font-bold md:text-4xl">{abbreviateNumber(memberCount)}+</div>
                                <div className="text-sm text-muted-foreground">Connected Members</div>
                            </div>
                            <div className="text-center">
                                <div className="text-gaming-purple mb-2 text-3xl font-bold md:text-4xl">{abbreviateNumber(discordCount)}+</div>
                                <div className="text-sm text-muted-foreground">Discord Members</div>
                            </div>
                            <div className="text-center">
                                <div className="text-gaming-purple mb-2 text-3xl font-bold md:text-4xl">{abbreviateNumber(robloxCount)}+</div>
                                <div className="text-sm text-muted-foreground">Roblox Members</div>
                            </div>
                            <div className="text-center">
                                <div className="text-gaming-blue mb-2 text-3xl font-bold md:text-4xl">{abbreviateNumber(postCount)}+</div>
                                <div className="text-sm text-muted-foreground">Active Discussions</div>
                            </div>
                            <div className="text-center">
                                <div className="text-gaming-green mb-2 text-3xl font-bold md:text-4xl">99.9%</div>
                                <div className="text-sm text-muted-foreground">Uptime</div>
                            </div>
                            <div className="text-center">
                                <div className="text-gaming-orange mb-2 text-3xl font-bold md:text-4xl">24/7</div>
                                <div className="text-sm text-muted-foreground">Support</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="features" className="py-20">
                    <div className="container mx-auto px-6 sm:px-4">
                        <div className="mx-auto mb-16 max-w-3xl text-center">
                            <HeadingLarge
                                title="Everything you need for thriving communities"
                                description="Our gaming platform provides all the essential tools needed to build thriving communities and deliver exceptional high-fidelity gaming experiences through advanced development capabilities."
                            />
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature, index) => {
                                const Icon = feature.icon;
                                return (
                                    <Card key={index} gradient className="h-full transition-all duration-300">
                                        <CardHeader>
                                            <Icon className={`mb-4 h-10 w-10 ${feature.color}`} />
                                            <CardTitle>{feature.title}</CardTitle>
                                            <CardDescription>{feature.description}</CardDescription>
                                        </CardHeader>
                                    </Card>
                                );
                            })}
                        </div>
                    </div>
                </section>

                <section className="py-20">
                    <div className="container mx-auto px-6 sm:px-4">
                        <div className="mx-auto mb-16 max-w-3xl text-center">
                            <HeadingLarge
                                title="Trusted by developers and members worldwide"
                                description="Members choose us for our commitment to open environments and exceptional development capabilities."
                            ></HeadingLarge>
                        </div>

                        <div className="grid gap-6 md:grid-cols-3">
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="mb-4 flex items-center">
                                        {[...Array(5)].map((_, i) => (
                                            <Star key={i} className="fill-gaming-orange text-gaming-orange h-4 w-4" />
                                        ))}
                                    </div>
                                    <p className="mb-4 text-muted-foreground">
                                        "The {name} platform revolutionized our community management. The high-fidelity game development tools are
                                        exceptional and our players love the seamless experience."
                                    </p>
                                    <div className="flex items-center">
                                        <div className="bg-gaming-blue mr-3 flex h-10 w-10 items-center justify-center rounded-full font-bold text-white">
                                            A
                                        </div>
                                        <div>
                                            <div className="font-semibold">Alex Chen</div>
                                            <div className="text-sm text-muted-foreground">Lead Developer, Pixel Studios</div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="pt-6">
                                    <div className="mb-4 flex items-center">
                                        {[...Array(5)].map((_, i) => (
                                            <Star key={i} className="fill-gaming-orange text-gaming-orange h-4 w-4" />
                                        ))}
                                    </div>
                                    <p className="mb-4 text-muted-foreground">
                                        "Their open gaming environment and advanced development capabilities helped us create high-fidelity
                                        experiences that grew our community from 1,000 to 50,000 players in 6 months."
                                    </p>
                                    <div className="flex items-center">
                                        <div className="bg-gaming-purple mr-3 flex h-10 w-10 items-center justify-center rounded-full font-bold text-white">
                                            S
                                        </div>
                                        <div>
                                            <div className="font-semibold">Sarah Johnson</div>
                                            <div className="text-sm text-muted-foreground">Community Manager, GameForge</div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="pt-6">
                                    <div className="mb-4 flex items-center">
                                        {[...Array(5)].map((_, i) => (
                                            <Star key={i} className="fill-gaming-orange text-gaming-orange h-4 w-4" />
                                        ))}
                                    </div>
                                    <p className="mb-4 text-muted-foreground">
                                        "Outstanding open gaming platform with exceptional development support. The seamless integration enabled us to
                                        deliver high-fidelity games and increased community engagement by 300%."
                                    </p>
                                    <div className="flex items-center">
                                        <div className="bg-gaming-green mr-3 flex h-10 w-10 items-center justify-center rounded-full font-bold text-white">
                                            M
                                        </div>
                                        <div>
                                            <div className="font-semibold">Mike Rodriguez</div>
                                            <div className="text-sm text-muted-foreground">Founder, Indie Game Collective</div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </section>

                <section id="pricing" className="relative overflow-x-hidden py-20">
                    <div className="pointer-events-none absolute inset-0">
                        <AbstractBackgroundPattern />
                    </div>
                    <div className="relative z-20 container mx-auto px-6 sm:px-4">
                        <div className="mx-auto mb-16 max-w-3xl text-center">
                            <h2 className="mb-4 text-3xl font-bold md:text-4xl">Simple, transparent pricing</h2>
                            <p className="text-lg text-muted-foreground">Choose the plan that fits you best. No long term contracts.</p>
                        </div>

                        <Deferred data="subscriptions" fallback={<Loading variant="grid" cols={3} />}>
                            {subscriptions && subscriptions.length > 0 ? (
                                <SubscriptionCards subscriptions={subscriptions} />
                            ) : (
                                <EmptyState
                                    icon={<Package />}
                                    title="No subscription plans available"
                                    description="We're currently working on our subscription offerings. Check back soon for exciting plans and features!"
                                />
                            )}
                        </Deferred>
                    </div>
                </section>

                <section className="bg-muted/20 py-20">
                    <div className="container mx-auto px-6 text-center sm:px-4">
                        <div className="mx-auto max-w-3xl">
                            <h2 className="mb-4 text-3xl font-bold text-balance md:text-4xl">Ready to join the community?</h2>
                            <p className="mb-8 text-lg text-balance text-muted-foreground">
                                Join thousands of members who trust {name} to deliver exceptional high-fidelity gaming experiences in our open,
                                accessible environment.
                            </p>
                            <div className="flex flex-col justify-center gap-4 sm:flex-row">
                                <Button size="lg" asChild>
                                    <Link href={route('forums.index')} className="flex items-center gap-2">
                                        Start Connecting
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                                <Button variant="outline" size="lg">
                                    <Link href={route('store.subscriptions')} className="flex items-center gap-2">
                                        <CalendarSync className="size-4" />
                                        Browse Subscriptions
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </section>
            </main>

            <AppFooter />
        </div>
    );
}

interface SubscriptionCardsProps {
    subscriptions: App.Data.ProductData[];
}

function SubscriptionCards({ subscriptions }: SubscriptionCardsProps) {
    const [expandedCards, setExpandedCards] = useState<Record<number, boolean>>({});

    const toggleExpanded = (subscriptionId: number) => {
        setExpandedCards((prev) => ({
            ...prev,
            [subscriptionId]: !prev[subscriptionId],
        }));
    };

    return (
        <div
            className={cn(
                'grid grid-cols-1 gap-6',
                subscriptions.length === 1
                    ? 'md:grid-cols-1'
                    : subscriptions.length === 2
                      ? 'md:grid-cols-2'
                      : subscriptions.length === 4
                        ? 'md:grid-cols-2 lg:grid-cols-4'
                        : 'md:grid-cols-2 lg:grid-cols-3',
            )}
        >
            {subscriptions.map((subscription) => {
                const defaultPrice = subscription.prices.find((price: App.Data.PriceData) => price.isDefault) ||
                    subscription.prices[0] || {
                        amount: 0,
                        interval: 'month',
                    };

                const features = subscription.metadata?.features || [];
                const isExpanded = expandedCards[subscription.id] || false;
                const displayedFeatures = isExpanded ? features : features.slice(0, 5);
                const hasMoreFeatures = features.length > 5;

                return (
                    <Card
                        gradient
                        key={subscription.id}
                        className={cn('flex flex-col justify-between bg-background sm:h-full', subscription.isFeatured && 'ring-2 ring-info')}
                    >
                        {subscription.isFeatured && (
                            <div className="absolute -top-4 left-1/2 z-10 -translate-x-1/2">
                                <Badge variant="default" className="bg-info text-info-foreground">
                                    Featured
                                </Badge>
                            </div>
                        )}
                        <CardHeader>
                            {subscription.featuredImageUrl && (
                                <img
                                    alt={subscription.name}
                                    src={subscription.featuredImageUrl}
                                    className="mb-4 aspect-[16/9] w-full rounded-2xl bg-muted object-cover"
                                />
                            )}
                            <CardTitle>{subscription.name}</CardTitle>
                            {subscription.description && <CardDescription>{stripCharacters(subscription.description)}</CardDescription>}
                            {defaultPrice && (
                                <div className="mt-4 text-3xl font-bold">
                                    {currency(defaultPrice.amount, false)}
                                    <span className="text-lg font-normal text-muted-foreground"> / {defaultPrice.interval}</span>
                                </div>
                            )}
                        </CardHeader>
                        <CardContent className="flex flex-1 flex-col">
                            {features.length > 0 && (
                                <div className="mb-4 space-y-3">
                                    <h4 className="text-sm font-semibold tracking-wide text-muted-foreground uppercase">Features included</h4>
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
                            <div className="mt-auto space-y-2 pt-4">
                                {hasMoreFeatures && (
                                    <Button variant="ghost" size="sm" onClick={() => toggleExpanded(subscription.id)} className="w-full">
                                        {isExpanded ? (
                                            <>
                                                View less
                                                <ChevronDown className="size-4 rotate-180 transition-transform" />
                                            </>
                                        ) : (
                                            <>
                                                View {features.length - 5} more
                                                <ChevronDown className="size-4 transition-transform" />
                                            </>
                                        )}
                                    </Button>
                                )}
                                <Button className="mt-auto w-full bg-transparent" variant="outline" asChild>
                                    <Link href={route('store.subscriptions')}>Get started</Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                );
            })}
        </div>
    );
}
