import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { pluralize } from '@/lib/utils';

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

    return (
        <div className={`flex flex-col md:flex-row items-center justify-between gap-4 ${className || ''}`}>
            <div className="hidden md:block text-sm text-muted-foreground">
                Showing {((currentPage - 1) * perPage) + 1} to {Math.min(currentPage * perPage, total)} of {total} {pluralize(entityLabel, total)}
            </div>

            <div className="flex items-center gap-1 w-full md:w-auto justify-center overflow-x-auto">
                {/* Previous Button */}
                {currentPage > 1 ? (
                    <Link
                        href={`${baseUrl}?page=${currentPage - 1}`}
                        className="inline-flex"
                    >
                        <Button variant="outline" size="sm">
                            <ChevronLeft className="size-4 mr-1" />
                            Previous
                        </Button>
                    </Link>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        <ChevronLeft className="size-4 mr-1" />
                        Previous
                    </Button>
                )}

                {pageNumbers.map((page, index) => (
                    page === '...' ? (
                        <span key={`ellipsis-${index}`} className="px-3 py-2 text-sm text-muted-foreground">
                            ...
                        </span>
                    ) : (
                        <Link
                            key={page}
                            href={`${baseUrl}?page=${page}`}
                            className="inline-flex"
                        >
                            <Button
                                variant={currentPage === page ? "default" : "outline"}
                                size="sm"
                                className="min-w-[40px]"
                            >
                                {page}
                            </Button>
                        </Link>
                    )
                ))}

                {currentPage < lastPage ? (
                    <Link
                        href={`${baseUrl}?page=${currentPage + 1}`}
                        className="inline-flex"
                    >
                        <Button variant="outline" size="sm">
                            Next
                            <ChevronRight className="size-4 ml-1" />
                        </Button>
                    </Link>
                ) : (
                    <Button variant="outline" size="sm" disabled>
                        Next
                        <ChevronRight className="size-4 ml-1" />
                    </Button>
                )}
            </div>
        </div>
    );
}
