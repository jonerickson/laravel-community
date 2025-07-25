import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { CommandDialog, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Calendar, FileText, MessageSquare, Search, Shield, User } from 'lucide-react';
import { useEffect, useState } from 'react';

interface SearchResult {
    id: number;
    type: 'topic' | 'post' | 'policy';
    title: string;
    description?: string;
    excerpt?: string;
    version?: string;
    url: string;
    forum_name?: string;
    category_name?: string;
    author_name: string;
    post_type?: string;
    effective_date?: string;
    created_at: string;
}

interface SearchResponse {
    data: SearchResult[];
    meta: {
        total: number;
        query: string;
        counts: {
            topics: number;
            posts: number;
            policies: number;
        };
    };
}

const typeIcons = {
    topic: MessageSquare,
    post: FileText,
    policy: Shield,
};

const typeLabels = {
    topic: 'Topic',
    post: 'Post',
    policy: 'Policy',
};

const typeBadgeVariants = {
    topic: 'secondary' as const,
    post: 'outline' as const,
    policy: 'default' as const,
};

export function GlobalSearch() {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [meta, setMeta] = useState<SearchResponse['meta'] | null>(null);

    // Debounced search
    useEffect(() => {
        if (query.length < 2) {
            setResults([]);
            setMeta(null);
            return;
        }

        const timeoutId = setTimeout(async () => {
            setLoading(true);
            try {
                const response = await fetch(`/api/search?q=${encodeURIComponent(query)}&limit=10`);
                const data: SearchResponse = await response.json();
                setResults(data.data);
                setMeta(data.meta);
            } catch (error) {
                console.error('Search error:', error);
                setResults([]);
                setMeta(null);
            } finally {
                setLoading(false);
            }
        }, 300);

        return () => clearTimeout(timeoutId);
    }, [query]);

    // Handle keyboard shortcuts
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

    const groupedResults = results.reduce(
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
            <Button variant="ghost" size="icon" className="group h-9 w-9 cursor-pointer" onClick={() => setOpen(true)}>
                <Search className="!size-5 opacity-80 group-hover:opacity-100" />
                <span className="sr-only">Search</span>
            </Button>

            <CommandDialog open={open} onOpenChange={setOpen} className="w-full lg:!max-w-5xl">
                <CommandInput placeholder="Search topics, posts, and policies..." value={query} onValueChange={setQuery} />
                <CommandList
                    className={`max-h-screen transition-all duration-500 ease-in-out ${
                        results.length > 0 || loading || (query.length >= 2 && results.length === 0) ? 'h-[30rem]' : 'h-32'
                    }`}
                >
                    {loading && query.length >= 2 && <div className="py-6 text-center text-sm text-muted-foreground">Searching...</div>}

                    {!loading && query.length >= 2 && results.length === 0 && <CommandEmpty>No results found for "{query}"</CommandEmpty>}

                    {!loading && query.length < 2 && (
                        <div className="py-6 text-center text-sm text-muted-foreground">
                            <div className="mb-2">Start typing to search</div>
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

                                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                                <div className="flex items-center gap-1">
                                                    <User className="h-3 w-3" />
                                                    {result.author_name}
                                                </div>

                                                {result.forum_name && <div>in {result.forum_name}</div>}

                                                {result.category_name && <div>in {result.category_name}</div>}

                                                {result.version && <div>v{result.version}</div>}

                                                <div className="flex items-center gap-1">
                                                    <Calendar className="h-3 w-3" />
                                                    {formatDate(result.effective_date || result.created_at)}
                                                </div>
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
