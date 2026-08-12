export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    parent_id?: number | null;
    is_active: boolean;
    sort_order: number;
    meta_title?: string | null;
    meta_description?: string | null;
    parent?: Pick<Category, 'id' | 'name'> | null;
}

export interface Brand {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    is_active: boolean;
    meta_title?: string | null;
    meta_description?: string | null;
}

export interface CategoryOption {
    id: number;
    name: string;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface CatalogFilters {
    search: string;
    status: string;
    category_id?: string;
    brand_id?: string;
}

export interface ProductVariantForm {
    id?: number;
    sku: string;
    name: string;
    price: string;
    compare_at_price: string;
    stock_quantity: string;
    is_active: boolean;
    sort_order: string;
}

export interface ProductImageItem {
    id: number;
    url: string;
    alt_text?: string | null;
    sort_order: number;
    is_primary: boolean;
}

export interface ProductVariant {
    id: number;
    sku: string;
    name: string;
    price_cents: number;
    compare_at_price_cents?: number | null;
    stock_quantity: number;
    is_active: boolean;
    sort_order: number;
}

export interface ProductListItem {
    id: number;
    name: string;
    slug: string;
    is_active: boolean;
    category?: Pick<Category, 'id' | 'name'> | null;
    brand?: Pick<Brand, 'id' | 'name'> | null;
    variants: ProductVariant[];
}

export interface ProductFormData {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    category_id: number;
    brand_id?: number | null;
    is_active: boolean;
    meta_title?: string | null;
    meta_description?: string | null;
    variants: ProductVariant[];
    images: ProductImageItem[];
}
