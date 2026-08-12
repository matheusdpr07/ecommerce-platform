export interface Banner {
    id: number;
    title: string;
    eyebrow?: string | null;
    description?: string | null;
    image_url?: string | null;
    image_alt?: string | null;
    cta_label?: string | null;
    cta_url?: string | null;
    theme: 'paper' | 'ink' | 'accent';
    placement: 'hero' | 'editorial';
    is_active: boolean;
    starts_at?: string | null;
    ends_at?: string | null;
    sort_order: number;
}

export interface StoreBanner {
    id: number;
    title: string;
    eyebrow?: string | null;
    description?: string | null;
    image_url?: string | null;
    image_alt?: string | null;
    cta_label?: string | null;
    cta_url?: string | null;
    theme: Banner['theme'];
    placement: Banner['placement'];
}

export interface BannerFilters {
    search: string;
    status: string;
    placement: string;
}

export interface BannerFormData {
    title: string;
    eyebrow: string;
    description: string;
    image: File | null;
    image_alt: string;
    cta_label: string;
    cta_url: string;
    theme: Banner['theme'];
    placement: Banner['placement'];
    is_active: boolean;
    starts_at: string;
    ends_at: string;
    sort_order: number;
    remove_image: boolean;
}
