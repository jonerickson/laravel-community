import { Button } from '@/components/ui/button';
import { useIsMobile } from '@/hooks';
import { pluralize } from '@/lib/utils';
import { Link, router } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';

interface PaginationProps {
    pagination: App.Data.PaginatedData;
    baseUrl: string;
    entityLabel: string;
    className?: string;
}

export function Pagination({ pagination, baseUrl, entityLabel, className }: PaginationProps) {
    const [pressTimer, setPressTimer] = useState<NodeJS.Timeout | null>(null);
    const [isLongPress, setIsLongPress] = useState(false);
    const { currentPage, lastPage, perPage, total } = pagination;
    const isMobile = useIsMobile();

    if (lastPage <= 1) {
        return null;
    }

    const getPageNumbers = () => {
        const pages: (number | string)[] = [];
        const showPages = isMobile ? 3 : 5;
        const halfShow = Math.floor(showPages / 2);

        let start = Math.max(1, currentPage - halfShow);
        let end = Math.min(lastPage, currentPage + halfShow);

        if (currentPage <= halfShow) {
            end = Math.min(lastPage, showPages);
        }
        if (currentPage > lastPage - halfShow) {
            start = Math.max(1, lastPage - showPages + 1);
        }

        if (start > 1) {
            if (!isMobile) {
                pages.push(1);
            }
            if (start > (isMobile ? 1 : 2)) {
                pages.push('...');
            }
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        if (end < lastPage) {
            if (end < lastPage - (isMobile ? 0 : 1)) {
                pages.push('...');
            }
            if (!isMobile) {
                pages.push(lastPage);
            }
        }

        return pages;
    };

    const pageNumbers = getPageNumbers();

    const buildPageUrl = (page: number) => {
        const separator = baseUrl.includes('?') ? '&' : '?';
        return `${baseUrl}${separator}page=${page}`;
    };

    const handlePressStart = (direction: 'next' | 'prev') => {
        setIsLongPress(false);
        const timer = setTimeout(() => {
            setIsLongPress(true);
            const targetPage = direction === 'next' ? lastPage : 1;
            router.get(buildPageUrl(targetPage));
        }, 500);
        setPressTimer(timer);
    };

    const handlePressEnd = () => {
        if (pressTimer) {
            clearTimeout(pressTimer);
            setPressTimer(null);
        }
    };

    const handleClick = (direction: 'next' | 'prev') => {
        if (isLongPress) {
            setIsLongPress(false);
            return;
        }
        handlePressEnd();
        const targetPage = direction === 'next' ? currentPage + 1 : currentPage - 1;
        router.get(buildPageUrl(targetPage));
    };

    return (
        <div className={`flex flex-col items-center justify-between gap-4 md:flex-row ${className || ''}`}>
            <div className="hidden text-sm text-muted-foreground md:block">
                Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, total)} of {total} {pluralize(entityLabel, total)}
            </div>

            <div className="flex w-full items-center justify-center gap-1 overflow-x-auto md:w-auto">
                {currentPage > 1 ? (
                    <div className="flex-1 md:inline-flex">
                        <Button
                            variant="outline"
                            size="sm"
                            className="w-full"
                            onMouseDown={() => handlePressStart('prev')}
                            onMouseUp={handlePressEnd}
                            onMouseLeave={handlePressEnd}
                            onTouchStart={() => handlePressStart('prev')}
                            onTouchEnd={handlePressEnd}
                            onClick={() => handleClick('prev')}
                        >
                            <ChevronLeft className="size-4" />
                            <span className="hidden sm:block">Previous</span>
                        </Button>
                    </div>
                ) : (
                    <div className="flex-1 md:inline-flex">
                        <Button variant="outline" size="sm" disabled className="w-full">
                            <ChevronLeft className="size-4" />
                            <span className="hidden sm:block">Previous</span>
                        </Button>
                    </div>
                )}

                {pageNumbers.map((page, index) =>
                    page === '...' ? (
                        <span key={`ellipsis-${index}`} className="px-3 py-2 text-sm text-muted-foreground">
                            ...
                        </span>
                    ) : (
                        <Link key={page} href={buildPageUrl(page as number)} className="flex-1 md:inline-flex">
                            <Button variant={currentPage === page ? 'default' : 'outline'} size="sm" className="w-full md:min-w-[40px]">
                                {page}
                            </Button>
                        </Link>
                    ),
                )}

                {currentPage < lastPage ? (
                    <div className="flex-1 md:inline-flex">
                        <Button
                            variant="outline"
                            size="sm"
                            className="w-full"
                            onMouseDown={() => handlePressStart('next')}
                            onMouseUp={handlePressEnd}
                            onMouseLeave={handlePressEnd}
                            onTouchStart={() => handlePressStart('next')}
                            onTouchEnd={handlePressEnd}
                            onClick={() => handleClick('next')}
                        >
                            <span className="hidden sm:block">Next</span>
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                ) : (
                    <div className="flex-1 md:inline-flex">
                        <Button variant="outline" size="sm" disabled className="w-full">
                            <span className="hidden sm:block">Next</span>
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                )}
            </div>
        </div>
    );
}
