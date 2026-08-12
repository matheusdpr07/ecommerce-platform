<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModerateReviewRequest;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Review::class);

        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $rating = $request->integer('rating');

        return Inertia::render('Admin/Reviews/Index', [
            'reviews' => $this->reviewService->paginateForAdmin(
                $search,
                $status,
                in_array($rating, range(1, 5), true) ? $rating : null,
            ),
            'filters' => [
                'search' => $search,
                'status' => $status,
                'rating' => $rating ? (string) $rating : '',
            ],
        ]);
    }

    public function update(ModerateReviewRequest $request, Review $review): RedirectResponse
    {
        $data = $request->validated();

        $this->reviewService->moderate(
            $review,
            ReviewStatus::from($data['status']),
            $data['moderation_notes'] ?? null,
            $request->user(),
        );

        return back()->with('success', 'Moderação da avaliação atualizada.');
    }
}
