<?php

test('application home page is accessible', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('Store/Products/Index'));
});

test('application boots successfully', function () {
    expect(app()->bound('config'))->toBeTrue();
    expect(config('app.name'))->not->toBeEmpty();
});
