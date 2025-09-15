import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { PaginatedData } from '@/types';
import { pluralize } from '@/lib/utils';

interface PaginationProps {
    pagination: PaginatedData;
    baseUrl: string;
    entityLabel: string;
    className?: string;
}

export function Pagination({ pagination, baseUrl, entityLabel, className }: PaginationProps) {
    const { current_page, last_page, per_page, total } = pagination;

    if (last_page <= 1) {
        return null;
    }

    const getPageNumbers = () => {
        const pages: (number | string)[] = [];
        const showPages = 5;
        const halfShow = Math.floor(showPages / 2);

        let start = Math.max(1, current_page - halfShow);
        let end = Math.min(last_page, current_page + halfShow);

        if (current_page <= halfShow) {
            end = Math.min(last_page, showPages);
        }
        if (current_page > last_page - halfShow) {
            start = Math.max(1, last_page - showPages + 1);
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

        if (end < last_page) {
            if (end < last_page - 1) {
                pages.push('...');
            }
            pages.push(last_page);
        }

        return pages;
    };

    const pageNumbers = getPageNumbers();

    return (
        <div className={`flex flex-col md:flex-row items-center justify-between gap-4 ${className || ''}`}>
            <div className="hidden md:block text-sm text-muted-foreground">
                Showing {((current_page - 1) * per_page) + 1} to {Math.min(current_page * per_page, total)} of {total} {pluralize(entityLabel, total)}
            </div>

            <div className="flex items-center gap-1 w-full md:w-auto justify-center overflow-x-auto">
                {/* Previous Button */}
                {current_page > 1 ? (
                    <Link
                        href={`${baseUrl}?page=${current_page - 1}`}
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

                {/* Page Numbers */}
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
                                variant={current_page === page ? "default" : "outline"}
                                size="sm"
                                className="min-w-[40px]"
                            >
                                {page}
                            </Button>
                        </Link>
                    )
                ))}

                {/* Next Button */}
                {current_page < last_page ? (
                    <Link
                        href={`${baseUrl}?page=${current_page + 1}`}
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
