<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;

test('customers cannot access administrative customer records', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.customers.index'))
        ->assertForbidden();
});

test('admins can filter customers and see accurate net spending', function () {
    $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);
    $customer = User::factory()->create([
        'name' => 'Cliente Comprador',
        'email' => 'comprador@example.com',
    ]);
    User::factory()->unverified()->create([
        'name' => 'Cliente Sem Pedido',
        'email' => 'sem-pedido@example.com',
    ]);

    $approvedOrder = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Paid,
        'total_cents' => 10000,
    ]);
    Payment::factory()->for($approvedOrder)->create([
        'status' => PaymentStatus::Approved,
        'amount_cents' => 10000,
    ]);
    $partialOrder = Order::factory()->for($customer)->create([
        'status' => OrderStatus::PartiallyRefunded,
        'total_cents' => 10000,
    ]);
    Payment::factory()->for($partialOrder)->create([
        'status' => PaymentStatus::PartiallyRefunded,
        'amount_cents' => 10000,
        'refunded_amount_cents' => 3000,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.customers.index', [
            'search' => 'comprador',
            'verification' => 'verified',
            'activity' => 'with_orders',
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Customers/Index')
            ->has('customers.data', 1)
            ->where('customers.data.0.id', $customer->id)
            ->where('customers.data.0.orders_count', 2)
            ->where('customers.data.0.net_spent_cents', 17000)
        );
});

test('admins can inspect customer addresses orders and purchase summary', function () {
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create(['name' => 'Maria Cliente']);
    Address::factory()->for($customer)->create([
        'label' => 'Casa',
        'is_default' => true,
    ]);

    $firstOrder = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Paid,
        'total_cents' => 12000,
    ]);
    Payment::factory()->for($firstOrder)->create([
        'status' => PaymentStatus::Approved,
        'amount_cents' => 12000,
    ]);
    $secondOrder = Order::factory()->for($customer)->create([
        'status' => OrderStatus::Refunded,
        'total_cents' => 8000,
    ]);
    Payment::factory()->for($secondOrder)->create([
        'status' => PaymentStatus::Refunded,
        'amount_cents' => 8000,
        'refunded_amount_cents' => 8000,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Customers/Show')
            ->where('customer.id', $customer->id)
            ->where('customer.name', 'Maria Cliente')
            ->missing('customer.password')
            ->missing('customer.role')
            ->has('customer.addresses', 1)
            ->has('orders.data', 2)
            ->where('summary.orders_count', 2)
            ->where('summary.paid_orders_count', 2)
            ->where('summary.net_spent_cents', 12000)
            ->where('summary.average_ticket_cents', 6000)
        );
});

test('administrative users are not exposed as customers', function () {
    $admin = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.customers.show', $otherAdmin))
        ->assertNotFound();
});
