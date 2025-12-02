import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import KnowledgeBaseArticleCard from '@/components/knowledge-base-article-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, InfiniteScroll, router, usePage } from '@inertiajs/react';
import { BookOpen } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface KnowledgeBaseIndexProps {
    articles: App.Data.PaginatedData<App.Data.KnowledgeBaseArticleData>;
    categories: App.Data.KnowledgeBaseCategoryData[];
    filters: {
        category?: string;
        type?: string;
    };
}

export default function KnowledgeBaseIndex({ articles, categories, filters }: KnowledgeBaseIndexProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const [searchQuery, setSearchQuery] = useState('');
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Knowledge Base',
            href: route('knowledge-base.index'),
        },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            router.visit(route('knowledge-base.search', { q: searchQuery }));
        }
    };

    const handleFilterChange = (key: string, value: string | null) => {
        const params = new URLSearchParams(window.location.search);

        if (value && value !== 'all') {
            params.set(key, value);
        } else {
            params.delete(key);
        }

        router.visit(route('knowledge-base.index') + (params.toString() ? `?${params.toString()}` : ''), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'CollectionPage',
        name: `${siteName} Knowledge Base`,
        description: 'Browse our knowledge base articles and documentation',
        url: route('knowledge-base.index'),
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Knowledge base">
                <meta name="description" content={`Browse our knowledge base articles and documentation from ${siteName}`} />
                <meta property="og:title" content={`Knowledge Base - ${siteName}`} />
                <meta property="og:description" content={`Browse our knowledge base articles and documentation from ${siteName}`} />
                <meta property="og:type" content="website" />
                <meta property="og:image" content={logoUrl} />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                <Heading title="Knowledge base" description="Browse our articles and documentation" />

                <div className="-mt-8 flex flex-col gap-6">
                    <div className="flex flex-col gap-4">
                        <form onSubmit={handleSearch} className="flex flex-col gap-4 sm:flex-row sm:gap-2">
                            <div className="relative flex-1">
                                <Input
                                    type="search"
                                    placeholder="Search articles..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                />
                            </div>
                            <Button type="submit">Search</Button>
                        </form>

                        <div className="flex flex-col gap-4 sm:flex-row">
                            <Select value={filters.category || 'all'} onValueChange={(value) => handleFilterChange('category', value)}>
                                <SelectTrigger className="sm:w-[200px]">
                                    <SelectValue placeholder="All categories" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All categories</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={category.id.toString()}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={filters.type || 'all'} onValueChange={(value) => handleFilterChange('type', value)}>
                                <SelectTrigger className="sm:w-[200px]">
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
                                    <SelectItem value="guide">Guide</SelectItem>
                                    <SelectItem value="faq">FAQ</SelectItem>
                                    <SelectItem value="changelog">Changelog</SelectItem>
                                    <SelectItem value="troubleshooting">Troubleshooting</SelectItem>
                                    <SelectItem value="announcement">Announcement</SelectItem>
                                    <SelectItem value="other">Other</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {articles.data.length > 0 ? (
                        <InfiniteScroll data="articles" loading={() => 'Loading more articles...'}>
                            <div className="-mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                                {articles.data.map((article) => (
                                    <KnowledgeBaseArticleCard key={article.id} article={article} />
                                ))}
                            </div>
                        </InfiniteScroll>
                    ) : (
                        <EmptyState icon={<BookOpen />} title="No articles found" description="Try adjusting your filters or check back later." />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
