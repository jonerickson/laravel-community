import type { BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

/**
 * Recursively builds breadcrumb items for all parent forums
 */
export function buildForumParentBreadcrumbs(forum: App.Data.ForumData): BreadcrumbItem[] {
    const parents: BreadcrumbItem[] = [];
    let parent = forum.parent;

    while (parent) {
        parents.unshift({
            title: parent.name,
            href: route('forums.show', { forum: parent.slug }),
        });
        parent = parent.parent;
    }

    return parents;
}

/**
 * Builds complete breadcrumb trail for a forum page
 */
export function buildForumBreadcrumbs(forum: App.Data.ForumData): BreadcrumbItem[] {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Forums',
            href: route('forums.index'),
        },
    ];

    if (forum.category) {
        breadcrumbs.push({
            title: forum.category.name,
            href: route('forums.categories.show', { category: forum.category.slug }),
        });
    }

    breadcrumbs.push(...buildForumParentBreadcrumbs(forum));

    breadcrumbs.push({
        title: forum.name,
        href: route('forums.show', { forum: forum.slug }),
    });

    return breadcrumbs;
}

/**
 * Builds complete breadcrumb trail for a topic page
 */
export function buildTopicBreadcrumbs(forum: App.Data.ForumData, topic: App.Data.TopicData): BreadcrumbItem[] {
    const breadcrumbs = buildForumBreadcrumbs(forum);

    breadcrumbs.push({
        title: topic.title,
        href: route('forums.topics.show', { forum: forum.slug, topic: topic.slug }),
    });

    return breadcrumbs;
}
