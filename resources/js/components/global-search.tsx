import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Toggle } from '@/components/ui/toggle';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { currency } from '@/lib/utils';
import axios from 'axios';
import { Calendar, ChevronDown, FileText, MessageSquare, Search, Shield, ShoppingBag, User } from 'lucide-react';
import { useEffect, useState } from 'react';

interface SearchResult {
    id: number;
    type: 'topic' | 'post' | 'policy' | 'product';
    title: string;
    description?: string;
    excerpt?: string;
    version?: string;
    price?: string;
    url: string;
    forum_name?: string;
    category_name?: string;
    author_name: string;
    post_type?: string;
    effective_at?: string;
    created_at?: string;
    updated_at?: string;
}

interface SearchResponse {
    success: boolean;
    message: string;
    data: SearchResult[];
    meta: {
        timestamp: string;
        version: string;
        total: number;
        query: string;
        types: string[];
        date_filters: {
            created_after: string | null;
            created_before: string | null;
            updated_after: string | null;
            updated_before: string | null;
        };
        counts: {
            topics: number;
            posts: number;
            policies: number;
            products: number;
        };
    };
    errors: Record<string, string[]>;
}

const typeIcons = {
    topic: MessageSquare,
    post: FileText,
    policy: Shield,
    product: ShoppingBag,
};

const typeLabels = {
    topic: 'Topic',
    post: 'Post',
    policy: 'Policy',
    product: 'Product',
};

const typeBadgeVariants = {
    topic: 'secondary' as const,
    post: 'outline' as const,
    policy: 'default' as const,
    product: 'destructive' as const,
};

export function GlobalSearch() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [meta, setMeta] = useState<SearchResponse['meta'] | null>(null);
    const [selectedTypes, setSelectedTypes] = useState<string[]>(['topic', 'post', 'policy', 'product']);
    const [dateFiltersOpen, setDateFiltersOpen] = useState(false);
    const [dateFilters, setDateFilters] = useState({
        created_after: '',
        created_before: '',
        updated_after: '',
        updated_before: '',
    });

    useEffect(() => {
        if (query.length < 2) {
            setResults([]);
            setMeta(null);
            return;
        }

        const timeoutId = setTimeout(async () => {
            setLoading(true);
            try {
                const response = await axios.get(route('api.search'), {
                    params: {
                        q: query,
                        limit: 10,
                        types: selectedTypes,
                        created_after: dateFilters.created_after || undefined,
                        created_before: dateFilters.created_before || undefined,
                        updated_after: dateFilters.updated_after || undefined,
                        updated_before: dateFilters.updated_before || undefined,
                    },
                });
                const data = response.data as SearchResponse;
                setResults(data.data || []);
                setMeta(data.meta || null);
            } catch (error) {
                console.error('Error searching:', error);
                setResults([]);
                setMeta(null);
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [query, selectedTypes, dateFilters]);

    useEffect(() => {
        const down = (e: KeyboardEvent) => {
            if (e.key === 'k' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                setOpen((open) => !open);
            }
        };
        document.addEventListener('keydown', down);
        return () => document.removeEventListener('keydown', down);
    }, []);

    const handleSelect = (url: string) => {
        setOpen(false);
        setQuery('');
        window.location.href = url;
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString();
    };

    const toggleType = (type: string) => {
        setSelectedTypes((prev) => {
            const newTypes = prev.includes(type) ? prev.filter((t) => t !== type) : [...prev, type];

            return newTypes.length === 0 ? [type] : newTypes;
        });
    };

    const updateDateFilter = (key: keyof typeof dateFilters, value: string) => {
        setDateFilters((prev) => ({
            ...prev,
            [key]: value,
        }));
    };

    const clearDateFilters = () => {
        setDateFilters({
            created_after: '',
            created_before: '',
            updated_after: '',
            updated_before: '',
        });
    };

    const groupedResults = (results || []).reduce(
        (acc, result) => {
            if (!acc[result.type]) {
                acc[result.type] = [];
            }
            acc[result.type].push(result);
            return acc;
        },
        {} as Record<string, SearchResult[]>,
    );

    return (
        <>
            <Button variant="ghost" size="icon" className="group h-9 w-9" onClick={() => setOpen(true)}>
                <Search className="!size-5 opacity-80 group-hover:opacity-100" />
                <span className="sr-only">Search</span>
            </Button>

            <CommandDialog open={open} onOpenChange={setOpen} className="w-full lg:!max-w-5xl">
                <CommandInput placeholder="Search topics, posts, policies, and products..." value={query} onValueChange={setQuery} />

                <Collapsible open={dateFiltersOpen} onOpenChange={setDateFiltersOpen}>
                    <div className="flex items-center gap-2 border-b px-3 py-2">
                        <span className="mr-2 text-sm text-nowrap text-muted-foreground">Filter by:</span>
                        <Toggle
                            pressed={selectedTypes.includes('topic')}
                            onPressedChange={() => toggleType('topic')}
                            size="sm"
                            className="h-7 px-2 text-xs"
                        >
                            <Tooltip>
                                <TooltipContent>Topics</TooltipContent>
                                <TooltipTrigger asChild>
                                    <div className="flex items-center gap-1">
                                        <MessageSquare className="mr-1 h-3 w-3" />
                                        <span className="hidden sm:block">Topics</span>
                                    </div>
                                </TooltipTrigger>
                            </Tooltip>
                        </Toggle>
                        <Toggle
                            pressed={selectedTypes.includes('post')}
                            onPressedChange={() => toggleType('post')}
                            size="sm"
                            className="h-7 px-2 text-xs"
                        >
                            <Tooltip>
                                <TooltipContent>Posts</TooltipContent>
                                <TooltipTrigger asChild>
                                    <div className="flex items-center gap-1">
                                        <FileText className="mr-1 h-3 w-3" />
                                        <span className="hidden sm:block">Posts</span>
                                    </div>
                                </TooltipTrigger>
                            </Tooltip>
                        </Toggle>
                        <Toggle
                            pressed={selectedTypes.includes('policy')}
                            onPressedChange={() => toggleType('policy')}
                            size="sm"
                            className="h-7 px-2 text-xs"
                        >
                            <Tooltip>
                                <TooltipContent>Policies</TooltipContent>
                                <TooltipTrigger asChild>
                                    <div className="flex items-center gap-1">
                                        <Shield className="mr-1 h-3 w-3" />
                                        <span className="hidden sm:block">Policies</span>
                                    </div>
                                </TooltipTrigger>
                            </Tooltip>
                        </Toggle>
                        <Toggle
                            pressed={selectedTypes.includes('product')}
                            onPressedChange={() => toggleType('product')}
                            size="sm"
                            className="h-7 px-2 text-xs"
                        >
                            <Tooltip>
                                <TooltipContent>Products</TooltipContent>
                                <TooltipTrigger asChild>
                                    <div className="flex items-center gap-1">
                                        <ShoppingBag className="mr-1 h-3 w-3" />
                                        <span className="hidden sm:block">Products</span>
                                    </div>
                                </TooltipTrigger>
                            </Tooltip>
                        </Toggle>
                        <CollapsibleTrigger asChild>
                            <Button variant="ghost" size="sm" className="ml-2 h-7 px-2 text-xs">
                                <Calendar className="mr-1 h-3 w-3" />
                                Date Filters
                                <ChevronDown className="ml-1 h-3 w-3" />
                            </Button>
                        </CollapsibleTrigger>
                    </div>
                    <CollapsibleContent className="border-b px-3 py-3">
                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1">
                                <Label htmlFor="created-after" className="text-xs">
                                    Created After
                                </Label>
                                <Input
                                    id="created-after"
                                    type="date"
                                    value={dateFilters.created_after}
                                    onChange={(e) => updateDateFilter('created_after', e.target.value)}
                                    className="h-7 text-xs"
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="created-before" className="text-xs">
                                    Created Before
                                </Label>
                                <Input
                                    id="created-before"
                                    type="date"
                                    value={dateFilters.created_before}
                                    onChange={(e) => updateDateFilter('created_before', e.target.value)}
                                    className="h-7 text-xs"
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="updated-after" className="text-xs">
                                    Updated After
                                </Label>
                                <Input
                                    id="updated-after"
                                    type="date"
                                    value={dateFilters.updated_after}
                                    onChange={(e) => updateDateFilter('updated_after', e.target.value)}
                                    className="h-7 text-xs"
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="updated-before" className="text-xs">
                                    Updated Before
                                </Label>
                                <Input
                                    id="updated-before"
                                    type="date"
                                    value={dateFilters.updated_before}
                                    onChange={(e) => updateDateFilter('updated_before', e.target.value)}
                                    className="h-7 text-xs"
                                />
                            </div>
                        </div>
                        <div className="mt-3 flex justify-end">
                            <Button variant="ghost" size="sm" onClick={clearDateFilters} className="h-6 px-2 text-xs">
                                Clear Filters
                            </Button>
                        </div>
                    </CollapsibleContent>
                </Collapsible>
                <CommandList
                    className={`max-h-screen transition-all duration-500 ease-in-out ${
                        results.length > 0 || loading || (query.length >= 2 && results.length === 0) ? 'h-[30rem]' : 'h-32'
                    }`}
                >
                    {loading && query.length >= 2 && <div className="py-6 text-center text-sm text-muted-foreground">Searching...</div>}

                    {!loading && query.length >= 2 && results.length === 0 && <CommandEmpty>No results found for "{query}"</CommandEmpty>}

                    {!loading && query.length < 2 && (
                        <div className="py-6 text-center text-sm text-muted-foreground">
                            <div className="mb-2">Start typing to search...</div>
                            <div className="text-xs">
                                <kbd className="pointer-events-none inline-flex h-5 items-center gap-1 rounded border bg-muted px-1.5 font-mono text-[10px] font-medium text-muted-foreground opacity-100 select-none">
                                    <span className="text-xs">⌘</span>K
                                </kbd>{' '}
                                to focus
                            </div>
                        </div>
                    )}

                    {Object.entries(groupedResults).map(([type, typeResults]) => (
                        <CommandGroup key={type} heading={`${typeLabels[type as keyof typeof typeLabels]} (${typeResults.length})`}>
                            {typeResults.map((result) => {
                                const Icon = typeIcons[result.type];
                                return (
                                    <CommandItem
                                        key={`${result.type}-${result.id}`}
                                        value={`${result.title} ${result.description || result.excerpt || ''}`}
                                        onSelect={() => handleSelect(result.url)}
                                        className="flex items-start gap-3 py-3"
                                    >
                                        <Icon className="mt-0.5 h-4 w-4 text-muted-foreground" />
                                        <div className="flex-1 space-y-1">
                                            <div className="flex items-center gap-2">
                                                <div className="leading-none font-medium">{result.title}</div>
                                                <Badge variant={typeBadgeVariants[result.type]} className="text-xs">
                                                    {typeLabels[result.type]}
                                                </Badge>
                                            </div>

                                            {result.description && (
                                                <div className="line-clamp-1 text-sm text-muted-foreground">{result.description}</div>
                                            )}

                                            {result.excerpt && <div className="line-clamp-1 text-sm text-muted-foreground">{result.excerpt}</div>}

                                            {result.price && <div className="text-sm font-medium text-primary">{currency(result.price)}</div>}

                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                {result.author_name && (
                                                    <div className="flex items-center gap-1">
                                                        <User className="h-3 w-3" />
                                                        {result.author_name}
                                                    </div>
                                                )}

                                                {result.forum_name && <div>in {result.forum_name}</div>}

                                                {result.category_name && <div>in {result.category_name}</div>}

                                                {result.version && <div>v{result.version}</div>}

                                                {result.effective_at ||
                                                    (result.created_at && (
                                                        <div className="flex items-center gap-1">
                                                            <Calendar className="h-3 w-3" />
                                                            {formatDate(result.effective_at || result.created_at)}
                                                        </div>
                                                    ))}
                                            </div>
                                        </div>
                                    </CommandItem>
                                );
                            })}
                        </CommandGroup>
                    ))}

                    {meta && meta.total > results.length && (
                        <div className="border-t p-2 text-center text-xs text-muted-foreground">
                            Showing {results.length} of {meta.total} results
                        </div>
                    )}
                </CommandList>
            </CommandDialog>
        </>
    );
}
