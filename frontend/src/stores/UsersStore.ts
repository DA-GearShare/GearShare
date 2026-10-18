import { ref, computed } from 'vue';
import { defineStore } from 'pinia';

export type User = { id: string; email: string; name: string; is_admin: boolean; is_teacher: boolean; };

export const useUsersStore  = defineStore('users', () => {
    const users = ref<User[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    async function fetchUsers() {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch('/api/v1/users', { credentials: 'include' });
            console.log('Status:', res.status);
            const data = await res.json();
            console.log('Antwort:', data);
            if (!res.ok) throw new Error('Status ' + res.status);
            users.value = data.items;
        } catch {
            users.value = [];
            error.value = 'Die Benutzer konnten nicht geladen werden.';
        } finally {
            isLoading.value = false;
        }
    }

    async function setAdmin(user: User, is_admin: boolean) {
        error.value = null;
        try {
            const res = await fetch(`/api/v1/users/${user.id}/roles`, {
                method: 'PATCH',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_admin: is_admin, is_teacher: user.is_teacher }),
            });
            if (!res.ok) throw new Error('Status ' + res.status);
            await fetchUsers();
        } catch {
            error.value = 'Die Rolle konnte nicht geändert werden.';
        }
    }

    return { users, isLoading, error, fetchUsers, setAdmin };
});
