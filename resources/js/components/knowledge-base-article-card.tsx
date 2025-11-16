import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { ucFirst } from '@/lib/utils';
import { stripCharacters, truncate } from '@/utils/truncate';
import { Link } from '@inertiajs/react';
import { Clock, ImageIcon } from 'lucide-react';

interface KnowledgeBaseArticleCardProps {
    article: App.Data.KnowledgeBaseArticleData;
}

export default function KnowledgeBaseArticleCard({ article }: KnowledgeBaseArticleCardProps) {
    const publishedDate = new Date(article.publishedAt || article.createdAt || new Date());
    const formattedDate = publishedDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });

    return (
        <div className="flex flex-col items-start space-y-4">
            <Link href={route('knowledge-base.show', { article: article.slug })} className="group w-full">
                <article className="flex flex-col space-y-6">
                    <div className="relative w-full overflow-hidden rounded-2xl">
                        {article.featuredImageUrl ? (
                            <img
                                alt={article.title}
                                src={article.featuredImageUrl}
                                className="aspect-video w-full rounded-2xl bg-muted object-cover transition-transform group-hover:scale-105 group-hover:shadow-lg sm:aspect-[2/1] lg:aspect-[3/2]"
                            />
                        ) : (
                            <div className="flex aspect-video w-full items-center justify-center rounded-2xl bg-muted transition-transform group-hover:scale-105 group-hover:shadow-lg sm:aspect-[2/1] lg:aspect-[3/2]">
                                <ImageIcon className="size-16 text-muted-foreground" />
                            </div>
                        )}
                        <div className="absolute inset-0 rounded-2xl ring-1 ring-ring/20 ring-inset" />
                    </div>

                    <div className="flex max-w-xl grow flex-col justify-between space-y-2">
                        <div className="flex flex-row flex-wrap gap-2">
                            <Badge variant="secondary">{ucFirst(article.type)}</Badge>
                            {article.category && <Badge variant="outline">{article.category.name}</Badge>}
                        </div>

                        <div className="flex items-center gap-x-4 text-xs">
                            <time dateTime={article.publishedAt || article.createdAt || undefined} className="text-muted-foreground">
                                {formattedDate}
                            </time>

                            {article.readingTime && (
                                <div className="flex items-center gap-1 text-muted-foreground">
                                    <Clock className="size-3" />
                                    <span>{article.readingTime} min read</span>
                                </div>
                            )}
                        </div>

                        <div className="group relative grow">
                            <HeadingSmall title={article.title} description={article.excerpt || truncate(stripCharacters(article.content))} />
                        </div>
                    </div>
                </article>
            </Link>
        </div>
    );
}
