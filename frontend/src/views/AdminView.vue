<script setup lang="ts">
import { useUsersStore } from '@/stores/UsersStore.ts';
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/AuthStore.ts';

const auth = useAuthStore();
const usersStore = useUsersStore();

onMounted(usersStore.fetchUsers);
</script>

<template>
    <h1>Admin Seite</h1>

    <p v-if="usersStore.error" class="message">{{ usersStore.error }}</p>

    <p v-if="usersStore.isLoading" class="message">Lädt ...</p>
    <p v-else-if="usersStore.users.length === 0" class="message">Keine Benutzer vorhanden.</p>
    <ul v-else class="user-list">
        <li v-for="u in usersStore.users" :key="u.id" class="user-row">
            <div class="user-info">
                <span class="user-name">{{ u.name }}</span>
                <span class="user-email">{{ u.email }}</span>
            </div>

            <div class="user-actions">
                <span v-if="u.isAdmin" class="badge badge-admin">Admin</span>
                <span v-if="u.isTeacher" class="badge">Lehrer</span>
                <span v-else class="badge">Schüler</span>

                <template v-if="u.id !== auth.user?.id">
                    <button v-if="!u.isAdmin" @click="usersStore.setAdmin(u, true)" class="btn-primary">
                        Zum Admin machen
                    </button>
                    <button v-else @click="usersStore.setAdmin(u, false)" class="btn-primary">
                        Admin entziehen
                    </button>
                </template>
            </div>
        </li>
    </ul>
</template>

<style scoped>
.message {
    color: var(--color-muted);
}

.user-list {
    list-style: none;
    margin: 1.5rem 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.user-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem 1.25rem;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 8px;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.user-name {
    font-weight: 600;
    color: var(--color-text-primary);
}

.user-email {
    font-size: 0.875rem;
    color: var(--color-muted);
}

.user-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.badge {
    padding: 0.25rem 0.625rem;
    font-size: 0.8125rem;
    color: var(--color-text-secondary);
    background: var(--color-soft-surface);
    border-radius: 999px;
}

.badge-admin {
    color: var(--color-surface);
    background: var(--color-navy);
}

@media (max-width: 600px) {
    .user-row {
        flex-direction: column;
        align-items: flex-start;
    }
}
</style>
