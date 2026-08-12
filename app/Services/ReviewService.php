<?php

namespace App\Services;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function __construct(
        private readonly AdminAuditService $auditService,
    ) {}

    /**
     * @param  array{rating: int, title?: string|null, body: string}  $data
     */
    public function create(User $user, Product $product, array $data): Review
    {
        if ($product->reviews()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'review' => 'Você já avaliou este produto. Edite a avaliação existente.',
            ]);
        }

        $orderItem = $this->eligibleOrderItem($user, $product);

        if ($orderItem === null) {
            throw ValidationException::withMessages([
                'review' => 'A avaliação fica disponível após a entrega de uma compra deste produto.',
            ]);
        }

        return $product->reviews()->create([
            ...$data,
            'user_id' => $user->id,
            'order_item_id' => $orderItem->id,
            'status' => ReviewStatus::Pending,
            'is_verified_purchase' => true,
        ]);
    }

    /**
     * @param  array{rating: int, title?: string|null, body: string}  $data
     */
    public function update(Review $review, array $data): Review
    {
        $review->update([
            ...$data,
            'status' => ReviewStatus::Pending,
            'moderation_notes' => null,
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        return $review->refresh();
    }

    public function moderate(
        Review $review,
        ReviewStatus $status,
        ?string $notes,
        User $admin,
    ): Review {
        $review->update([
            'status' => $status,
            'moderation_notes' => $notes,
            'moderated_by' => $admin->id,
            'moderated_at' => now(),
        ]);

        $this->auditService->record(
            $admin,
            'review.moderated',
            $review,
            "Avaliação #{$review->id} marcada como {$status->label()}.",
            ['status' => $status->value],
        );

        return $review->refresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function forProduct(Product $product, ?User $user): array
    {
        $ratingCounts = $product->reviews()
            ->approved()
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $total = (int) $ratingCounts->sum();
        $average = $total > 0
            ? round((float) $product->reviews()->approved()->avg('rating'), 1)
            : 0.0;

        $items = $product->reviews()
            ->approved()
            ->with('user:id,name')
            ->latest('moderated_at')
            ->latest('id')
            ->limit(20)
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'reviewer_name' => $this->publicReviewerName($review->user->name),
                'is_verified_purchase' => $review->is_verified_purchase,
                'created_at' => $review->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'summary' => [
                'average' => $average,
                'total' => $total,
                'distribution' => collect(range(5, 1))->map(fn (int $rating) => [
                    'rating' => $rating,
                    'count' => (int) ($ratingCounts[$rating] ?? 0),
                    'percentage' => $total > 0
                        ? (int) round(((int) ($ratingCounts[$rating] ?? 0) / $total) * 100)
                        : 0,
                ])->all(),
            ],
            'items' => $items,
            'eligibility' => $this->eligibility($product, $user),
        ];
    }

    public function paginateForAdmin(string $search, string $status, ?int $rating): LengthAwarePaginator
    {
        $statusEnum = ReviewStatus::tryFrom($status);

        return Review::query()
            ->with(['user:id,name,email', 'product:id,name,slug'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($statusEnum, fn ($query) => $query->where('status', $statusEnum))
            ->when($rating, fn ($query) => $query->where('rating', $rating))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'title' => $review->title,
                'body' => $review->body,
                'status' => $review->status->value,
                'status_label' => $review->status->label(),
                'is_verified_purchase' => $review->is_verified_purchase,
                'moderation_notes' => $review->moderation_notes,
                'created_at' => $review->created_at?->toIso8601String(),
                'user' => [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                    'email' => $review->user->email,
                ],
                'product' => [
                    'id' => $review->product->id,
                    'name' => $review->product->name,
                    'slug' => $review->product->slug,
                ],
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function eligibility(Product $product, ?User $user): array
    {
        if ($user === null) {
            return [
                'can_review' => false,
                'can_edit' => false,
                'reason' => 'Entre na sua conta para verificar suas compras.',
                'existing_review' => null,
            ];
        }

        $existing = $product->reviews()->where('user_id', $user->id)->first();

        if ($existing) {
            return [
                'can_review' => false,
                'can_edit' => true,
                'reason' => null,
                'existing_review' => [
                    'id' => $existing->id,
                    'rating' => $existing->rating,
                    'title' => $existing->title,
                    'body' => $existing->body,
                    'status' => $existing->status->value,
                    'status_label' => $existing->status->label(),
                    'moderation_notes' => $existing->moderation_notes,
                ],
            ];
        }

        if ($this->eligibleOrderItem($user, $product) === null) {
            return [
                'can_review' => false,
                'can_edit' => false,
                'reason' => 'Você poderá avaliar depois que uma compra deste produto for entregue.',
                'existing_review' => null,
            ];
        }

        return [
            'can_review' => true,
            'can_edit' => false,
            'reason' => null,
            'existing_review' => null,
        ];
    }

    private function eligibleOrderItem(User $user, Product $product): ?OrderItem
    {
        return OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('fulfillment_status', FulfillmentStatus::Delivered)
                ->whereIn('status', [
                    OrderStatus::Paid,
                    OrderStatus::PartiallyRefunded,
                    OrderStatus::Refunded,
                ]))
            ->latest('id')
            ->first();
    }

    private function publicReviewerName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $firstName = $parts[0] ?? 'Cliente';
        $lastInitial = count($parts) > 1
            ? ' '.mb_strtoupper(mb_substr((string) end($parts), 0, 1)).'.'
            : '';

        return $firstName.$lastInitial;
    }
}
