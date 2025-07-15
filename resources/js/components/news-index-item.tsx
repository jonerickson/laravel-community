import HeadingSmall from '@/components/heading-small';
import { UserInfo } from '@/components/user-info';

export interface NewsItemAuthor {
    name: string;
    role: string;
    href: string;
    imageUrl: string;
}

export interface NewsItemCategory {
    title: string;
    href: string;
}

export interface NewsIndexItem {
    id: number;
    title: string;
    href: string;
    description: string;
    imageUrl: string;
    date: string;
    datetime: string;
    category: NewsItemCategory;
    author: NewsItemAuthor;
}

export default function NewsIndexItem({ item }: { item: NewsIndexItem }) {
    return (
        <article key={item.id} className="flex flex-col items-start justify-between">
            <div className="relative w-full">
                <img alt="" src={item.imageUrl} className="aspect-video w-full rounded-2xl bg-muted object-cover sm:aspect-[2/1] lg:aspect-[3/2]" />
                <div className="absolute inset-0 rounded-2xl ring-1 ring-gray-900/10 ring-inset" />
            </div>
            <div className="flex max-w-xl grow flex-col justify-between">
                <div className="mt-8 flex items-center gap-x-4 text-xs">
                    <time dateTime={item.datetime} className="text-muted-foreground">
                        {item.date}
                    </time>
                    <span className="relative z-10 rounded-full bg-muted px-3 py-1.5 font-medium text-muted-foreground">{item.category.title}</span>
                </div>
                <div className="group relative mt-2 grow">
                    <HeadingSmall title={item.title} description={item.description} />
                </div>
                <div className="flex items-center gap-2 py-1.5 pt-4 text-left text-sm">
                    <UserInfo
                        user={{
                            id: 1,
                            name: item.author.name,
                            email: 'test@test.com',
                            email_verified_at: null,
                            created_at: '',
                            updated_at: '',
                        }}
                        showEmail={true}
                    />
                </div>
            </div>
        </article>
    );
}
