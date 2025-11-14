import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { currency } from '@/lib/utils';
import { Head, router } from '@inertiajs/react';
import { Calendar, FileText, MessageSquare, Search as SearchIcon, Shield, ShoppingBag, User } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface SearchResult {
    id: number;
    type: 'policy' | 'product' | 'post' | 'topic' | 'user';
    title: string;
    description?: string;
    excerpt?: string;
    version?: string;
    price?: string;
    url: string;
    forum_name?: string;
    category_name?: string;
    author_name?: string;
    post_type?: string;
    effective_at?: string;
    created_at?: string;
    updated_at?: string;
}

interface Filters {
    types: string[];
    sort_by: string;
    sort_order: string;
    per_page: number;
    created_after: string | null;
    created_before: string | null;
    updated_after: string | null;
    updated_before: string | null;
}

interface Counts {
    topics: number;
    posts: number;
    policies: number;
    products: number;
    users: number;
}

interface Props {
    results: App.Data.PaginatedData<SearchResult>;
    query: string;
    filters: Filters;
    counts: Counts;
}

const typeIcons = {
    topic: MessageSquare,
    post: FileText,
    policy: Shield,
    product: ShoppingBag,
    user: User,
};

const typeLabels = {
    topic: 'Topic',
    post: 'Post',
    policy: 'Policy',
    product: 'Product',
    user: 'Member',
};

export default function Search({ results, query: initialQuery, filters: initialFilters, counts }: Props) {
    const [query, setQuery] = useState(initialQuery);
    const [types, setTypes] = useState<string[]>(initialFilters.types);
    const [sortBy, setSortBy] = useState(initialFilters.sort_by);
    const [sortOrder, setSortOrder] = useState(initialFilters.sort_order);
    const [perPage, setPerPage] = useState(initialFilters.per_page);
    const [createdAfter, setCreatedAfter] = useState(initialFilters.created_after || '');
    const [createdBefore, setCreatedBefore] = useState(initialFilters.created_before || '');
    const [updatedAfter, setUpdatedAfter] = useState(initialFilters.updated_after || '');
    const [updatedBefore, setUpdatedBefore] = useState(initialFilters.updated_before || '');

    const handleSearch = (e?: FormEvent) => {
        if (e) {
            e.preventDefault();
        }

        router.get(
            route('search'),
            {
                q: query,
                types,
                sort_by: sortBy,
                sort_order: sortOrder,
                per_page: perPage,
                created_after: createdAfter || undefined,
                created_before: createdBefore || undefined,
                updated_after: updatedAfter || undefined,
                updated_before: updatedBefore || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const toggleType = (type: string) => {
        setTypes((prev) => {
            const newTypes = prev.includes(type) ? prev.filter((t) => t !== type) : [...prev, type];
            return newTypes.length === 0 ? [type] : newTypes;
        });
    };

    const clearFilters = () => {
        setCreatedAfter('');
        setCreatedBefore('');
        setUpdatedAfter('');
        setUpdatedBefore('');
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString();
    };

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (query !== initialQuery || types.toString() !== initialFilters.types.toString()) {
                handleSearch();
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [query, types]);

    return (
        <AppLayout>
            <Head title="Search" />

            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <Heading title="Search" description="Search across topics, posts, policies, products, and members" />

                <div className="grid gap-6 lg:grid-cols-[280px_1fr]">
                    <aside className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Filters</CardTitle>
                                <CardDescription>Refine your search results</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Content:</Label>
                                    </div>
                                    <div className="space-y-2">
                                        {['policy', 'post', 'product', 'topic', 'user'].map((type) => (
                                            <div key={type} className="flex items-center gap-2">
                                                <Checkbox
                                                    id={`type-${type}`}
                                                    checked={types.includes(type)}
                                                    onCheckedChange={() => toggleType(type)}
                                                />
                                                <Label htmlFor={`type-${type}`} className="flex items-center gap-2 text-sm font-normal">
                                                    {typeLabels[type as keyof typeof typeLabels]}
                                                    <span className="text-xs text-muted-foreground">({counts[`${type}s` as keyof Counts]})</span>
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <Separator />

                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Sorting:</Label>
                                    </div>
                                    <Select value={sortBy} onValueChange={setSortBy}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="relevance">Relevance</SelectItem>
                                            <SelectItem value="created_at">Date created</SelectItem>
                                            <SelectItem value="updated_at">Date updated</SelectItem>
                                            <SelectItem value="title">Title</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <Select value={sortOrder} onValueChange={setSortOrder}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="desc">Descending</SelectItem>
                                            <SelectItem value="asc">Ascending</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <Separator />

                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Dates:</Label>
                                        {(createdAfter || createdBefore || updatedAfter || updatedBefore) && (
                                            <Button variant="ghost" size="sm" onClick={clearFilters} className="h-auto p-0 text-xs">
                                                Clear
                                            </Button>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <div className="space-y-1">
                                            <Label htmlFor="created-after" className="text-xs">
                                                Created after:
                                            </Label>
                                            <Input
                                                id="created-after"
                                                type="date"
                                                value={createdAfter}
                                                onChange={(e) => setCreatedAfter(e.target.value)}
                                                className="h-8 text-xs"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label htmlFor="created-before" className="text-xs">
                                                Created before:
                                            </Label>
                                            <Input
                                                id="created-before"
                                                type="date"
                                                value={createdBefore}
                                                onChange={(e) => setCreatedBefore(e.target.value)}
                                                className="h-8 text-xs"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label htmlFor="updated-after" className="text-xs">
                                                Updated after:
                                            </Label>
                                            <Input
                                                id="updated-after"
                                                type="date"
                                                value={updatedAfter}
                                                onChange={(e) => setUpdatedAfter(e.target.value)}
                                                className="h-8 text-xs"
                                            />
                                        </div>
                                        <div className="space-y-1">
                                            <Label htmlFor="updated-before" className="text-xs">
                                                Updated before:
                                            </Label>
                                            <Input
                                                id="updated-before"
                                                type="date"
                                                value={updatedBefore}
                                                onChange={(e) => setUpdatedBefore(e.target.value)}
                                                className="h-8 text-xs"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <div className="space-y-3">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm font-medium">Results:</Label>
                                    </div>
                                    <Select value={perPage.toString()} onValueChange={(v) => setPerPage(parseInt(v))}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="10">10</SelectItem>
                                            <SelectItem value="20">20</SelectItem>
                                            <SelectItem value="30">30</SelectItem>
                                            <SelectItem value="50">50</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <Button onClick={() => handleSearch()} className="w-full">
                                    Apply filters
                                </Button>
                            </CardContent>
                        </Card>
                    </aside>

                    <div className="space-y-6">
                        <form onSubmit={handleSearch}>
                            <Input
                                type="search"
                                placeholder="Search policies, posts, products, topics and members..."
                                value={query}
                                onChange={(e) => setQuery(e.target.value)}
                            />
                        </form>

                        {results.lastPage > 1 && (
                            <Pagination
                                pagination={results}
                                baseUrl={route('search', {
                                    q: query,
                                    types,
                                    sort_by: sortBy,
                                    sort_order: sortOrder,
                                    per_page: perPage,
                                    created_after: createdAfter || undefined,
                                    created_before: createdBefore || undefined,
                                    updated_after: updatedAfter || undefined,
                                    updated_before: updatedBefore || undefined,
                                })}
                                entityLabel="result"
                            />
                        )}

                        <div className="space-y-4">
                            {results.data.length === 0 && initialQuery && (
                                <EmptyState
                                    icon={<SearchIcon />}
                                    title="No results found"
                                    description="Try adjusting your search query or filters to find what you're looking for."
                                />
                            )}

                            {results.data.length === 0 && !initialQuery && (
                                <EmptyState
                                    icon={<SearchIcon />}
                                    title="Start searching"
                                    description="Enter a search query above to find topics, posts, policies, products, and members."
                                />
                            )}

                            {results.data.map((result) => {
                                const Icon = typeIcons[result.type];
                                return (
                                    <Card key={`${result.type}-${result.id}`} className="transition-shadow hover:shadow-md">
                                        <CardContent>
                                            <div className="flex gap-4">
                                                <div className="flex-shrink-0">
                                                    <div className="flex size-10 items-center justify-center rounded-lg bg-muted">
                                                        <Icon className="size-5 text-muted-foreground" />
                                                    </div>
                                                </div>
                                                <div className="flex-1 space-y-2">
                                                    <div className="flex items-start justify-between gap-4">
                                                        <div className="flex-1">
                                                            <a href={result.url} className="group">
                                                                <HeadingSmall title={result.title} />
                                                            </a>
                                                        </div>
                                                    </div>

                                                    {result.description && <p className="text-sm text-muted-foreground">{result.description}</p>}

                                                    {result.excerpt && <p className="text-sm text-muted-foreground">{result.excerpt}</p>}

                                                    {result.price && (
                                                        <div className="text-lg font-semibold text-primary">{currency(result.price)}</div>
                                                    )}

                                                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                                        {result.author_name && (
                                                            <div className="flex items-center gap-1">
                                                                <User className="size-3" />
                                                                {result.author_name}
                                                            </div>
                                                        )}

                                                        {result.forum_name && <div>in {result.forum_name}</div>}

                                                        {result.category_name && <div>in {result.category_name}</div>}

                                                        {result.version && <div>v{result.version}</div>}

                                                        {(result.effective_at || result.created_at) && (
                                                            <div className="flex items-center gap-1">
                                                                <Calendar className="size-3" />
                                                                {formatDate(result.effective_at || result.created_at || '')}
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>

                        {results.lastPage > 1 && (
                            <Pagination
                                pagination={results}
                                baseUrl={route('search', {
                                    q: query,
                                    types,
                                    sort_by: sortBy,
                                    sort_order: sortOrder,
                                    per_page: perPage,
                                    created_after: createdAfter || undefined,
                                    created_before: createdBefore || undefined,
                                    updated_after: updatedAfter || undefined,
                                    updated_before: updatedBefore || undefined,
                                })}
                                entityLabel="result"
                            />
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
