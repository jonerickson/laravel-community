<?php

declare(strict_types=1);

use App\Enums\ProductApprovalStatus;
use App\Enums\ProductType;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Image;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Policy;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;

describe('Product categories relationship', function (): void {
    test('returns empty collection when product has no categories', function (): void {
        $product = Product::factory()->create();

        expect($product->categories)->toBeEmpty();
    });

    test('returns categories attached to product', function (): void {
        $product = Product::factory()->create();
        $category = ProductCategory::factory()->create();

        $product->categories()->attach($category);

        $categories = $product->categories;

        expect($categories)->toHaveCount(1);
        expect($categories->first()->id)->toBe($category->id);
    });

    test('returns multiple categories', function (): void {
        $product = Product::factory()->create();
        $category1 = ProductCategory::factory()->create();
        $category2 = ProductCategory::factory()->create();

        $product->categories()->attach([$category1->id, $category2->id]);

        expect($product->categories)->toHaveCount(2);
    });

    test('categories relationship is BelongsToMany', function (): void {
        $product = Product::factory()->create();

        expect($product->categories())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});

describe('Product prices relationship', function (): void {
    test('returns empty collection when product has no prices', function (): void {
        $product = Product::factory()->create();

        expect($product->prices)->toBeEmpty();
    });

    test('returns prices belonging to product', function (): void {
        $product = Product::factory()->create();
        $price = Price::factory()->create(['product_id' => $product->id]);

        $prices = $product->prices;

        expect($prices)->toHaveCount(1);
        expect($prices->first()->id)->toBe($price->id);
    });

    test('does not return prices from other products', function (): void {
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        Price::factory()->create(['product_id' => $product->id]);
        Price::factory()->create(['product_id' => $otherProduct->id]);

        expect($product->prices)->toHaveCount(1);
    });

    test('prices relationship is HasMany', function (): void {
        $product = Product::factory()->create();

        expect($product->prices())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasMany::class);
    });
});

describe('Product activePrices relationship', function (): void {
    test('returns only active prices', function (): void {
        $product = Product::factory()->create();

        Price::factory()->active()->create(['product_id' => $product->id]);
        Price::factory()->inactive()->create(['product_id' => $product->id]);

        expect($product->activePrices)->toHaveCount(1);
    });

    test('returns empty when no active prices', function (): void {
        $product = Product::factory()->create();

        Price::factory()->inactive()->create(['product_id' => $product->id]);

        expect($product->activePrices)->toBeEmpty();
    });
});

describe('Product defaultPrice relationship', function (): void {
    test('returns default active price', function (): void {
        $product = Product::factory()->create();

        Price::factory()->active()->create(['product_id' => $product->id, 'is_default' => false]);
        $defaultPrice = Price::factory()->active()->default()->create(['product_id' => $product->id]);

        expect($product->defaultPrice->id)->toBe($defaultPrice->id);
    });

    test('returns null when no default price', function (): void {
        $product = Product::factory()->create();

        Price::factory()->active()->create(['product_id' => $product->id, 'is_default' => false]);

        expect($product->defaultPrice)->toBeNull();
    });

    test('returns null when default price is inactive', function (): void {
        $product = Product::factory()->create();

        Price::factory()->inactive()->default()->create(['product_id' => $product->id]);

        expect($product->defaultPrice)->toBeNull();
    });

    test('defaultPrice relationship is HasOne', function (): void {
        $product = Product::factory()->create();

        expect($product->defaultPrice())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasOne::class);
    });
});

describe('Product orderItems relationship', function (): void {
    test('returns empty collection when product has no order items', function (): void {
        $product = Product::factory()->create();

        expect($product->orderItems)->toBeEmpty();
    });

    test('returns order items through prices', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $price = Price::factory()->create(['product_id' => $product->id]);
        $order = Order::factory()->create(['user_id' => $user->id]);

        $orderItem = OrderItem::create([
            'order_id' => $order->id,
            'price_id' => $price->id,
            'quantity' => 1,
            'amount' => $price->amount,
        ]);

        $orderItems = $product->orderItems;

        expect($orderItems)->toHaveCount(1);
        expect($orderItems->first()->id)->toBe($orderItem->id);
    });

    test('orderItems relationship is HasManyThrough', function (): void {
        $product = Product::factory()->create();

        expect($product->orderItems())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasManyThrough::class);
    });
});

describe('Product seller relationship', function (): void {
    test('returns null when product has no seller', function (): void {
        $product = Product::factory()->create(['seller_id' => null]);

        expect($product->seller)->toBeNull();
    });

    test('returns seller user when product has seller', function (): void {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        expect($product->seller->id)->toBe($seller->id);
    });

    test('seller relationship is BelongsTo', function (): void {
        $product = Product::factory()->create();

        expect($product->seller())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });
});

describe('Product approver relationship', function (): void {
    test('returns null when product has no approver', function (): void {
        $product = Product::factory()->create(['approved_by' => null]);

        expect($product->approver)->toBeNull();
    });

    test('returns approver user when product has been approved', function (): void {
        $approver = User::factory()->asAdmin()->create();
        $product = Product::factory()->approved()->create([
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        expect($product->approver->id)->toBe($approver->id);
    });

    test('approver relationship is BelongsTo', function (): void {
        $product = Product::factory()->create();

        expect($product->approver())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });
});

describe('Product policies relationship', function (): void {
    test('returns empty collection when product has no policies', function (): void {
        $product = Product::factory()->create();

        expect($product->policies)->toBeEmpty();
    });

    test('returns policies attached to product', function (): void {
        $product = Product::factory()->create();
        $policy = Policy::factory()->create();

        $product->policies()->attach($policy);

        $policies = $product->policies;

        expect($policies)->toHaveCount(1);
        expect($policies->first()->id)->toBe($policy->id);
    });

    test('policies relationship is BelongsToMany', function (): void {
        $product = Product::factory()->create();

        expect($product->policies())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});

describe('Product reviews relationship (from Reviewable trait)', function (): void {
    test('returns empty collection when product has no reviews', function (): void {
        $product = Product::factory()->create();

        expect($product->reviews)->toBeEmpty();
    });

    test('returns reviews with ratings', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $review = Comment::factory()->approved()->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'created_by' => $user->id,
            'rating' => 5,
        ]);

        expect($product->reviews)->toHaveCount(1);
        expect($product->reviews->first()->id)->toBe($review->id);
    });

    test('does not return comments without rating', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Comment::factory()->approved()->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'created_by' => $user->id,
            'rating' => null,
        ]);

        expect($product->reviews)->toBeEmpty();
    });
});

describe('Product approvedReviews relationship', function (): void {
    test('returns only approved reviews', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Comment::factory()->approved()->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'created_by' => $user->id,
            'rating' => 5,
        ]);

        $pendingReview = Comment::factory()->approved()->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'created_by' => $user->id,
            'rating' => 4,
        ]);
        $pendingReview->update(['is_approved' => false]);

        expect($product->approvedReviews)->toHaveCount(1);
    });
});

describe('Product groups relationship (from HasGroups trait)', function (): void {
    test('returns empty collection when product has no groups', function (): void {
        $product = Product::factory()->create();

        expect($product->groups)->toBeEmpty();
    });

    test('returns groups attached to product', function (): void {
        $product = Product::factory()->create();
        $group = Group::factory()->create();

        $product->groups()->attach($group);

        $groups = $product->groups;

        expect($groups)->toHaveCount(1);
        expect($groups->first()->id)->toBe($group->id);
    });

    test('groups relationship is BelongsToMany', function (): void {
        $product = Product::factory()->create();

        expect($product->groups())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\BelongsToMany::class);
    });
});

describe('Product scopes', function (): void {
    test('products scope filters to Product type', function (): void {
        Product::factory()->product()->create();
        Product::factory()->subscription()->create();

        $products = Product::query()->products()->get();

        expect($products)->toHaveCount(1);
        expect($products->first()->type)->toBe(ProductType::Product);
    });

    test('subscriptions scope filters to Subscription type', function (): void {
        Product::factory()->product()->create();
        Product::factory()->subscription()->create();

        $subscriptions = Product::query()->subscriptions()->get();

        expect($subscriptions)->toHaveCount(1);
        expect($subscriptions->first()->type)->toBe(ProductType::Subscription);
    });

    test('approved scope filters to Approved status', function (): void {
        Product::factory()->approved()->create();
        Product::factory()->pending()->create();
        Product::factory()->rejected()->create();

        $approved = Product::query()->approved()->get();

        expect($approved)->toHaveCount(1);
        expect($approved->first()->approval_status)->toBe(ProductApprovalStatus::Approved);
    });

    test('pending scope filters to Pending status', function (): void {
        Product::factory()->approved()->create();
        Product::factory()->pending()->create();

        $pending = Product::query()->pending()->get();

        expect($pending)->toHaveCount(1);
        expect($pending->first()->approval_status)->toBe(ProductApprovalStatus::Pending);
    });

    test('rejected scope filters to Rejected status', function (): void {
        Product::factory()->approved()->create();
        Product::factory()->rejected()->create();

        $rejected = Product::query()->rejected()->get();

        expect($rejected)->toHaveCount(1);
        expect($rejected->first()->approval_status)->toBe(ProductApprovalStatus::Rejected);
    });

    test('marketplace scope filters to products with seller', function (): void {
        $seller = User::factory()->create();
        Product::factory()->create(['seller_id' => $seller->id]);
        Product::factory()->create(['seller_id' => null]);

        $marketplace = Product::query()->marketplace()->get();

        expect($marketplace)->toHaveCount(1);
        expect($marketplace->first()->seller_id)->toBe($seller->id);
    });

    test('withExternalProduct scope filters to products with external_product_id', function (): void {
        Product::factory()->withStripe()->create();
        Product::factory()->withoutStripe()->create();

        $withExternal = Product::query()->withExternalProduct()->get();

        expect($withExternal)->toHaveCount(1);
        expect($withExternal->first()->external_product_id)->not->toBeNull();
    });

    test('withoutExternalProduct scope filters to products without external_product_id', function (): void {
        Product::factory()->withStripe()->create();
        Product::factory()->withoutStripe()->create();

        $withoutExternal = Product::query()->withoutExternalProduct()->get();

        expect($withoutExternal)->toHaveCount(1);
        expect($withoutExternal->first()->external_product_id)->toBeNull();
    });
});

describe('Product isMarketplaceProduct attribute', function (): void {
    test('returns true when product has seller', function (): void {
        $seller = User::factory()->create();
        $product = Product::factory()->create(['seller_id' => $seller->id]);

        expect($product->is_marketplace_product)->toBeTrue();
    });

    test('returns false when product has no seller', function (): void {
        $product = Product::factory()->create(['seller_id' => null]);

        expect($product->is_marketplace_product)->toBeFalse();
    });
});

describe('Product isProduct and isSubscription methods', function (): void {
    test('isProduct returns true for Product type', function (): void {
        $product = Product::factory()->product()->create();

        expect($product->isProduct())->toBeTrue();
        expect($product->isSubscription())->toBeFalse();
    });

    test('isSubscription returns true for Subscription type', function (): void {
        $product = Product::factory()->subscription()->create();

        expect($product->isSubscription())->toBeTrue();
        expect($product->isProduct())->toBeFalse();
    });
});

describe('Product hasExternalProduct method', function (): void {
    test('returns true when external_product_id is set', function (): void {
        $product = Product::factory()->withStripe()->create();

        expect($product->hasExternalProduct())->toBeTrue();
    });

    test('returns false when external_product_id is null', function (): void {
        $product = Product::factory()->withoutStripe()->create();

        expect($product->hasExternalProduct())->toBeFalse();
    });
});

describe('Product averageRating attribute (from Reviewable trait)', function (): void {
    test('returns 0 when no approved reviews', function (): void {
        $product = Product::factory()->create();

        expect($product->average_rating)->toBe(0);
    });

    test('calculates average from approved reviews', function (): void {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Comment::factory()->approved()->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'created_by' => $user->id,
            'rating' => 5,
        ]);

        Comment::factory()->approved()->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'created_by' => $user->id,
            'rating' => 3,
        ]);

        $product->refresh();

        expect($product->average_rating)->toBe(4.0);
    });
});

describe('Product files relationship (from HasFiles trait)', function (): void {
    test('returns empty collection when product has no files', function (): void {
        $product = Product::factory()->create();

        expect($product->files)->toBeEmpty();
    });

    test('returns files attached to product', function (): void {
        $product = Product::factory()->create();

        $file = $product->files()->create([
            'name' => 'test-file.pdf',
            'path' => 'products/test-file.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ]);

        $files = $product->files;

        expect($files)->toHaveCount(1);
        expect($files->first()->id)->toBe($file->id);
    });

    test('file relationship returns single file', function (): void {
        $product = Product::factory()->create();

        $product->files()->create([
            'name' => 'single-file.pdf',
            'path' => 'products/single-file.pdf',
            'mime' => 'application/pdf',
            'size' => 512,
        ]);

        expect($product->file)->not->toBeNull();
        expect($product->file->name)->toBe('single-file.pdf');
    });
});

describe('Product images relationship (from HasImages trait)', function (): void {
    test('returns empty collection when product has no images', function (): void {
        $product = Product::factory()->create();

        expect($product->images)->toBeEmpty();
    });

    test('returns images attached to product', function (): void {
        $product = Product::factory()->create();

        $image = $product->images()->create([
            'path' => 'products/gallery/test-image.jpg',
        ]);

        $images = $product->images;

        expect($images)->toHaveCount(1);
        expect($images->first()->id)->toBe($image->id);
    });

    test('returns multiple images', function (): void {
        $product = Product::factory()->create();

        $product->images()->create(['path' => 'products/gallery/image-1.jpg']);
        $product->images()->create(['path' => 'products/gallery/image-2.jpg']);
        $product->images()->create(['path' => 'products/gallery/image-3.jpg']);

        expect($product->images)->toHaveCount(3);
    });

    test('image relationship returns latest image by id', function (): void {
        $product = Product::factory()->create();

        $product->images()->create(['path' => 'products/gallery/first.jpg']);
        $latest = $product->images()->create(['path' => 'products/gallery/second.jpg']);

        expect($product->image)->not->toBeNull();
        expect($product->image->id)->toBe($latest->id);
    });

    test('does not return images from other products', function (): void {
        $product = Product::factory()->create();
        $otherProduct = Product::factory()->create();

        $product->images()->create(['path' => 'products/gallery/mine.jpg']);
        $otherProduct->images()->create(['path' => 'products/gallery/other.jpg']);

        expect($product->images)->toHaveCount(1);
    });

    test('deleting product cascades to delete images', function (): void {
        $product = Product::factory()->create();

        $product->images()->create(['path' => 'products/gallery/image-1.jpg']);
        $product->images()->create(['path' => 'products/gallery/image-2.jpg']);

        expect(Image::query()->where('imageable_id', $product->id)->count())->toBe(2);

        $product->delete();

        expect(Image::query()->where('imageable_id', $product->id)->count())->toBe(0);
    });

    test('images relationship is MorphMany', function (): void {
        $product = Product::factory()->create();

        expect($product->images())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphMany::class);
    });

    test('image relationship is MorphOne', function (): void {
        $product = Product::factory()->create();

        expect($product->image())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\MorphOne::class);
    });
});

describe('Product inventoryItem relationship (from HasInventory trait)', function (): void {
    test('returns null when product has no inventory item', function (): void {
        $product = Product::factory()->create();

        expect($product->inventoryItem)->toBeNull();
    });

    test('returns inventory item for product', function (): void {
        $product = Product::factory()->create();

        $inventoryItem = InventoryItem::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU',
            'quantity_available' => 10,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'track_inventory' => true,
        ]);

        expect($product->inventoryItem->id)->toBe($inventoryItem->id);
    });

    test('inventoryItem relationship is HasOne', function (): void {
        $product = Product::factory()->create();

        expect($product->inventoryItem())->toBeInstanceOf(Illuminate\Database\Eloquent\Relations\HasOne::class);
    });
});

describe('Product hasStock method (from HasInventory trait)', function (): void {
    test('returns true when no inventory item exists', function (): void {
        $product = Product::factory()->create();

        expect($product->hasStock())->toBeTrue();
    });

    test('returns true when inventory tracking is disabled', function (): void {
        $product = Product::factory()->create();

        InventoryItem::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU',
            'quantity_available' => 0,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'track_inventory' => false,
        ]);

        $product->refresh();

        expect($product->hasStock())->toBeTrue();
    });

    test('returns true when sufficient stock available', function (): void {
        $product = Product::factory()->create();

        InventoryItem::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU',
            'quantity_available' => 10,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'track_inventory' => true,
        ]);

        $product->refresh();

        expect($product->hasStock(5))->toBeTrue();
    });

    test('returns false when insufficient stock', function (): void {
        $product = Product::factory()->create();

        InventoryItem::create([
            'product_id' => $product->id,
            'sku' => 'TEST-SKU',
            'quantity_available' => 2,
            'quantity_reserved' => 0,
            'quantity_damaged' => 0,
            'track_inventory' => true,
            'allow_backorder' => false,
        ]);

        $product->refresh();

        expect($product->hasStock(5))->toBeFalse();
    });
});

describe('Product trait scopes', function (): void {
    test('active scope filters to active products', function (): void {
        Product::factory()->active()->create();
        Product::factory()->inactive()->create();

        $active = Product::query()->active()->get();

        expect($active)->toHaveCount(1);
        expect($active->first()->is_active)->toBeTrue();
    });

    test('inactive scope filters to inactive products', function (): void {
        Product::factory()->active()->create();
        Product::factory()->inactive()->create();

        $inactive = Product::query()->inactive()->get();

        expect($inactive)->toHaveCount(1);
        expect($inactive->first()->is_active)->toBeFalse();
    });

    test('featured scope filters to featured products', function (): void {
        Product::factory()->create(['is_featured' => true]);
        Product::factory()->create(['is_featured' => false]);

        $featured = Product::query()->featured()->get();

        expect($featured)->toHaveCount(1);
        expect($featured->first()->is_featured)->toBeTrue();
    });

    test('notFeatured scope filters to non-featured products', function (): void {
        Product::factory()->create(['is_featured' => true]);
        Product::factory()->create(['is_featured' => false]);

        $notFeatured = Product::query()->notFeatured()->get();

        expect($notFeatured)->toHaveCount(1);
        expect($notFeatured->first()->is_featured)->toBeFalse();
    });

    test('visible scope filters to visible products', function (): void {
        Product::factory()->create(['is_visible' => true]);
        Product::factory()->create(['is_visible' => false]);

        $visible = Product::query()->visible()->get();

        expect($visible)->toHaveCount(1);
        expect($visible->first()->is_visible)->toBeTrue();
    });

    test('hidden scope filters to hidden products', function (): void {
        Product::factory()->create(['is_visible' => true]);
        Product::factory()->create(['is_visible' => false]);

        $hidden = Product::query()->hidden()->get();

        expect($hidden)->toHaveCount(1);
        expect($hidden->first()->is_visible)->toBeFalse();
    });
});

describe('Product trait methods', function (): void {
    test('activate sets product to active', function (): void {
        $product = Product::factory()->inactive()->create();

        expect($product->is_active)->toBeFalse();

        $product->activate();

        expect($product->is_active)->toBeTrue();
    });

    test('deactivate sets product to inactive', function (): void {
        $product = Product::factory()->active()->create();

        expect($product->is_active)->toBeTrue();

        $product->deactivate();

        expect($product->is_active)->toBeFalse();
    });

    test('show sets product to visible', function (): void {
        $product = Product::factory()->create(['is_visible' => false]);

        $product->show();

        expect($product->is_visible)->toBeTrue();
    });

    test('hide sets product to hidden', function (): void {
        $product = Product::factory()->create(['is_visible' => true]);

        $product->hide();

        expect($product->is_visible)->toBeFalse();
    });
});

describe('Product reference ID (from HasReferenceId trait)', function (): void {
    test('generates UUID reference_id on creation', function (): void {
        $product = Product::factory()->create();

        expect($product->reference_id)->not->toBeNull();
        expect($product->reference_id)->toBeString();
    });

    test('reference_id is unique per product', function (): void {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        expect($product1->reference_id)->not->toBe($product2->reference_id);
    });
});

describe('Product slug generation', function (): void {
    test('generates slug from name', function (): void {
        $product = Product::factory()->create(['name' => 'Test Product Name']);

        expect($product->generateSlug())->toBe('test-product-name');
    });

    test('auto-generates slug on creation', function (): void {
        $product = Product::factory()->create(['name' => 'My Cool Product']);

        expect($product->slug)->not->toBeNull();
        expect($product->slug)->not->toBeEmpty();
    });
});
