import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Kbd } from '@/components/ui/kbd';
import { Label } from '@/components/ui/label';
import { Toggle } from '@/components/ui/toggle';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { currency } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import axios from 'axios';
import { ArrowRight, Calendar, ChevronDown, FileText, MessageSquare, Search, Shield, ShoppingBag, User } from 'lucide-react';
import { useEffect, useState } from 'react';

interface SearchResponse {
    success: boolean;
    message: string;
    data: App.Data.SearchResultData[];
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

type SearchResultType = 'policy' | 'product' | 'post' | 'topic' | 'user';

const typeIcons: Record<SearchResultType, typeof MessageSquare> = {
    topic: MessageSquare,
    post: FileText,
    policy: Shield,
    product: ShoppingBag,
    user: User,
};

const typeLabels: Record<SearchResultType, string> = {
    topic: 'Topic',
    post: 'Post',
    policy: 'Policy',
    product: 'Product',
    user: 'Member',
};

const typeBadgeVariants: Record<SearchResultType, 'secondary' | 'outline' | 'default' | 'destructive'> = {
    topic: 'secondary',
    post: 'outline',
    policy: 'default',
    product: 'destructive',
    user: 'default',
};

export function GlobalSearch() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<App.Data.SearchResultData[]>([]);
    const [loading, setLoading] = useState(false);
    const [meta, setMeta] = useState<SearchResponse['meta'] | null>(null);
    const [selectedTypes, setSelectedTypes] = useState<string[]>(['policy', 'post', 'product', 'topic', 'user']);
    const [dateFiltersOpen, setDateFiltersOpen] = useState(false);
    const [dateFilters, setDateFilters] = useState({
        created_after: '',
        created_before: '',
        updated_after: '',
        updated_before: '',
    });

    const [pressTimer, setPressTimer] = useState<NodeJS.Timeout | null>(null);
    const [isLongPress, setIsLongPress] = useState(false);

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
        {} as Record<string, App.Data.SearchResultData[]>,
    );

    const handlePressStart = () => {
        setIsLongPress(false);
        const timer = setTimeout(() => {
            setIsLongPress(true);
            router.get(route('search'));
        }, 500);
        setPressTimer(timer);
    };

    const handlePressEnd = () => {
        if (pressTimer) {
            clearTimeout(pressTimer);
            setPressTimer(null);
        }
    };

    const handleClick = () => {
        if (isLongPress) {
            return;
        }
        handlePressEnd();
        setOpen(true);
    };

    return (
        <>
            <Button
                variant="ghost"
                size="icon"
                className="group h-9 w-9"
                onClick={handleClick}
                onMouseDown={handlePressStart}
                onMouseUp={handlePressEnd}
                onMouseLeave={handlePressEnd}
                onTouchStart={handlePressStart}
                onTouchEnd={handlePressEnd}
                onTouchCancel={handlePressEnd}
            >
                <Search className="size-5 opacity-80 group-hover:opacity-100" />
                <span className="sr-only">Search</span>
            </Button>

            <CommandDialog open={open} onOpenChange={setOpen} className="w-full lg:!max-w-5xl" shouldFilter={false}>
                <CommandInput placeholder="Search policies, posts, products, topics and members..." value={query} onValueChange={setQuery} />

                <Collapsible open={dateFiltersOpen} onOpenChange={setDateFiltersOpen}>
                    <div className="flex items-center gap-2 border-b px-3 py-2">
                        <span className="mr-2 text-sm text-nowrap text-muted-foreground">Filter by:</span>
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
                                        <Shield className="mr-1 size-3" />
                                        <span className="hidden sm:block">Policies</span>
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
                                        <FileText className="mr-1 size-3" />
                                        <span className="hidden sm:block">Posts</span>
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
                                        <ShoppingBag className="mr-1 size-3" />
                                        <span className="hidden sm:block">Products</span>
                                    </div>
                                </TooltipTrigger>
                            </Tooltip>
                        </Toggle>
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
                                        <MessageSquare className="mr-1 size-3" />
                                        <span className="hidden sm:block">Topics</span>
                                    </div>
                                </TooltipTrigger>
                            </Tooltip>
                        </Toggle>
                        <Toggle
                            pressed={selectedTypes.includes('user')}
                            onPressedChange={() => toggleType('user')}
                            size="sm"
                            className="h-7 px-2 text-xs"
                        >
                            <Tooltip>
                                <TooltipContent>Members</TooltipContent>
                                <TooltipTrigger asChild>
                                    <div className="flex items-center gap-1">
                                        <User className="mr-1 size-3" />
                                        <span className="hidden sm:block">Members</span>
                                    </div>
                                </TooltipTrigger>
                            </Tooltip>
                        </Toggle>
                        <CollapsibleTrigger asChild>
                            <Button variant="ghost" size="sm" className="ml-2 h-7 px-2 text-xs">
                                <Calendar className="mr-1 size-3" />
                                Date Filters
                                <ChevronDown className="ml-1 size-3" />
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
                <CommandList className="max-h-[30rem] overflow-y-auto">
                    {loading && query.length >= 2 && <div className="py-6 text-center text-sm text-muted-foreground">Searching...</div>}

                    {!loading && query.length >= 2 && results.length === 0 && <CommandEmpty>No results found for "{query}"</CommandEmpty>}

                    {!loading && query.length < 2 && (
                        <div className="py-6 text-center text-sm text-muted-foreground">
                            <div className="mb-2">Start typing to search...</div>
                            <div className="text-xs">
                                <Kbd>⌘ K</Kbd> to focus
                            </div>
                        </div>
                    )}

                    {(Object.entries(groupedResults) as [SearchResultType, App.Data.SearchResultData[]][]).map(([type, typeResults]) => (
                        <CommandGroup key={type} heading={`${typeLabels[type]} (${typeResults.length})`}>
                            {typeResults.map((result) => {
                                const Icon = typeIcons[type];
                                return (
                                    <CommandItem
                                        key={`${result.type}-${result.id}`}
                                        value={`${result.title} ${result.description || result.excerpt || undefined}`}
                                        onSelect={() => handleSelect(result.url)}
                                        className="flex items-start gap-3 py-3"
                                    >
                                        <Icon className="mt-0.5 size-4 text-muted-foreground" />
                                        <div className="flex-1 space-y-1">
                                            <div className="flex items-center gap-2">
                                                <div className="leading-none font-medium">{result.title}</div>
                                                <Badge variant={typeBadgeVariants[type]} className="text-xs">
                                                    {typeLabels[type]}
                                                </Badge>
                                            </div>

                                            {result.description && (
                                                <RichEditorContent
                                                    className="line-clamp-1 text-sm text-muted-foreground"
                                                    content={result.description}
                                                />
                                            )}

                                            {result.excerpt && <div className="line-clamp-1 text-sm text-muted-foreground">{result.excerpt}</div>}

                                            {result.price && <div className="text-sm font-medium text-primary">{currency(result.price)}</div>}

                                            {['policy', 'post', 'topic'].includes(result.type) && (
                                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                    {result.authorName && (
                                                        <div className="flex items-center gap-1">
                                                            <User className="!size-3" />
                                                            {result.authorName}
                                                        </div>
                                                    )}

                                                    {result.forumName && <div>in {result.forumName}</div>}

                                                    {result.categoryName && <div>in {result.categoryName}</div>}

                                                    {result.version && <div>v{result.version}</div>}

                                                    {result.effectiveAt ||
                                                        (result.createdAt && (
                                                            <div className="flex items-center gap-1">
                                                                <Calendar className="!size-3" />
                                                                {formatDate(result.effectiveAt || result.createdAt)}
                                                            </div>
                                                        ))}
                                                </div>
                                            )}
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

                    {query.length >= 2 && (
                        <div className="border-t p-3">
                            <Link
                                href={route('search', {
                                    q: query,
                                    types: selectedTypes,
                                    created_after: dateFilters.created_after || undefined,
                                    created_before: dateFilters.created_before || undefined,
                                    updated_after: dateFilters.updated_after || undefined,
                                    updated_before: dateFilters.updated_before || undefined,
                                })}
                                className="group flex items-center justify-center gap-2 text-sm font-medium text-primary hover:underline"
                                onClick={() => setOpen(false)}
                            >
                                View all results
                                <ArrowRight className="size-4 transition-transform group-hover:translate-x-1" />
                            </Link>
                        </div>
                    )}
                </CommandList>
            </CommandDialog>
        </>
    );
}
