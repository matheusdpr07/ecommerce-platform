<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

test('registration creates customer users only', function () {
    $response = $this->post('/register', [
        'name' => 'Cliente Teste',
        'email' => 'cliente@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::Admin->value,
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'cliente@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Customer);
});

test('profile update cannot change user role', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Nome Atualizado',
            'email' => $user->email,
            'role' => UserRole::Admin->value,
        ]);

    $response->assertRedirect(route('profile.edit', absolute: false));

    expect($user->fresh()->role)->toBe(UserRole::Customer);
});

test('admin promote command promotes an existing user', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
    ]);

    $exitCode = Artisan::call('admin:promote', [
        'email' => 'admin@example.com',
    ]);

    expect($exitCode)->toBe(0)
        ->and($user->fresh()->role)->toBe(UserRole::Admin);
});

test('admin promote command rejects unverified users without force', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'nao-verificado@example.com',
    ]);

    $exitCode = Artisan::call('admin:promote', [
        'email' => 'nao-verificado@example.com',
    ]);

    expect($exitCode)->toBe(1)
        ->and($user->fresh()->role)->toBe(UserRole::Customer);
});

test('user policy allows admins to access admin panel', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->can('accessAdminPanel', User::class))->toBeTrue();
});

test('user policy denies customers access to admin panel', function () {
    $customer = User::factory()->create();

    expect($customer->can('accessAdminPanel', User::class))->toBeFalse();
});
