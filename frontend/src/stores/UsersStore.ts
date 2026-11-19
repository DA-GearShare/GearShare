import { ref } from 'vue';
import { defineStore } from 'pinia';

export type User = {
    id: string;
    email: string;
    name: string | null;
    isAdmin: boolean;
    isTeacher: boolean;
};

export const useUsersStore = defineStore('users', () => {
    const users = ref<User[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    async function fetchUsers() {
        isLoading.value = true;
        error.value = null;
        try {
            const res = await fetch('/api/v1/users?pageSize=100', { credentials: 'include' });
            if (!res.ok) throw new Error('Status ' + res.status);
            const data = await res.json();
            users.value = data.items;
        } catch (e) {
            console.error(e);
            users.value = [];
            error.value = 'Die Benutzer konnten nicht geladen werden.';
        } finally {
            isLoading.value = false;
        }
    }

    async function setRole(id: string, isAdmin: boolean, isTeacher: boolean) {
        try {
            const res = await fetch(`/api/v1/users/${id}/roles`, {
                method: 'PATCH',
                credentials: 'include',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ isAdmin, isTeacher }),
            });

            if (res.ok) {
                await fetchUsers();
                return { ok: true, message: '' };
            }
            if (res.status === 409) return { ok: false, message: 'Du kannst deine eigene Admin-Rolle nicht entfernen.' };
            if (res.status === 403) return { ok: false, message: 'Keine Berechtigung.' };
            if (res.status === 404) return { ok: false, message: 'Die Person wurde nicht gefunden.' };
            return { ok: false, message: `Speichern fehlgeschlagen (Status ${res.status}).` };
        } catch (e) {
            console.error(e);
            return { ok: false, message: 'Server nicht erreichbar.' };
        }
    }

    return { users, isLoading, error, fetchUsers, setRole };
});
