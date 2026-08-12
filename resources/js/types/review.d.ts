export type ReviewStatus = 'pending' | 'approved' | 'rejected';

export interface StoreReview {
    id: number;
    rating: number;
    title?: string | null;
    body: string;
    reviewer_name: string;
    is_verified_purchase: boolean;
    created_at?: string | null;
}

export interface ExistingReview {
    id: number;
    rating: number;
    title?: string | null;
    body: string;
    status: ReviewStatus;
    status_label: string;
    moderation_notes?: string | null;
}

export interface ProductReviewsPayload {
    summary: {
        average: number;
        total: number;
        distribution: Array<{
            rating: number;
            count: number;
            percentage: number;
        }>;
    };
    items: StoreReview[];
    eligibility: {
        can_review: boolean;
        can_edit: boolean;
        reason?: string | null;
        existing_review?: ExistingReview | null;
    };
}

export interface AdminReview {
    id: number;
    rating: number;
    title?: string | null;
    body: string;
    status: ReviewStatus;
    status_label: string;
    is_verified_purchase: boolean;
    moderation_notes?: string | null;
    created_at?: string | null;
    user: {
        id: number;
        name: string;
        email: string;
    };
    product: {
        id: number;
        name: string;
        slug: string;
    };
}

export interface ReviewFilters {
    search: string;
    status: string;
    rating: string;
}
