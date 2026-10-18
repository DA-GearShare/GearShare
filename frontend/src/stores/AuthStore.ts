import { ref, computed } from 'vue';
import { defineStore } from 'pinia';

export type Role = 'Schüler' | 'Lehrer' | 'Admin';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<{ id: string; email: string; displayname: string; isAdmin: boolean; isTeacher: boolean; } | null>(null);
    const error = ref<string | null>(null);

    const role = computed<Role | null>(() => {
        if (!user.value) return null;
        if (user.value.isAdmin) return 'Admin';
        if (user.value.isTeacher) return 'Lehrer';
        return 'Schüler';
    });

    const isLoggedIn = computed(() => {
        return user.value !== null;
    });

    async function fetchUser() {
        error.value = null;
        try {
            const res = await fetch('/api/v1/auth/me', {
                credentials: 'include',
            });
            user.value = res.ok ? await res.json() : null;
        } catch {
            user.value = null;
            error.value = "Benutzer konnte nicht geladen werden.";
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
        return role.value !== null && roles.includes(role.value);
    }

    return { user, isLoggedIn, role, error, hasRole, fetchUser, logout };
});
