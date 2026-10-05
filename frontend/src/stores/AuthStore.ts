import { ref, computed } from 'vue';
import { defineStore } from 'pinia';

export type Role = 'student' | 'teacher' | 'admin';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<{ name: string; email: string; role: Role } | null>(null);

    const isLoggedIn = computed(() => {
        return user.value !== null;
    });

    async function fetchUser() {
        try {
            const res = await fetch('/api/v1/auth/me', {
                credentials: 'include',
            });
            user.value = res.ok ? await res.json() : null;
        } catch {
            user.value = null;
        }
    }

    async function logout() {
        try {
            await fetch('/api/v1/auth/logout', {
                method: 'POST',
                credentials: 'include',
            });
        } finally {
            user.value = null;
        }
    }

    function hasRole(roles?: Role[]) {
        if (!roles || roles.length === 0) return true;
        return user.value !== null && roles.includes(user.value.role);
    }

    return { user, isLoggedIn, hasRole, fetchUser, logout };
});
