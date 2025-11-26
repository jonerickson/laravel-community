import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import KnowledgeBaseArticleCard from '@/components/knowledge-base-article-card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Search, SearchX } from 'lucide-react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface KnowledgeBaseSearchProps {
    results: App.Data.KnowledgeBaseArticleData[];
    query: string;
}

export default function KnowledgeBaseSearch({ results, query: initialQuery }: KnowledgeBaseSearchProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const [searchQuery, setSearchQuery] = useState(initialQuery || '');
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Knowledge Base',
            href: route('knowledge-base.index'),
        },
        {
            title: 'Search',
            href: route('knowledge-base.search'),
        },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        if (searchQuery.trim()) {
            router.visit(route('knowledge-base.search', { q: searchQuery }), {
                preserveState: true,
                preserveScroll: false,
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Search: ${initialQuery ? `${initialQuery} - Knowledge base` : 'Knowledge base'}`}>
                <meta name="description" content={`Search results for "${initialQuery}" in the ${siteName} knowledge base`} />
                <meta property="og:title" content={`Search: ${initialQuery} - Knowledge Base - ${siteName}`} />
                <meta property="og:description" content={`Search results for "${initialQuery}" in the ${siteName} knowledge base`} />
                <meta property="og:image" content={logoUrl} />
            </Head>

            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto">
                <Heading
                    title="Search knowledge base"
                    description={initialQuery ? `Results for "${initialQuery}"` : 'Search our documentation and articles'}
                />

                <div className="-mt-8 flex flex-col gap-6">
                    <div className="flex flex-col gap-4">
                        <form onSubmit={handleSearch} className="flex flex-col gap-4 sm:flex-row sm:gap-2">
                            <div className="relative flex-1">
                                <Input
                                    type="search"
                                    placeholder="Search articles..."
                                    value={searchQuery}
                                    onChange={(e) => setSearchQuery(e.target.value)}
                                    autoFocus
                                />
                            </div>
                            <Button type="submit">Search</Button>
                        </form>

                        {initialQuery && results && results.length > 0 && (
                            <>
                                <p className="mb-2 text-sm text-muted-foreground">
                                    Found {results.length} {results.length === 1 ? 'article' : 'articles'}
                                </p>
                                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {results.map((article) => (
                                        <KnowledgeBaseArticleCard key={article.id} article={article} />
                                    ))}
                                </div>
                            </>
                        )}
                    </div>

                    {initialQuery && results && results.length === 0 && (
                        <EmptyState
                            icon={<SearchX />}
                            title="No results found"
                            description={`No articles found for "${initialQuery}". Try different keywords or browse all articles.`}
                        />
                    )}

                    {!initialQuery && (
                        <EmptyState
                            icon={<Search />}
                            title="Search our knowledge base"
                            description="Enter keywords to search through our documentation and articles"
                        />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
