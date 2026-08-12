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
}
