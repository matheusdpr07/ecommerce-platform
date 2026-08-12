<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

function createDeliveredReviewPurchase(User $user, Product $product): OrderItem
{
    $variant = $product->variants()->firstOrFail();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'status' => OrderStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Delivered,
        'delivered_at' => now(),
    ]);

    return OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_name' => $product->name,
        'product_slug' => $product->slug,
        'variant_name' => $variant->name,
        'variant_sku' => $variant->sku,
    ]);
}

test('guests must authenticate before reviewing a product', function () {
    $product = createStorefrontProduct();

    $this->post(route('store.products.reviews.store', $product), [
        'rating' => 5,
        'body' => 'Uma experiência excelente.',
    ])->assertRedirect(route('login'));
});

test('customers can only review products from delivered purchases', function () {
    $customer = User::factory()->create();
    $product = createStorefrontProduct();

    $this->actingAs($customer)
        ->post(route('store.products.reviews.store', $product), [
            'rating' => 5,
            'body' => 'Uma experiência excelente.',
        ])
        ->assertSessionHasErrors('review');

    $this->assertDatabaseCount('reviews', 0);
});

test('customers can submit a verified review after delivery', function () {
    $customer = User::factory()->create();
    $product = createStorefrontProduct();
    $item = createDeliveredReviewPurchase($customer, $product);

    $this->actingAs($customer)
        ->post(route('store.products.reviews.store', $product), [
            'rating' => 5,
            'title' => 'Superou as expectativas',
            'body' => 'Qualidade excelente e uso muito agradável.',
        ])
        ->assertRedirect(route('store.products.show', $product->slug).'#avaliacoes');

    $this->assertDatabaseHas('reviews', [
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'order_item_id' => $item->id,
        'rating' => 5,
        'status' => ReviewStatus::Pending->value,
        'is_verified_purchase' => true,
    ]);
});

test('only approved reviews are shown with aggregated rating', function () {
    $product = createStorefrontProduct();
    $firstCustomer = User::factory()->create(['name' => 'Maria de Souza']);
    $secondCustomer = User::factory()->create();
    $firstItem = createDeliveredReviewPurchase($firstCustomer, $product);
    $secondItem = createDeliveredReviewPurchase($secondCustomer, $product);

    Review::factory()->create([
        'user_id' => $firstCustomer->id,
        'product_id' => $product->id,
        'order_item_id' => $firstItem->id,
        'rating' => 5,
        'body' => 'Produto excelente e muito bem acabado.',
        'status' => ReviewStatus::Approved,
    ]);
    Review::factory()->create([
        'user_id' => $secondCustomer->id,
        'product_id' => $product->id,
        'order_item_id' => $secondItem->id,
        'rating' => 1,
        'body' => 'Conteúdo ainda aguardando moderação.',
        'status' => ReviewStatus::Pending,
    ]);

    $this->get(route('store.products.show', $product->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('reviews.summary.average', 5)
            ->where('reviews.summary.total', 1)
            ->has('reviews.items', 1)
            ->where('reviews.items.0.reviewer_name', 'Maria S.')
            ->where('reviews.items.0.is_verified_purchase', true)
        );
});

test('editing a published review sends it back to moderation', function () {
    $customer = User::factory()->create();
    $product = createStorefrontProduct();
    $item = createDeliveredReviewPurchase($customer, $product);
    $review = Review::factory()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'order_item_id' => $item->id,
        'status' => ReviewStatus::Approved,
        'moderated_at' => now(),
    ]);

    $this->actingAs($customer)
        ->put(route('store.reviews.update', $review), [
            'rating' => 4,
            'title' => 'Atualização',
            'body' => 'Depois de mais tempo de uso, continuo gostando.',
        ])
        ->assertRedirect(route('store.products.show', $product->slug).'#avaliacoes');

    expect($review->fresh()->status)->toBe(ReviewStatus::Pending)
        ->and($review->fresh()->rating)->toBe(4)
        ->and($review->fresh()->moderated_at)->toBeNull();
});

test('admins can moderate reviews and customers cannot', function () {
    $customer = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $product = createStorefrontProduct();
    $item = createDeliveredReviewPurchase($customer, $product);
    $review = Review::factory()->create([
        'user_id' => $customer->id,
        'product_id' => $product->id,
        'order_item_id' => $item->id,
        'status' => ReviewStatus::Pending,
    ]);

    $this->actingAs($customer)
        ->patch(route('admin.reviews.update', $review), [
            'status' => 'approved',
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->patch(route('admin.reviews.update', $review), [
            'status' => 'approved',
            'moderation_notes' => 'Conteúdo verificado.',
        ])
        ->assertRedirect();

    expect($review->fresh()->status)->toBe(ReviewStatus::Approved)
        ->and($review->fresh()->moderated_by)->toBe($admin->id);
    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'review.moderated',
        'auditable_id' => $review->id,
    ]);
});
