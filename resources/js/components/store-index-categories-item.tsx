import { ProductCategory } from '@/types';
import { Link } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';

export default function StoreIndexCategoriesItem({ item }: { item: ProductCategory }) {
    return (
        <Link
            key={item.name}
            href={route('store.categories.show', { slug: item.slug })}
            className="relative flex h-80 w-56 flex-col justify-center overflow-hidden rounded-lg bg-muted p-6 hover:opacity-75 xl:w-auto"
        >
            {item.imageUrl ? (
                <>
                    <span aria-hidden="true" className="absolute inset-0">
                        <img alt={item.imageAlt} src={item.imageUrl} className="size-full object-cover" />
                    </span>
                    <span aria-hidden="true" className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-muted-foreground opacity-50" />
                    <span className="relative mt-auto text-center text-xl font-bold text-white">{item.name}</span>
                </>
            ) : (
                <>
                    <div className="flex aspect-video w-full items-center justify-center rounded-2xl bg-muted sm:aspect-[2/1] lg:aspect-[3/2]">
                        <ImageIcon className="h-16 w-16 text-muted-foreground" />
                    </div>
                    <span aria-hidden="true" className="absolute inset-x-0 bottom-0 h-2/3 bg-gradient-to-t from-muted-foreground opacity-50" />
                    <span className="relative mt-auto text-center text-xl font-bold text-white">{item.name}</span>
                </>
            )}
        </Link>
    );
}
