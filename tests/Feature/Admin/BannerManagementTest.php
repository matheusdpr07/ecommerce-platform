<?php

use App\Models\Banner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('customers cannot manage banners', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.banners.index'))
        ->assertForbidden();
});

test('admins can list and filter banners', function () {
    $admin = User::factory()->admin()->create();
    Banner::factory()->create(['title' => 'Banner principal']);
    Banner::factory()->editorial()->create(['title' => 'História editorial']);

    $this->actingAs($admin)
        ->get(route('admin.banners.index', ['placement' => 'editorial']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Banners/Index')
            ->has('banners.data', 1)
            ->where('banners.data.0.title', 'História editorial')
        );
});

test('admins can create banners with image', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.banners.store'), [
            'title' => 'Coleção essencial',
            'eyebrow' => 'Novidades',
            'description' => 'Uma história cuidadosamente selecionada.',
            'image' => UploadedFile::fake()->image('banner.jpg', 1200, 900),
            'image_alt' => 'Produtos da nova coleção',
            'cta_label' => 'Descobrir agora',
            'cta_url' => '/?sort=newest',
            'theme' => 'ink',
            'placement' => 'hero',
            'is_active' => true,
            'starts_at' => null,
            'ends_at' => null,
            'sort_order' => 1,
        ])
        ->assertRedirect(route('admin.banners.index'));

    $banner = Banner::query()->where('title', 'Coleção essencial')->firstOrFail();

    expect($banner->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($banner->image_path);
    $this->assertDatabaseHas('admin_audit_logs', [
        'action' => 'banner.created',
        'auditable_id' => $banner->id,
    ]);
});

test('admins can replace banner image and update schedule', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    Storage::disk('public')->put('banners/old.jpg', 'old');
    $banner = Banner::factory()->create(['image_path' => 'banners/old.jpg']);

    $this->actingAs($admin)
        ->post(route('admin.banners.update', $banner), [
            '_method' => 'put',
            'title' => 'Banner atualizado',
            'eyebrow' => '',
            'description' => '',
            'image' => UploadedFile::fake()->image('new.jpg', 1200, 900),
            'image_alt' => 'Nova composição',
            'cta_label' => 'Ver catálogo',
            'cta_url' => '/',
            'theme' => 'accent',
            'placement' => 'editorial',
            'is_active' => true,
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'sort_order' => 3,
            'remove_image' => false,
        ])
        ->assertRedirect(route('admin.banners.index'));

    $banner->refresh();

    expect($banner->title)->toBe('Banner atualizado')
        ->and($banner->placement)->toBe('editorial')
        ->and($banner->image_path)->not->toBe('banners/old.jpg');
    Storage::disk('public')->assertMissing('banners/old.jpg');
    Storage::disk('public')->assertExists($banner->image_path);
});

test('storefront receives only active banners inside their schedule', function () {
    Banner::factory()->create(['title' => 'Banner disponível']);
    Banner::factory()->inactive()->create(['title' => 'Banner inativo']);
    Banner::factory()->create([
        'title' => 'Banner futuro',
        'starts_at' => now()->addDay(),
    ]);
    Banner::factory()->create([
        'title' => 'Banner expirado',
        'ends_at' => now()->subDay(),
    ]);

    $this->get(route('store.home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('banners', 1)
            ->where('banners.0.title', 'Banner disponível')
        );
});

test('admins can delete banners and their image', function () {
    Storage::fake('public');
    $admin = User::factory()->admin()->create();
    Storage::disk('public')->put('banners/delete.jpg', 'image');
    $banner = Banner::factory()->create(['image_path' => 'banners/delete.jpg']);

    $this->actingAs($admin)
        ->delete(route('admin.banners.destroy', $banner))
        ->assertRedirect(route('admin.banners.index'));

    $this->assertDatabaseMissing('banners', ['id' => $banner->id]);
    Storage::disk('public')->assertMissing('banners/delete.jpg');
});
