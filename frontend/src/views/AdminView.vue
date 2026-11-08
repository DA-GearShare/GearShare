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

    <p v-if="usersStore.error" class="message-error">{{ usersStore.error }}</p>

    <p v-if="usersStore.isLoading" class="meta">Lädt ...</p>
    <p v-else-if="usersStore.users.length === 0" class="meta">Keine Benutzer vorhanden.</p>
    <ul v-else class="card-list user-list">
        <li v-for="u in usersStore.users" :key="u.id" class="card card-row user-row">
            <div class="user-info">
                <h3>{{ u.name }}</h3>
                <p class="meta">{{ u.email }}</p>
            </div>

            <div class="user-actions">
                <span v-if="u.isAdmin" class="tag tag-admin">Admin</span>
                <span v-if="u.isTeacher" class="tag">Lehrer</span>
                <span v-if="!u.isAdmin && !u.isTeacher" class="tag">Schüler</span>

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
.user-list {
    padding: 0;
    list-style: none;
}

.user-row {
    flex-wrap: wrap;
}

.user-info {
    flex: 1;
    min-width: 200px;
}

.user-info h3,
.user-info p {
    margin: 0;
}

.user-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: var(--space-s);
}

.tag-admin {
    color: var(--color-surface);
    background: var(--color-navy);
}
</style>
