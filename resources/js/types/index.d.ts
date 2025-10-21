import { LucideIcon } from 'lucide-react';

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface File {
    name: string;
    language: 'html' | 'css' | 'javascript';
    content: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string | (() => string);
    icon?: LucideIcon | null;
    isActive: () => boolean;
    target?: string;
    shouldShow?: boolean | ((auth: App.Data.AuthData) => boolean);
}
