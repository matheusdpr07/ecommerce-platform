<?php

use App\Models\User;

test('guests are redirected from admin panel', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('login', absolute: false));
});

test('customers cannot access admin panel', function () {
    $customer = User::factory()->create();

    $response = $this
        ->actingAs($customer)
        ->get(route('admin.dashboard'));

    $response->assertForbidden();
});

test('admins can access admin panel', function () {
    $admin = User::factory()->admin()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.dashboard'));

    $response->assertOk();
});

test('unverified admins cannot access admin panel', function () {
    $admin = User::factory()->admin()->unverified()->create();

    $response = $this
        ->actingAs($admin)
        ->get(route('admin.dashboard'));

    $response->assertRedirect(route('verification.notice', absolute: false));
});
