<?php

use App\Models\Address;
use App\Models\User;

test('guests cannot manage addresses', function () {
    $this->get(route('store.addresses.index'))
        ->assertRedirect(route('login', absolute: false));
});

test('customers can list their addresses', function () {
    $user = User::factory()->create();
    Address::factory()->for($user)->count(2)->create();

    $this->actingAs($user)
        ->get(route('store.addresses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Store/Addresses/Index')
            ->has('addresses', 2)
        );
});

test('customers can create addresses', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->post(route('store.addresses.store'), [
            'label' => 'Casa',
            'recipient_name' => 'Maria Silva',
            'recipient_phone' => '11999999999',
            'postal_code' => '01310-100',
            'street' => 'Avenida Paulista',
            'number' => '1000',
            'complement' => 'Apto 12',
            'neighborhood' => 'Bela Vista',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'is_default' => true,
        ]);

    $response->assertRedirect(route('store.addresses.index'));

    $this->assertDatabaseHas('addresses', [
        'user_id' => $user->id,
        'postal_code' => '01310100',
        'is_default' => true,
    ]);
});

test('customers can update their addresses', function () {
    $user = User::factory()->create();
    $address = Address::factory()->for($user)->create(['label' => 'Casa']);

    $this->actingAs($user)
        ->put(route('store.addresses.update', $address), [
            'label' => 'Trabalho',
            'recipient_name' => $address->recipient_name,
            'recipient_phone' => null,
            'postal_code' => '01310-100',
            'street' => $address->street,
            'number' => $address->number,
            'complement' => null,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'is_default' => true,
        ])
        ->assertRedirect(route('store.addresses.index'));

    expect($address->fresh()->label)->toBe('Trabalho');
});

test('customers cannot update another users address', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $address = Address::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->put(route('store.addresses.update', $address), [
            'label' => 'Invasao',
            'recipient_name' => 'Hacker',
            'recipient_phone' => null,
            'postal_code' => '01310-100',
            'street' => 'Rua X',
            'number' => '1',
            'complement' => null,
            'neighborhood' => 'Centro',
            'city' => 'Sao Paulo',
            'state' => 'SP',
            'is_default' => true,
        ])
        ->assertForbidden();
});

test('customers can delete their addresses', function () {
    $user = User::factory()->create();
    $address = Address::factory()->for($user)->create();

    $this->actingAs($user)
        ->delete(route('store.addresses.destroy', $address))
        ->assertRedirect(route('store.addresses.index'));

    $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
});
