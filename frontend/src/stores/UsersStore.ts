import { ref, computed } from 'vue';
import { defineStore } from 'pinia';

export type User = { id: string; email: string; displayName: string; isAdmin: boolean; isTeacher: boolean; };

export const useUsersStore  = defineStore('users', () => {
    const users = ref<User[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    async function fetchUsers() {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch('/api/v1/users', { credentials: 'include' });
            if (!res.ok) throw new Error('Status ' + res.status);
            const data = await res.json();
            users.value = data.items;
        } catch {
            users.value = [];
            error.value = 'Die Benutzer konnten nicht geladen werden.';
        } finally {
            isLoading.value = false;
        }
    }

    async function setAdmin(user: User, isAdmin: boolean) {
        error.value = null;
        try {
            const res = await fetch(`/api/v1/users/${user.id}/roles`, {
                method: 'PATCH',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ isAdmin: isAdmin, isTeacher: user.isTeacher }),
            });
            if (!res.ok) throw new Error('Status ' + res.status);
            await fetchUsers();
        } catch {
            error.value = 'Die Rolle konnte nicht geändert werden.';
        }
    }

    return { users, isLoading, error, fetchUsers, setAdmin };
});
