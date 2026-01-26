<?php

declare(strict_types=1);

use App\Enums\ProductApprovalStatus;
use App\Models\Group;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function (): void {
    Group::factory()->asDefaultMemberGroup()->create();
});

// viewAny

it('allows anyone to view any products', function (): void {
    expect(Gate::forUser(null)->check('viewAny', Product::class))->toBeTrue();

    $user = User::factory()->create();
    expect(Gate::forUser($user)->check('viewAny', Product::class))->toBeTrue();
});

// view - approval status

it('allows viewing approved active product with active category', function (): void {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
    ]);
    $product->categories()->attach($category);

    expect(Gate::forUser(null)->check('view', $product))->toBeTrue();
});

it('denies viewing pending product', function (): void {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Pending,
        'is_active' => true,
    ]);
    $product->categories()->attach($category);

    expect(Gate::forUser(null)->check('view', $product))->toBeFalse();
});

it('denies viewing rejected product', function (): void {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Rejected,
        'is_active' => true,
    ]);
    $product->categories()->attach($category);

    expect(Gate::forUser(null)->check('view', $product))->toBeFalse();
});

// view - active status

it('denies viewing inactive product', function (): void {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => false,
    ]);
    $product->categories()->attach($category);

    expect(Gate::forUser(null)->check('view', $product))->toBeFalse();
});

// view - categories

it('allows viewing product with no categories', function (): void {
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
    ]);

    expect(Gate::forUser(null)->check('view', $product))->toBeTrue();
});

it('allows viewing product when at least one category is active', function (): void {
    $activeCategory = ProductCategory::factory()->create(['is_active' => true]);
    $inactiveCategory = ProductCategory::factory()->create(['is_active' => false]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
    ]);
    $product->categories()->attach([$activeCategory->id, $inactiveCategory->id]);

    expect(Gate::forUser(null)->check('view', $product))->toBeTrue();
});

it('denies viewing product when all categories are inactive', function (): void {
    $inactiveCategory1 = ProductCategory::factory()->create(['is_active' => false]);
    $inactiveCategory2 = ProductCategory::factory()->create(['is_active' => false]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
    ]);
    $product->categories()->attach([$inactiveCategory1->id, $inactiveCategory2->id]);

    expect(Gate::forUser(null)->check('view', $product))->toBeFalse();
});

// view - combined conditions

it('denies viewing inactive and unapproved product', function (): void {
    $category = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Pending,
        'is_active' => false,
    ]);
    $product->categories()->attach($category);

    expect(Gate::forUser(null)->check('view', $product))->toBeFalse();
});

it('allows guest to view approved active product', function (): void {
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
    ]);

    expect(Gate::forUser(null)->check('view', $product))->toBeTrue();
});

it('allows authenticated user to view approved active product', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
    ]);

    expect(Gate::forUser($user)->check('view', $product))->toBeTrue();
});

it('denies viewing product with multiple active categories when none are viewable', function (): void {
    $inactiveCategory = ProductCategory::factory()->create(['is_active' => false]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
    ]);
    $product->categories()->attach($inactiveCategory);

    expect(Gate::forUser(null)->check('view', $product))->toBeFalse();
});

it('allows viewing product with single active category', function (): void {
    $activeCategory = ProductCategory::factory()->create(['is_active' => true]);
    $product = Product::factory()->create([
        'approval_status' => ProductApprovalStatus::Approved,
        'is_active' => true,
    ]);
    $product->categories()->attach($activeCategory);

    expect(Gate::forUser(null)->check('view', $product))->toBeTrue();
});
