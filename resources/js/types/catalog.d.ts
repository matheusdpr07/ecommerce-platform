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

export interface StoreCategoryOption {
    id: number;
    name: string;
    slug: string;
}

export interface StoreCatalogFilters {
    search: string;
    category: string;
    brand: string;
    sort: string;
    min_price: string;
    max_price: string;
}

export interface StoreProductImage {
    url: string;
    alt_text?: string | null;
}

export interface StoreProductSummary {
    id: number;
    name: string;
    slug: string;
    category?: Pick<Category, 'id' | 'name' | 'slug'> | null;
    brand?: Pick<Brand, 'id' | 'name' | 'slug'> | null;
    min_price_cents: number;
    max_price_cents: number;
    min_original_price_cents?: number | null;
    has_promotion?: boolean;
    has_stock: boolean;
    primary_image?: StoreProductImage | null;
}

export interface StoreProductVariant {
    id: number;
    sku: string;
    name: string;
    price_cents: number;
    original_price_cents?: number | null;
    has_promotion?: boolean;
    compare_at_price_cents?: number | null;
    stock_quantity: number;
    in_stock: boolean;
}

export interface StoreProductDetail {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    meta_title?: string | null;
    meta_description?: string | null;
    category?: Pick<Category, 'id' | 'name' | 'slug'> | null;
    brand?: Pick<Brand, 'id' | 'name' | 'slug'> | null;
    variants: StoreProductVariant[];
    images: Array<{
        id: number;
        url: string;
        alt_text?: string | null;
        is_primary: boolean;
    }>;
}

export interface CartItemPayload {
    id: number;
    quantity: number;
    unit_price_cents: number;
    original_unit_price_cents?: number;
    has_promotion?: boolean;
    line_total_cents: number;
    is_available: boolean;
    max_quantity: number;
    variant: {
        id: number;
        sku: string;
        name: string;
        stock_quantity: number;
    };
    product: {
        id: number;
        name: string;
        slug: string;
        primary_image?: StoreProductImage | null;
    };
}

export interface CartPayload {
    item_count: number;
    subtotal_cents: number;
    discount_cents: number;
    total_cents: number;
    coupon?: {
        code: string;
        name: string;
        discount_cents: number;
    } | null;
    items: CartItemPayload[];
}

export interface Coupon {
    id: number;
    code: string;
    name: string;
    type: 'percentage' | 'fixed_amount';
    value: number;
    min_order_cents?: number | null;
    max_discount_cents?: number | null;
    usage_limit?: number | null;
    usage_count: number;
    starts_at?: string | null;
    expires_at?: string | null;
    is_active: boolean;
}

export interface Promotion {
    id: number;
    name: string;
    type: 'percentage' | 'fixed_amount';
    value: number;
    scope: 'all_products' | 'category' | 'brand' | 'product';
    category_id?: number | null;
    brand_id?: number | null;
    product_id?: number | null;
    priority: number;
    starts_at?: string | null;
    expires_at?: string | null;
    is_active: boolean;
    category?: Pick<Category, 'id' | 'name'> | null;
    brand?: Pick<Brand, 'id' | 'name'> | null;
    product?: Pick<ProductListItem, 'id' | 'name'> | null;
}

export interface PromotionScopeOption {
    value: Promotion['scope'];
    label: string;
}

export interface CartSummary {
    item_count: number;
    subtotal_cents: number;
}

export interface WishlistItemPayload {
    id: number;
    product: {
        id: number;
        name: string;
        slug: string;
        min_price_cents: number;
        has_stock: boolean;
        primary_image?: StoreProductImage | null;
        category?: Pick<Category, 'name' | 'slug'> | null;
    };
}

export interface WishlistPayload {
    item_count: number;
    items: WishlistItemPayload[];
}

export interface WishlistSummary {
    item_count: number;
}

export interface CustomerAddress {
    id: number;
    label: string;
    recipient_name: string;
    recipient_phone?: string | null;
    postal_code: string;
    formatted_postal_code: string;
    street: string;
    number: string;
    complement?: string | null;
    neighborhood: string;
    city: string;
    state: string;
    is_default: boolean;
    summary: string;
}

export interface ShippingMethodItem {
    id: number;
    name: string;
    description?: string | null;
    price_cents: number;
    free_above_cents?: number | null;
    min_order_cents?: number | null;
    max_order_cents?: number | null;
    estimated_days_min?: number | null;
    estimated_days_max?: number | null;
    sort_order: number;
    is_active: boolean;
}

export interface CheckoutShippingOption {
    id: number;
    name: string;
    description?: string | null;
    price_cents: number;
    estimated_days_min?: number | null;
    estimated_days_max?: number | null;
}

export interface CheckoutPayload {
    cart: CartPayload;
    addresses: CustomerAddress[];
    shipping_methods: CheckoutShippingOption[];
    selected_address_id?: number | null;
    selected_shipping_method_id?: number | null;
    shipping?: CheckoutShippingOption | null;
    shipping_cents: number;
    grand_total_cents: number;
    is_ready: boolean;
}

export interface OrderSummary {
    id: number;
    number: string;
    status: string;
    status_label: string;
    item_count: number;
    total_cents: number;
    placed_at?: string | null;
}

export interface OrderItemDetail {
    id: number;
    product_name: string;
    product_slug: string;
    variant_name: string;
    variant_sku: string;
    quantity: number;
    unit_price_cents: number;
    original_unit_price_cents?: number | null;
    line_total_cents: number;
}

export interface OrderDetail {
    id: number;
    number: string;
    status: string;
    status_label: string;
    subtotal_cents: number;
    discount_cents: number;
    shipping_cents: number;
    total_cents: number;
    coupon?: {
        code: string;
        name?: string | null;
    } | null;
    shipping_method_name: string;
    shipping_address: {
        recipient_name: string;
        recipient_phone?: string | null;
        postal_code: string;
        street: string;
        number: string;
        complement?: string | null;
        neighborhood: string;
        city: string;
        state: string;
        summary: string;
    };
    placed_at?: string | null;
    items: OrderItemDetail[];
}
