<?php

declare(strict_types=1);

use App\Enums\WarningConsequenceType;
use App\Http\Requests\Store\StoreReviewRequest;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\UserWarning;
use App\Models\Warning;
use App\Models\WarningConsequence;
use Illuminate\Support\Facades\Auth;

describe('StoreReviewRequest validation', function (): void {
    test('validation passes with valid content and rating', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when content is missing', function (): void {
        $request = new StoreReviewRequest([
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation fails when content is empty', function (): void {
        $request = new StoreReviewRequest([
            'content' => '',
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation fails when content is too short', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'Short',
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation passes when content is at minimum length', function (): void {
        $request = new StoreReviewRequest([
            'content' => str_repeat('A', 10),
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when content is too long', function (): void {
        $request = new StoreReviewRequest([
            'content' => str_repeat('A', 1001),
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation passes when content is at maximum length', function (): void {
        $request = new StoreReviewRequest([
            'content' => str_repeat('A', 1000),
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when content is not a string', function (): void {
        $request = new StoreReviewRequest([
            'content' => 12345,
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('content'))->toBeTrue();
    });

    test('validation fails when rating is missing', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('rating'))->toBeTrue();
    });

    test('validation fails when rating is below minimum', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 0,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('rating'))->toBeTrue();
    });

    test('validation fails when rating is above maximum', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 6,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('rating'))->toBeTrue();
    });

    test('validation passes with minimum rating', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 1,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation passes with maximum rating', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->passes())->toBeTrue();
    });

    test('validation fails when rating is not an integer', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 'five',
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('rating'))->toBeTrue();
    });

    test('validation fails when rating is a decimal', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 3.5,
        ]);

        $validator = validator($request->all(), $request->rules());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->has('rating'))->toBeTrue();
    });
});

describe('StoreReviewRequest custom messages', function (): void {
    test('content required message is customized', function (): void {
        $request = new StoreReviewRequest([
            'content' => '',
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('content'))->toBe('Please provide a review.');
    });

    test('content min message is customized', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'Short',
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('content'))->toBe('The review must be at least 10 characters.');
    });

    test('content max message is customized', function (): void {
        $request = new StoreReviewRequest([
            'content' => str_repeat('A', 1001),
            'rating' => 5,
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('content'))->toBe('The review cannot exceed 1,000 characters.');
    });

    test('rating required message is customized', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('rating'))->toBe('Please provide a rating.');
    });

    test('rating integer message is customized', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 'five',
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('rating'))->toBe('The rating must be a valid number.');
    });

    test('rating min message is customized', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 0,
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('rating'))->toBe('The rating must be at least 1 star.');
    });

    test('rating max message is customized', function (): void {
        $request = new StoreReviewRequest([
            'content' => 'This is a great product!',
            'rating' => 6,
        ]);

        $validator = validator($request->all(), $request->rules(), $request->messages());

        expect($validator->fails())->toBeTrue();
        expect($validator->errors()->first('rating'))->toBe('The rating cannot exceed 5 stars.');
    });
});

describe('StoreReviewRequest authorization', function (): void {
    test('authorize returns true when user is authenticated', function (): void {
        $user = User::factory()->create();
        Auth::login($user);

        $request = new StoreReviewRequest;

        expect($request->authorize())->toBeTrue();

        Auth::logout();
    });

    test('authorize returns false when user is guest', function (): void {
        $request = new StoreReviewRequest;

        expect($request->authorize())->toBeFalse();
    });
});

describe('StoreReviewRequest HTTP layer', function (): void {
    test('review can be submitted with valid data', function (): void {
        Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $category = ProductCategory::factory()->create(['is_active' => true]);

        $product = Product::factory()
            ->active()
            ->approved()
            ->hasPrices(1)
            ->create();

        $product->categories()->attach($category);

        $response = $this->actingAs($user)->post(route('store.subscriptions.reviews.store', $product->reference_id), [
            'content' => 'This is an excellent product!',
            'rating' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('message', 'Your review has been submitted successfully.');

        $this->assertDatabaseHas('comments', [
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'content' => 'This is an excellent product!',
            'rating' => 5,
        ]);
    });

    test('review submission requires authentication', function (): void {
        $product = Product::factory()
            ->active()
            ->approved()
            ->hasPrices(1)
            ->create();

        $response = $this->post(route('store.subscriptions.reviews.store', $product->reference_id), [
            'content' => 'This is an excellent product!',
            'rating' => 5,
        ]);

        $response->assertRedirect(route('login'));
    });

    test('review submission fails with validation errors', function (): void {
        Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $category = ProductCategory::factory()->create(['is_active' => true]);

        $product = Product::factory()
            ->active()
            ->approved()
            ->hasPrices(1)
            ->create();

        $product->categories()->attach($category);

        $response = $this->actingAs($user)->post(route('store.subscriptions.reviews.store', $product->reference_id), [
            'content' => '',
            'rating' => 0,
        ]);

        $response->assertSessionHasErrors(['content', 'rating']);
    });

    test('review submission fails when user has already reviewed product', function (): void {
        Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $category = ProductCategory::factory()->create(['is_active' => true]);

        $product = Product::factory()
            ->active()
            ->approved()
            ->hasPrices(1)
            ->create();

        $product->categories()->attach($category);

        Comment::factory()->create([
            'commentable_type' => Product::class,
            'commentable_id' => $product->id,
            'created_by' => $user->id,
            'rating' => 5,
        ]);

        $response = $this->actingAs($user)->post(route('store.subscriptions.reviews.store', $product->reference_id), [
            'content' => 'This is another review!',
            'rating' => 4,
        ]);

        $response->assertSessionHasErrors(['content' => 'You have already submitted a review for this product.']);
    });

    test('review submission fails when user has post restriction warning', function (): void {
        Group::factory()->asDefaultMemberGroup()->create();
        $user = User::factory()->create();

        $warning = Warning::create([
            'name' => 'Test Warning',
            'points' => 10,
            'days_applied' => 30,
            'is_active' => true,
        ]);

        $warningConsequence = WarningConsequence::create([
            'type' => WarningConsequenceType::PostRestriction,
            'threshold' => 5,
            'duration_days' => 7,
            'is_active' => true,
        ]);

        UserWarning::create([
            'user_id' => $user->id,
            'warning_id' => $warning->id,
            'warning_consequence_id' => $warningConsequence->id,
            'points_at_issue' => 10,
            'points_expire_at' => now()->addDays(30),
            'consequence_expires_at' => now()->addDays(7),
        ]);

        $user->refresh();

        $category = ProductCategory::factory()->create(['is_active' => true]);

        $product = Product::factory()
            ->active()
            ->approved()
            ->hasPrices(1)
            ->create();

        $product->categories()->attach($category);

        $response = $this->actingAs($user)->post(route('store.subscriptions.reviews.store', $product->reference_id), [
            'content' => 'This is a great product!',
            'rating' => 5,
        ]);

        $response->assertSessionHasErrors(['content' => 'You have been restricted from posting.']);
    });
});
