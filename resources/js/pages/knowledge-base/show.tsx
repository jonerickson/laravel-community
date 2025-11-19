import Heading from '@/components/heading';
import HeadingLarge from '@/components/heading-large';
import KnowledgeBaseArticleCard from '@/components/knowledge-base-article-card';
import RichEditorContent from '@/components/rich-editor-content';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { ucFirst } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Calendar, Clock } from 'lucide-react';

interface KnowledgeBaseShowProps {
    article: App.Data.KnowledgeBaseArticleData;
    relatedArticles: App.Data.KnowledgeBaseArticleData[];
}

export default function KnowledgeBaseShow({ article, relatedArticles }: KnowledgeBaseShowProps) {
    const { name: siteName, logoUrl } = usePage<App.Data.SharedData>().props;
    const pageDescription = article.excerpt || article.content.substring(0, 160).replace(/<[^>]*>/g, '') + '...';
    const publishedDate = new Date(article.publishedAt || article.createdAt || new Date());
    const formattedDate = publishedDate.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Knowledge Base',
            href: route('knowledge-base.index'),
        },
        ...(article.category
            ? [
                  {
                      title: article.category.name,
                      href: route('knowledge-base.index', { category: article.category.id }),
                  },
              ]
            : []),
        {
            title: article.title,
            href: route('knowledge-base.show', { article: article.slug }),
        },
    ];

    const structuredData = {
        '@context': 'https://schema.org',
        '@type': 'Article',
        headline: article.title,
        description: pageDescription,
        image: article.featuredImageUrl,
        author: {
            '@type': 'Person',
            name: article.author?.name,
        },
        publisher: {
            '@type': 'Organization',
            name: siteName,
        },
        datePublished: article.publishedAt || article.createdAt,
        dateModified: article.updatedAt,
        wordCount: article.content.split(' ').length,
        timeRequired: `PT${article.readingTime}M`,
        articleSection: article.category?.name,
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${article.title} - Knowledge base`}>
                <meta name="description" content={pageDescription} />
                <meta property="og:title" content={`${article.title} - Knowledge Base - ${siteName}`} />
                <meta property="og:description" content={pageDescription} />
                <meta property="og:type" content="article" />
                <meta property="og:image" content={article.featuredImageUrl || logoUrl} />
                <meta property="article:published_time" content={article.publishedAt || article.createdAt || undefined} />
                <meta property="article:author" content={article.author.name} />
                <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structuredData) }} />
            </Head>

            <div className="flex h-full flex-1 flex-col overflow-x-auto">
                <article className="mx-auto max-w-4xl" itemScope itemType="https://schema.org/Article">
                    <header className="mb-8">
                        <div className="mb-4 flex flex-wrap items-center gap-2">
                            <Badge variant="secondary">{ucFirst(article.type)}</Badge>
                            {article.category && <Badge variant="outline">{article.category.name}</Badge>}
                        </div>

                        <HeadingLarge title={article.title} />

                        {article.excerpt && (
                            <p className="-mt-6 mb-6 text-lg text-muted-foreground" itemProp="description">
                                {article.excerpt}
                            </p>
                        )}

                        <div className="-mt-4 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                            <div className="flex items-center gap-1">
                                <Calendar className="size-4" />
                                <time dateTime={article.publishedAt || article.createdAt || undefined}>{formattedDate}</time>
                            </div>

                            {article.readingTime && (
                                <div className="flex items-center gap-1">
                                    <Clock className="size-4" />
                                    <span>{article.readingTime} min read</span>
                                </div>
                            )}
                        </div>
                    </header>

                    {article.featuredImageUrl && (
                        <div className="mb-8 overflow-hidden rounded-lg">
                            <img src={article.featuredImageUrl} alt={article.title} className="h-auto w-full object-cover" itemProp="image" />
                        </div>
                    )}

                    <div className="prose prose-neutral dark:prose-invert mb-12 max-w-none" itemProp="articleBody">
                        <RichEditorContent content={article.content} />
                    </div>
                </article>

                {relatedArticles && relatedArticles.length > 0 && (
                    <section className="border-t pt-6">
                        <Heading title="Related articles" description={`Check out other articles in ${article.category?.name}`} />
                        <div className="grid gap-6 md:grid-cols-3">
                            {relatedArticles.map((relatedArticle) => (
                                <KnowledgeBaseArticleCard key={relatedArticle.id} article={relatedArticle} />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
