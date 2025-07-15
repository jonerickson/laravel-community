import StoreCategories from '@/components/store-categories';
import StoreFeatured from '@/components/store-featured';
import StoreUserProvided from '@/components/store-user-provided';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Store',
        href: '/store',
    },
];

const categories = [
    {
        id: 1,
        name: 'New Arrivals',
        href: '#',
        imageUrl: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/home-page-01-category-01.jpg',
    },
    {
        id: 2,
        name: 'Productivity',
        href: '#',
        imageUrl: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/home-page-01-category-02.jpg',
    },
    {
        id: 3,
        name: 'Workspace',
        href: '#',
        imageUrl: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/home-page-01-category-04.jpg',
    },
    {
        id: 4,
        name: 'Accessories',
        href: '#',
        imageUrl: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/home-page-01-category-05.jpg',
    },
    {
        id: 5,
        name: 'Sale',
        href: '#',
        imageUrl: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/home-page-01-category-03.jpg',
    },
];

const userProvidedProducts = [
    {
        id: 1,
        name: 'Desk and Office',
        description: 'Work from home accessories',
        imageUrl: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/home-page-02-edition-01.jpg',
        imageAlt: 'Desk with leather desk pad, walnut desk organizer, wireless keyboard and mouse, and porcelain mug.',
        href: '#',
    },
    {
        id: 2,
        name: 'Self-Improvement',
        description: 'Journals and note-taking',
        imageUrl: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/home-page-02-edition-02.jpg',
        imageAlt: 'Wood table with porcelain mug, leather journal, brass pen, leather key ring, and a houseplant.',
        href: '#',
    },
    {
        id: 3,
        name: 'Travel',
        description: 'Daily commute essentials',
        imageUrl: 'https://tailwindcss.com/plus-assets/img/ecommerce-images/home-page-02-edition-03.jpg',
        imageAlt: 'Collection of four insulated travel bottles on wooden shelf.',
        href: '#',
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Store" />
            <div className="flex h-full flex-1 flex-col gap-8 overflow-x-auto rounded-xl p-4">
                <StoreCategories categories={categories} />
                <StoreFeatured />
                <StoreUserProvided products={userProvidedProducts} />
            </div>
        </AppLayout>
    );
}
