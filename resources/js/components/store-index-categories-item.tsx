import { Link } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';

export default function StoreIndexCategoriesItem({ item }: { item: App.Data.ProductCategoryData }) {
    return (
        <Link
            key={item.name}
            href={route('store.categories.show', { slug: item.slug })}
            className="relative flex min-h-48 flex-col justify-center overflow-hidden rounded-lg bg-muted p-6 hover:opacity-75 xl:w-auto"
        >
            {item.featuredImageUrl ? (
                <>
                    <span aria-hidden="true" className="absolute inset-0">
                        <img alt={`${item.name} category image`} src={item.featuredImageUrl} className="size-full object-cover" />
                    </span>
                    <span
                        aria-hidden="true"
                        className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/80 via-black/20 to-transparent"
                    />
                    <span className="relative mt-auto text-center text-base font-bold text-white">{item.name}</span>
                </>
            ) : (
                <>
                    <div className="flex aspect-video w-full items-center justify-center rounded-2xl bg-muted sm:aspect-[2/1] lg:aspect-[3/2]">
                        <ImageIcon className="h-16 w-16 text-muted-foreground" />
                    </div>
                    <span
                        aria-hidden="true"
                        className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-black/80 via-black/20 to-transparent"
                    />
                    <span className="relative mt-auto text-center text-base font-bold text-white">{item.name}</span>
                </>
            )}
        </Link>
    );
}
