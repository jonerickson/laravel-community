import { EmptyState } from '@/components/empty-state';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import RichEditorContent from '@/components/rich-editor-content';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Pagination } from '@/components/ui/pagination';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import AppLayout from '@/layouts/app-layout';
import { cn, currency } from '@/lib/utils';
import { Head, useForm } from '@inertiajs/react';
import { Calendar, FileText, LoaderCircle, MessageSquare, Search as SearchIcon, Shield, ShoppingBag, User } from 'lucide-react';
import { FormEvent, useEffect } from 'react';

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
    results: App.Data.PaginatedData<App.Data.SearchResultData>;
    query: string;
    filters: Filters;
    counts: Counts;
}

const typeIcons: Record<App.Data.SearchResultData['type'], typeof MessageSquare> = {
    topic: MessageSquare,
    post: FileText,
    policy: Shield,
    product: ShoppingBag,
    user: User,
};

const typeLabels: Record<App.Data.SearchResultData['type'], string> = {
    topic: 'Topic',
    post: 'Post',
    policy: 'Policy',
    product: 'Product',
    user: 'Member',
};

export default function Search({ results, query: initialQuery, filters: initialFilters, counts }: Props) {
    const { data, setData, get, processing } = useForm({
        q: initialQuery,
        types: initialFilters.types,
        sort_by: initialFilters.sort_by,
        sort_order: initialFilters.sort_order,
        per_page: initialFilters.per_page,
        created_after: initialFilters.created_after || '',
        created_before: initialFilters.created_before || '',
        updated_after: initialFilters.updated_after || '',
        updated_before: initialFilters.updated_before || '',
    });

    const handleSearch = (e?: FormEvent) => {
        if (e) {
            e.preventDefault();
        }

        get(route('search'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const toggleType = (type: string) => {
        setData((prev) => {
            const newTypes = prev.types.includes(type) ? prev.types.filter((t) => t !== type) : [...prev.types, type];
            return {
                ...prev,
                types: newTypes.length === 0 ? [type] : newTypes,
            };
        });
    };

    const clearFilters = () => {
        setData({
            ...data,
            created_after: '',
            created_before: '',
            updated_after: '',
            updated_before: '',
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString();
    };

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (data.q !== initialQuery || data.types.toString() !== initialFilters.types.toString()) {
                handleSearch();
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [data.q, data.types]);

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
                                                    checked={data.types.includes(type)}
                                                    onCheckedChange={() => toggleType(type)}
                                                />
                                                <Label htmlFor={`type-${type}`} className="flex items-center gap-2 text-sm font-normal">
                                                    {typeLabels[type as keyof typeof typeLabels]}
                                                    <span className="text-xs text-muted-foreground">({counts[`${type}s` as keyof Counts] || 0})</span>
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
                                    <Select value={data.sort_by} onValueChange={(value) => setData('sort_by', value)}>
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
                                    <Select value={data.sort_order} onValueChange={(value) => setData('sort_order', value)}>
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
                                        {(data.created_after || data.created_before || data.updated_after || data.updated_before) && (
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
                                                value={data.created_after}
                                                onChange={(e) => setData('created_after', e.target.value)}
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
                                                value={data.created_before}
                                                onChange={(e) => setData('created_before', e.target.value)}
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
                                                value={data.updated_after}
                                                onChange={(e) => setData('updated_after', e.target.value)}
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
                                                value={data.updated_before}
                                                onChange={(e) => setData('updated_before', e.target.value)}
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
                                    <Select value={data.per_page.toString()} onValueChange={(v) => setData('per_page', parseInt(v))}>
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
                        <form onSubmit={handleSearch} className="relative">
                            <Input
                                type="search"
                                placeholder="Search policies, posts, products, topics and members..."
                                value={data.q}
                                onChange={(e) => setData('q', e.target.value)}
                                className={cn({
                                    'pr-10': processing,
                                })}
                            />
                            {processing && data.q.length > 0 && (
                                <div className="absolute top-1/2 right-3 -translate-y-1/2">
                                    <LoaderCircle className="animate-spin text-muted-foreground" />
                                </div>
                            )}
                        </form>

                        {results.lastPage > 1 && (
                            <Pagination
                                pagination={results}
                                baseUrl={route('search', {
                                    q: data.q,
                                    types: data.types,
                                    sort_by: data.sort_by,
                                    sort_order: data.sort_order,
                                    per_page: data.per_page,
                                    created_after: data.created_after || undefined,
                                    created_before: data.created_before || undefined,
                                    updated_after: data.updated_after || undefined,
                                    updated_before: data.updated_before || undefined,
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

                            {results.data.map((result: App.Data.SearchResultData) => {
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
                                                            <div className="text-sm text-muted-foreground">
                                                                {result.forumName && <div>in {result.forumName}</div>}
                                                                {result.categoryName && <div>in {result.categoryName}</div>}
                                                            </div>
                                                        </div>
                                                    </div>

                                                    {result.description && (
                                                        <RichEditorContent
                                                            className="text-sm text-muted-foreground"
                                                            content={(result.description || '').substring(0, 300)}
                                                        />
                                                    )}

                                                    {result.excerpt && <p className="text-sm text-muted-foreground">{result.excerpt}</p>}

                                                    {result.price && (
                                                        <div className="text-lg font-semibold text-primary">{currency(result.price)}</div>
                                                    )}

                                                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                                        {result.version && <div>{result.version}</div>}

                                                        {result.authorName && (
                                                            <div className="flex items-center gap-1">
                                                                <User className="size-3" />
                                                                {result.authorName}
                                                            </div>
                                                        )}

                                                        {(result.effectiveAt || result.createdAt) && (
                                                            <div className="flex items-center gap-1">
                                                                <Calendar className="size-3" />
                                                                {formatDate(result.effectiveAt || result.createdAt || '')}
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
                                    q: data.q,
                                    types: data.types,
                                    sort_by: data.sort_by,
                                    sort_order: data.sort_order,
                                    per_page: data.per_page,
                                    created_after: data.created_after || undefined,
                                    created_before: data.created_before || undefined,
                                    updated_after: data.updated_after || undefined,
                                    updated_before: data.updated_before || undefined,
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
