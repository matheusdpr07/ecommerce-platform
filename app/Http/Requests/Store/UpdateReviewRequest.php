<?php

namespace App\Http\Requests\Store;

use App\Models\Review;

class UpdateReviewRequest extends StoreReviewRequest
{
    public function authorize(): bool
    {
        /** @var Review $review */
        $review = $this->route('review');

        return $this->user()?->can('update', $review) ?? false;
    }
}
