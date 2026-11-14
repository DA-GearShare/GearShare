import type { User } from '@/stores/UsersStore.ts';

export type UserRole = 'admin' | 'teacher' | 'student';

export const ROLE_LABELS: Record<UserRole, string> = {
    admin: 'Admin',
    teacher: 'Lehrkraft',
    student: 'Schüler/in',
};

export function roleOf(user: User): UserRole {
    if (user.isAdmin) return 'admin';
    if (user.isTeacher) return 'teacher';
    return 'student';
}

export function displayName(user: User) {
    return user.name?.trim() || user.email;
}

export function initials(user: User) {
    const words = displayName(user).split(/\s+/).filter(Boolean);
    return words.slice(0, 2).map((word) => {
        return word.charAt(0).toUpperCase();
    }).join('');
}
