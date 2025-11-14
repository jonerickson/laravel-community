import { Button } from '@/components/ui/button';
import { pluralize } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface PaginationProps {
    pagination: App.Data.PaginatedData;
    baseUrl: string;
    entityLabel: string;
    className?: string;
}

export function Pagination({ pagination, baseUrl, entityLabel, className }: PaginationProps) {
    const { currentPage, lastPage, perPage, total } = pagination;

    if (lastPage <= 1) {
        return null;
    }

    const getPageNumbers = () => {
        const pages: (number | string)[] = [];
        const showPages = 5;
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
            pages.push(1);
            if (start > 2) {
                pages.push('...');
            }
        }

        for (let i = start; i <= end; i++) {
            pages.push(i);
        }

        if (end < lastPage) {
            if (end < lastPage - 1) {
                pages.push('...');
            }
            pages.push(lastPage);
        }

        return pages;
    };

    const pageNumbers = getPageNumbers();

    const buildPageUrl = (page: number) => {
        const separator = baseUrl.includes('?') ? '&' : '?';
        return `${baseUrl}${separator}page=${page}`;
    };

    return (
        <div className={`flex flex-col items-center justify-between gap-4 md:flex-row ${className || ''}`}>
            <div className="hidden text-sm text-muted-foreground md:block">
                Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, total)} of {total} {pluralize(entityLabel, total)}
            </div>

            <div className="flex w-full items-center justify-center gap-1 overflow-x-auto md:w-auto">
                {currentPage > 1 ? (
                    <Link href={buildPageUrl(currentPage - 1)} className="inline-flex">
                        <Button variant="outline" size="sm">
                            <ChevronLeft className="mr-1 size-4" />
                            Previous
                        </Button>
                    </Link>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        <ChevronLeft className="mr-1 size-4" />
                        Previous
                    </Button>
                )}

                {pageNumbers.map((page, index) =>
                    page === '...' ? (
                        <span key={`ellipsis-${index}`} className="px-3 py-2 text-sm text-muted-foreground">
                            ...
                        </span>
                    ) : (
                        <Link key={page} href={buildPageUrl(page as number)} className="inline-flex">
                            <Button variant={currentPage === page ? 'default' : 'outline'} size="sm" className="min-w-[40px]">
                                {page}
                            </Button>
                        </Link>
                    ),
                )}

                {currentPage < lastPage ? (
                    <Link href={buildPageUrl(currentPage + 1)} className="inline-flex">
                        <Button variant="outline" size="sm">
                            Next
                            <ChevronRight className="ml-1 size-4" />
                        </Button>
                    </Link>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        Next
                        <ChevronRight className="ml-1 size-4" />
                    </Button>
                )}
            </div>
        </div>
    );
}
