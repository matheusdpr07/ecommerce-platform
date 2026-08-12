export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    role: 'customer' | 'admin';
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
    store: {
        name: string;
        eyebrow: string;
        tagline: string;
        support_email?: string | null;
    };
    cart: {
        item_count: number;
        subtotal_cents: number;
    };
    wishlist: {
        item_count: number;
    };
    notifications: {
        unread_count: number;
    };
};
