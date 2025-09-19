import { AppFooter } from '@/components/app-footer';
import { AppHeader } from '@/components/app-header';
import HeadingLarge from '@/components/heading-large';
import { AbstractBackgroundPattern } from '@/components/ui/abstract-background-pattern';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    CalendarSync,
    CheckCircle,
    Gamepad2,
    Globe,
    MessageSquare,
    Rocket,
    Shield,
    ShoppingCart,
    Star,
    UserPlus,
    Users,
} from 'lucide-react';

export default function Home() {
    const { name } = usePage<App.Data.SharedData>().props;

    return (
        <div className="min-h-screen bg-background text-foreground">
            <AppHeader />

            <main>
                <section className="relative py-20 md:py-32">
                    <div className="pointer-events-none absolute right-0 bottom-0 z-10">
                        <AbstractBackgroundPattern className="h-[800px] w-[1000px]" corner="bottom-right" />
                    </div>

                    <div className="container mx-auto px-6 text-center sm:px-4">
                        <div className="mx-auto max-w-5xl">
                            <div className="mb-8 inline-flex items-center rounded-full border border-border/40 bg-muted/20 px-3 py-1 text-sm text-muted-foreground">
                                <Rocket className="text-gaming-blue mr-2 h-4 w-4" />
                                Now powering 10,000+ members
                            </div>

                            <h1 className="mb-6 text-4xl font-bold tracking-tight text-balance md:text-6xl lg:text-7xl">
                                The number one online <span className="gradient-text-gaming">gaming community</span> platform
                            </h1>

                            <p className="mx-auto mb-8 max-w-2xl text-lg text-balance text-muted-foreground md:text-xl">
                                {name} creates an open gaming environment accessible to everyone, delivering high-fidelity games through our
                                exceptional game development expertise and building online communities.
                            </p>

                            <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                <Button size="lg" className="glow-blue">
                                    <Link href={route('forums.index')} className="flex items-center gap-2">
                                        <UserPlus className="size-4" />
                                        Join The Community
                                    </Link>
                                </Button>
                                <Button variant="outline" size="lg">
                                    <Link href={route('forums.index')} className="flex items-center gap-2">
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
                        <div className="grid grid-cols-2 gap-8 md:grid-cols-4">
                            <div className="text-center">
                                <div className="text-gaming-blue mb-2 text-3xl font-bold md:text-4xl">10K+</div>
                                <div className="text-sm text-muted-foreground">Active Communities</div>
                            </div>
                            <div className="text-center">
                                <div className="text-gaming-purple mb-2 text-3xl font-bold md:text-4xl">2M+</div>
                                <div className="text-sm text-muted-foreground">Connected Players</div>
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
                        <div className="mb-16 text-center">
                            <HeadingLarge
                                title="Everything you need for gaming communities"
                                description="Our gaming platform provides all the essential tools needed to build thriving communities and deliver exceptional high-fidelity gaming experiences through advanced development capabilities."
                            />
                        </div>

                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            <Card className="glow-blue/20 hover:glow-blue transition-all duration-300">
                                <CardHeader>
                                    <Users className="text-gaming-blue mb-4 h-10 w-10" />
                                    <CardTitle>Player Management</CardTitle>
                                    <CardDescription>
                                        Comprehensive player management with detailed profiles, statistics tracking, and powerful community moderation
                                        tools.
                                    </CardDescription>
                                </CardHeader>
                            </Card>

                            <Card className="glow-purple/20 hover:glow-purple transition-all duration-300">
                                <CardHeader>
                                    <MessageSquare className="text-gaming-purple mb-4 h-10 w-10" />
                                    <CardTitle>Community Forums</CardTitle>
                                    <CardDescription>
                                        Built-in forum system with real-time chat, announcements, and engaging discussion spaces for your community.
                                    </CardDescription>
                                </CardHeader>
                            </Card>

                            <Card className="glow-green/20 hover:glow-green transition-all duration-300">
                                <CardHeader>
                                    <BarChart3 className="text-gaming-green mb-4 h-10 w-10" />
                                    <CardTitle>Analytics Dashboard</CardTitle>
                                    <CardDescription>
                                        Advanced analytics providing deep insights into player engagement and community growth to enhance our
                                        high-fidelity gaming experiences.
                                    </CardDescription>
                                </CardHeader>
                            </Card>

                            <Card className="glow-blue/20 hover:glow-blue transition-all duration-300">
                                <CardHeader>
                                    <Gamepad2 className="text-gaming-blue mb-4 h-10 w-10" />
                                    <CardTitle>Game Integration</CardTitle>
                                    <CardDescription>
                                        Advanced game development integration supporting high-fidelity experiences across platforms with custom server
                                        capabilities.
                                    </CardDescription>
                                </CardHeader>
                            </Card>

                            <Card className="glow-purple/20 hover:glow-purple transition-all duration-300">
                                <CardHeader>
                                    <Shield className="text-gaming-purple mb-4 h-10 w-10" />
                                    <CardTitle>Security & Moderation</CardTitle>
                                    <CardDescription>
                                        Advanced security systems ensuring fair play and safe gaming environments with robust anti-cheat and
                                        monitoring capabilities.
                                    </CardDescription>
                                </CardHeader>
                            </Card>

                            <Card className="glow-green/20 hover:glow-green transition-all duration-300">
                                <CardHeader>
                                    <Globe className="text-gaming-green mb-4 h-10 w-10" />
                                    <CardTitle>Global Infrastructure</CardTitle>
                                    <CardDescription>
                                        Worldwide server network delivering high-fidelity gaming experiences with low latency and high availability
                                        for players globally.
                                    </CardDescription>
                                </CardHeader>
                            </Card>
                        </div>
                    </div>
                </section>

                <section className="bg-muted/20 py-20">
                    <div className="container mx-auto px-6 sm:px-4">
                        <div className="mb-16 text-center">
                            <HeadingLarge
                                title="Trusted by developers and players worldwide"
                                description="Developers choose us for our commitment to open gaming environments and exceptional development capabilities"
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
                                        "Mountain Interactive's platform revolutionized our community management. The high-fidelity game development
                                        tools are exceptional and our players love the seamless experience."
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

                <section id="pricing" className="py-20">
                    <div className="container mx-auto px-6 sm:px-4">
                        <div className="mb-16 text-center">
                            <h2 className="mb-4 text-3xl font-bold md:text-4xl">Simple, transparent pricing</h2>
                            <p className="text-lg text-muted-foreground">Choose the plan that fits your community size</p>
                        </div>

                        <div className="mx-auto grid max-w-5xl gap-6 md:grid-cols-3">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Starter</CardTitle>
                                    <CardDescription>Perfect for small communities</CardDescription>
                                    <div className="mt-4 text-3xl font-bold">
                                        $29<span className="text-lg font-normal text-muted-foreground">/month</span>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-3">
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Up to 1,000 players
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Basic analytics
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Community forums
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Email support
                                        </li>
                                    </ul>
                                    <Button className="mt-6 w-full bg-transparent" variant="outline">
                                        Get Started
                                    </Button>
                                </CardContent>
                            </Card>

                            <Card className="border-gaming-blue glow-blue">
                                <CardHeader>
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <CardTitle>Professional</CardTitle>
                                            <CardDescription>Most popular choice</CardDescription>
                                        </div>
                                        <div className="bg-gaming-blue rounded px-2 py-1 text-xs font-semibold text-white">Popular</div>
                                    </div>
                                    <div className="mt-4 text-3xl font-bold">
                                        $99<span className="text-lg font-normal text-muted-foreground">/month</span>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-3">
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Up to 10,000 players
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Advanced analytics
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Custom integrations
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Priority support
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            API access
                                        </li>
                                    </ul>
                                    <Button className="mt-6 w-full">Get Started</Button>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle>Enterprise</CardTitle>
                                    <CardDescription>For large gaming studios</CardDescription>
                                    <div className="mt-4 text-3xl font-bold">Custom</div>
                                </CardHeader>
                                <CardContent>
                                    <ul className="space-y-3">
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Unlimited players
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Custom features
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            Dedicated support
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            SLA guarantee
                                        </li>
                                        <li className="flex items-center">
                                            <CheckCircle className="text-gaming-green mr-2 h-4 w-4" />
                                            White-label options
                                        </li>
                                    </ul>
                                    <Button className="mt-6 w-full bg-transparent" variant="outline">
                                        Contact Sales
                                    </Button>
                                </CardContent>
                            </Card>
                        </div>
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
                                <Button size="lg" className="glow-blue" asChild>
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
