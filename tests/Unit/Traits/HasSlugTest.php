<?php

declare(strict_types=1);

use App\Contracts\Sluggable;
use App\Enums\PostType;
use App\Models\Forum;
use App\Models\ForumCategory;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Topic;
use App\Traits\HasSlug;
use Illuminate\Support\Str;

describe('HasSlug auto-generates slug on creation', function (): void {
    test('generates slug from name for Topic', function (): void {
        $topic = Topic::factory()->create(['title' => 'My Test Topic']);

        expect($topic->slug)->toStartWith('my-test-topic');
    });

    test('generates slug from name for Product', function (): void {
        $category = ProductCategory::factory()->create();
        $product = Product::factory()->create(['name' => 'Amazing Widget', 'slug' => null]);
        $product->categories()->attach($category);

        expect($product->slug)->toStartWith('amazing-widget');
    });

    test('generates slug from name for Forum', function (): void {
        $forum = Forum::factory()->create(['name' => 'General Discussion']);

        expect($forum->slug)->toStartWith('general-discussion');
    });

    test('generates slug from name for ForumCategory', function (): void {
        $category = ForumCategory::factory()->create(['name' => 'Community Forums']);

        expect($category->slug)->toStartWith('community-forums');
    });

    test('generates slug from name for ProductCategory', function (): void {
        $category = ProductCategory::factory()->create(['name' => 'Digital Products', 'slug' => null]);

        expect($category->slug)->toStartWith('digital-products');
    });

    test('generates slug from title for Page', function (): void {
        $page = Page::factory()->create(['title' => 'About Us Page']);

        expect($page->slug)->toStartWith('about-us-page');
    });

    test('generates slug from title for blog Post', function (): void {
        $post = Post::factory()->blog()->create([
            'topic_id' => null,
            'title' => 'My Blog Article',
            'slug' => null,
        ]);

        expect($post->slug)->toStartWith('my-blog-article');
    });

    test('generates slug from content for forum Post', function (): void {
        $topic = Topic::factory()->create();
        $post = Post::factory()->forum()->create([
            'topic_id' => $topic->id,
            'content' => '<p>This is a forum reply with content</p>',
            'slug' => null,
        ]);

        // Forum posts use stripped content limited to 20 chars as slug
        expect($post->slug)->not->toBeEmpty();
        expect($post->slug)->toStartWith('this-is-a-forum-repl');
    });
});

describe('HasSlug handles blank slug', function (): void {
    test('auto-generates slug when slug is not provided', function (): void {
        $topic = Topic::factory()->create([
            'title' => 'Auto Slug Test',
            'slug' => null,
        ]);

        expect($topic->slug)->not->toBeNull();
        expect($topic->slug)->toStartWith('auto-slug-test');
    });

    test('auto-generates slug when slug is empty string', function (): void {
        $topic = Topic::factory()->create([
            'title' => 'Empty Slug Test',
            'slug' => '',
        ]);

        expect($topic->slug)->not->toBeEmpty();
        expect($topic->slug)->toStartWith('empty-slug-test');
    });
});

describe('HasSlug preserves provided slug', function (): void {
    test('uses provided slug when set explicitly', function (): void {
        $topic = Topic::factory()->create([
            'title' => 'Original Title',
            'slug' => 'custom-slug-value',
        ]);

        expect($topic->slug)->toBe('custom-slug-value');
    });
});

describe('HasSlug handles duplicate slugs', function (): void {
    test('appends random string when slug already exists', function (): void {
        $topic1 = Topic::factory()->create([
            'title' => 'Duplicate Title',
            'slug' => 'duplicate-title',
        ]);

        $topic2 = Topic::factory()->create([
            'title' => 'Duplicate Title',
            'slug' => null,
        ]);

        expect($topic1->slug)->toBe('duplicate-title');
        expect($topic2->slug)->toStartWith('duplicate-title-');
        expect($topic2->slug)->not->toBe('duplicate-title');
        expect(Str::length($topic2->slug))->toBeGreaterThan(Str::length('duplicate-title'));
    });

    test('each duplicate gets a unique suffix', function (): void {
        Topic::factory()->create([
            'title' => 'Same Title',
            'slug' => 'same-title',
        ]);

        $topic2 = Topic::factory()->create([
            'title' => 'Same Title',
            'slug' => null,
        ]);

        $topic3 = Topic::factory()->create([
            'title' => 'Same Title',
            'slug' => null,
        ]);

        expect($topic2->slug)->not->toBe($topic3->slug);
    });

    test('handles duplicate slug for Product', function (): void {
        $category = ProductCategory::factory()->create();

        $product1 = Product::factory()->create(['name' => 'Widget', 'slug' => 'widget']);
        $product1->categories()->attach($category);

        $product2 = Product::factory()->create(['name' => 'Widget', 'slug' => null]);
        $product2->categories()->attach($category);

        expect($product1->slug)->toBe('widget');
        expect($product2->slug)->toStartWith('widget-');
        expect($product2->slug)->not->toBe('widget');
    });
});

describe('HasSlug adds slug to fillable', function (): void {
    test('slug is fillable on Topic', function (): void {
        $topic = new Topic;

        expect($topic->getFillable())->toContain('slug');
    });

    test('slug is fillable on Product', function (): void {
        $product = new Product;

        expect($product->getFillable())->toContain('slug');
    });

    test('slug is fillable on Forum', function (): void {
        $forum = new Forum;

        expect($forum->getFillable())->toContain('slug');
    });

    test('slug is fillable on Post', function (): void {
        $post = new Post;

        expect($post->getFillable())->toContain('slug');
    });
});

describe('HasSlug generateSlug implementations', function (): void {
    test('Topic implements Sluggable contract', function (): void {
        $topic = new Topic;

        expect($topic)->toBeInstanceOf(Sluggable::class);
    });

    test('Product implements Sluggable contract', function (): void {
        $product = new Product;

        expect($product)->toBeInstanceOf(Sluggable::class);
    });

    test('Post implements Sluggable contract', function (): void {
        $post = new Post;

        expect($post)->toBeInstanceOf(Sluggable::class);
    });

    test('Forum implements Sluggable contract', function (): void {
        $forum = new Forum;

        expect($forum)->toBeInstanceOf(Sluggable::class);
    });

    test('Topic generateSlug returns slugified title', function (): void {
        $topic = new Topic;
        $topic->title = 'My Test Title';

        expect($topic->generateSlug())->toBe(Str::slug('My Test Title'));
    });

    test('Product generateSlug returns slugified name', function (): void {
        $product = new Product;
        $product->name = 'My Product Name';

        expect($product->generateSlug())->toBe(Str::slug('My Product Name'));
    });

    test('Post generateSlug returns slugified title for blog type', function (): void {
        $post = new Post;
        $post->type = PostType::Blog;
        $post->title = 'My Blog Title';

        expect($post->generateSlug())->toBe(Str::slug('My Blog Title'));
    });

    test('Post generateSlug returns stripped content slug for forum type', function (): void {
        $post = new Post;
        $post->type = PostType::Forum;
        $post->content = '<p>Hello World Forum Post</p>';

        $slug = $post->generateSlug();

        expect($slug)->not->toBeNull();
        expect($slug)->toContain('hello-world-forum');
    });

    test('Forum generateSlug returns slugified name', function (): void {
        $forum = new Forum;
        $forum->name = 'General Discussion';

        expect($forum->generateSlug())->toBe(Str::slug('General Discussion'));
    });
});

describe('HasSlug uses trait', function (): void {
    test('Topic uses HasSlug trait', function (): void {
        expect(in_array(HasSlug::class, class_uses_recursive(Topic::class)))->toBeTrue();
    });

    test('Product uses HasSlug trait', function (): void {
        expect(in_array(HasSlug::class, class_uses_recursive(Product::class)))->toBeTrue();
    });

    test('Post uses HasSlug trait', function (): void {
        expect(in_array(HasSlug::class, class_uses_recursive(Post::class)))->toBeTrue();
    });

    test('Forum uses HasSlug trait', function (): void {
        expect(in_array(HasSlug::class, class_uses_recursive(Forum::class)))->toBeTrue();
    });
});
