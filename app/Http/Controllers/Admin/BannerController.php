<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Models\Banner;
use App\Services\AdminAuditService;
use App\Services\BannerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BannerController extends Controller
{
    public function __construct(
        private readonly BannerService $bannerService,
        private readonly AdminAuditService $auditService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Banner::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $placement = $request->string('placement')->trim()->toString();

        $banners = Banner::query()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(in_array($placement, ['hero', 'editorial'], true), fn ($query) => $query->where('placement', $placement))
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Banner $banner) => $this->transformBanner($banner));

        return Inertia::render('Admin/Banners/Index', [
            'banners' => $banners,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'placement' => $placement,
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Banner::class);

        return Inertia::render('Admin/Banners/Create');
    }

    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $banner = $this->bannerService->create(
            $request->validated(),
            $request->file('image'),
        );

        $this->auditService->record(
            $request->user(),
            'banner.created',
            $banner,
            "Banner {$banner->title} criado.",
        );

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner criado com sucesso.');
    }

    public function edit(Banner $banner): Response
    {
        $this->authorize('update', $banner);

        return Inertia::render('Admin/Banners/Edit', [
            'banner' => $this->transformBanner($banner),
        ]);
    }

    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $banner = $this->bannerService->update(
            $banner,
            $request->validated(),
            $request->file('image'),
        );

        $this->auditService->record(
            $request->user(),
            'banner.updated',
            $banner,
            "Banner {$banner->title} atualizado.",
        );

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner atualizado com sucesso.');
    }

    public function destroy(Request $request, Banner $banner): RedirectResponse
    {
        $this->authorize('delete', $banner);

        $title = $banner->title;

        $this->auditService->record(
            $request->user(),
            'banner.deleted',
            $banner,
            "Banner {$title} excluído.",
        );
        $this->bannerService->delete($banner);

        return redirect()
            ->route('admin.banners.index')
            ->with('success', 'Banner excluído com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function transformBanner(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'eyebrow' => $banner->eyebrow,
            'description' => $banner->description,
            'image_url' => $banner->imageUrl(),
            'image_alt' => $banner->image_alt,
            'cta_label' => $banner->cta_label,
            'cta_url' => $banner->cta_url,
            'theme' => $banner->theme,
            'placement' => $banner->placement,
            'is_active' => $banner->is_active,
            'starts_at' => $banner->starts_at?->format('Y-m-d\TH:i'),
            'ends_at' => $banner->ends_at?->format('Y-m-d\TH:i'),
            'sort_order' => $banner->sort_order,
        ];
    }
}
