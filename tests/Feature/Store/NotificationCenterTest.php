<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\CustomerActivityNotification;
use App\Services\CustomerNotificationService;
use Illuminate\Support\Facades\Notification;

function customerActivityNotification(array $overrides = []): CustomerActivityNotification
{
    return new CustomerActivityNotification([
        'type' => 'order.updated',
        'title' => 'Pedido atualizado',
        'message' => 'Há uma nova etapa no seu pedido.',
        'action_url' => '/orders/1',
        'action_label' => 'Ver pedido',
        'tone' => 'neutral',
        ...$overrides,
    ]);
}

test('notification center requires authentication', function () {
    $this->get(route('store.notifications.index'))
        ->assertRedirect(route('login'));
});

test('customers can see only their notifications', function () {
    $customer = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $customer->notifyNow(customerActivityNotification(), ['database']);
    $otherCustomer->notifyNow(customerActivityNotification([
        'title' => 'Notificação de outra pessoa',
    ]), ['database']);

    $this->actingAs($customer)
        ->get(route('store.notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Notifications/Index')
            ->has('customerNotifications.data', 1)
            ->where('customerNotifications.data.0.title', 'Pedido atualizado')
        );
});

test('unread notification count is shared with the storefront', function () {
    $customer = User::factory()->create();
    $customer->notifyNow(customerActivityNotification(), ['database']);
    $customer->notifyNow(customerActivityNotification(), ['database']);

    $this->actingAs($customer)
        ->get(route('store.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('notifications.unread_count', 2)
        );
});

test('opening a notification marks it as read and follows its action', function () {
    $customer = User::factory()->create();
    $customer->notifyNow(customerActivityNotification([
        'action_url' => '/orders/99',
    ]), ['database']);
    $notification = $customer->notifications()->firstOrFail();

    $this->actingAs($customer)
        ->patch(route('store.notifications.update', $notification->id))
        ->assertRedirect('/orders/99');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('customers cannot mark notifications from another account', function () {
    $customer = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $otherCustomer->notifyNow(customerActivityNotification(), ['database']);
    $notification = $otherCustomer->notifications()->firstOrFail();

    $this->actingAs($customer)
        ->patch(route('store.notifications.update', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('customers can mark all notifications as read', function () {
    $customer = User::factory()->create();
    $customer->notifyNow(customerActivityNotification(), ['database']);
    $customer->notifyNow(customerActivityNotification(), ['database']);

    $this->actingAs($customer)
        ->patch(route('store.notifications.read-all'))
        ->assertRedirect();

    expect($customer->fresh()->unreadNotifications()->count())->toBe(0);
});

test('order lifecycle notification uses database and email channels', function () {
    Notification::fake();
    $customer = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $customer->id,
        'status' => OrderStatus::Paid,
    ]);

    app(CustomerNotificationService::class)->orderStatusChanged($order);

    Notification::assertSentTo(
        $customer,
        CustomerActivityNotification::class,
        function (CustomerActivityNotification $notification, array $channels) use ($customer): bool {
            $content = $notification->toArray($customer);

            return $channels === ['database', 'mail']
                && $content['type'] === 'payment.approved'
                && $content['tone'] === 'success';
        },
    );
});
