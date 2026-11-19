<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Pencil } from 'lucide-vue-next';
import { useUsersStore, type User } from '@/stores/UsersStore.ts';
import { useAuthStore } from '@/stores/AuthStore.ts';
import { roleOf, displayName, initials, ROLE_LABELS, type UserRole } from '@/utils/userRoles.ts';

type Filter = 'all' | UserRole;

const auth = useAuthStore();
const usersStore = useUsersStore();

const filters: { value: Filter; label: string }[] = [
    { value: 'all', label: 'Alle' },
    { value: 'admin', label: 'Admins' },
    { value: 'teacher', label: 'Lehrkräfte' },
    { value: 'student', label: 'Schüler/innen' },
];

const search = ref('');
const activeFilter = ref<Filter>('all');

// eslint-disable-next-line @typescript-eslint/naming-convention
const ROLE_ORDER: Record<UserRole, number> = {
    admin: 0,
    teacher: 1,
    student: 2,
};

const visibleUsers = computed(() => {
    const term = search.value.trim().toLowerCase();

    return usersStore.users
        .filter((u) => {
            const matchesRole = activeFilter.value === 'all' || roleOf(u) === activeFilter.value;
            const matchesSearch = term === ''
                || displayName(u).toLowerCase().includes(term)
                || u.email.toLowerCase().includes(term);
            return matchesRole && matchesSearch;
        })
        .sort((a, b) => {
            if (isSelf(a)) return -1;
            if (isSelf(b)) return 1;

            const byRole = ROLE_ORDER[roleOf(a)] - ROLE_ORDER[roleOf(b)];
            if (byRole !== 0) return byRole;

            return displayName(a).localeCompare(displayName(b), 'de');
        });
});

function isSelf(user: User) {
    return user.id === auth.user?.id;
}

onMounted(usersStore.fetchUsers);
</script>

<template>
    <p class="eyebrow">Administration</p>
    <h1>Nutzerverwaltung</h1>
    <p class="meta">Rollen für GearShare festlegen und verwalten.</p>

    <input
        v-model="search"
        type="search"
        class="user-search"
        placeholder="Name oder E-Mail suchen"
        aria-label="Name oder E-Mail suchen"
    >

    <div class="chips" role="group" aria-label="Nach Rolle filtern">
        <button
            v-for="f in filters"
            :key="f.value"
            type="button"
            class="chip"
            :class="{ 'chip-active': activeFilter === f.value }"
            :aria-pressed="activeFilter === f.value"
            @click="activeFilter = f.value"
        >
            {{ f.label }}
        </button>
    </div>

    <p v-if="usersStore.error" class="message-error">{{ usersStore.error }}</p>

    <div class="list-header">
        <h2>Personen</h2>
        <span class="meta">{{ visibleUsers.length }} von {{ usersStore.users.length }}</span>
    </div>

    <p v-if="usersStore.isLoading" class="meta">Lädt ...</p>
    <p v-else-if="visibleUsers.length === 0" class="meta">Keine Personen gefunden.</p>

    <ul v-else class="card-list user-list">
        <li v-for="u in visibleUsers" :key="u.id" class="card user-card">
            <div class="user-top">
                <div class="avatar" :class="[`avatar-${roleOf(u)}`, { 'avatar-self': isSelf(u) }]">
                    {{ initials(u) }}
                </div>

                <div class="user-info">
                    <h3>{{ displayName(u) + (isSelf(u) ? ' · Du' : '') }}</h3>
                    <p class="meta">{{ u.email }}</p>
                </div>

                <RouterLink
                    :to="`/admin/nutzer/${u.id}`"
                    class="icon-link"
                    :aria-label="`Rolle von ${displayName(u)} bearbeiten`"
                >
                    <Pencil />
                </RouterLink>
            </div>

            <div class="user-bottom">
                <span
                    class="tag"
                    :class="{ 'tag-admin': roleOf(u) === 'admin', 'tag-teacher': roleOf(u) === 'teacher' }"
                >
                    {{ ROLE_LABELS[roleOf(u)] }}
                </span>
                <span class="meta">{{ isSelf(u) ? 'Eigenes Konto' : 'Rolle zuweisen' }}</span>
            </div>
        </li>
    </ul>

    <p class="meta footer-hint">Wähle eine Person aus, um ihre Rolle festzulegen.</p>
</template>

<style scoped>
.user-search {
    width: 100%;
    min-height: 52px;
    margin-top: var(--space-l);
    padding: 0 var(--space-l);
    border-radius: var(--radius-pill);
}

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-s);
    margin: var(--space-m) 0 var(--space-xl);
}

.chip {
    min-height: 40px;
    padding: 0 var(--space-l);
    font: inherit;
    font-size: var(--text-label-size);
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-pill);
    cursor: pointer;
}

.chip:hover {
    background: var(--color-soft-surface);
}

.chip-active,
.chip-active:hover {
    color: var(--color-surface);
    background: var(--color-navy);
    border-color: var(--color-navy);
}

.list-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
}

.list-header h2 {
    margin: 0;
}

.user-list {
    padding: 0;
    margin-top: var(--space-m);
    list-style: none;
}

.user-card {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
}

.user-top {
    display: flex;
    align-items: center;
    gap: var(--space-m);
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-info h3,
.user-info p {
    margin: 0;
    overflow-wrap: anywhere;
}

.icon-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    color: var(--color-text-primary);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-tab);
}

.icon-link:hover {
    background: var(--color-soft-surface);
}

.icon-link svg {
    width: 18px;
    height: 18px;
}

.user-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: var(--space-m);
}

.user-bottom .meta {
    margin: 0;
}

.footer-hint {
    margin-top: var(--space-xl);
    text-align: center;
}
</style>
