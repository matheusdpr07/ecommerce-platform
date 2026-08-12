import type { User } from '@/types';

export function userIsAdmin(user: User | null | undefined): user is User {
    return user?.role === 'admin';
}
