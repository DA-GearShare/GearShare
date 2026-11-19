<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ChevronLeft } from 'lucide-vue-next';
import { useUsersStore } from '@/stores/UsersStore.ts';
import { useAuthStore } from '@/stores/AuthStore.ts';
import { roleOf, displayName, initials, type UserRole } from '@/utils/userRoles.ts';

const route = useRoute();
const router = useRouter();
const usersStore = useUsersStore();
const auth = useAuthStore();

const id = route.params.id as string;
const selected = ref<UserRole>('student');
const saving = ref(false);
const error = ref<string | null>(null);

const options: { value: UserRole; title: string; text: string }[] = [
    { value: 'student', title: 'Schüler/in', text: 'Inventar ansehen und eigene Reservierungen' },
    { value: 'teacher', title: 'Lehrkraft', text: 'Geräteausgabe und Reservierungen betreuen' },
    { value: 'admin', title: 'Admin', text: 'Vollzugriff auf GearShare' },
];

const user = computed(() => {
    return usersStore.users.find((u) => {
        return u.id === id;
    }) ?? null;
});

const isSelf = computed(() => {
    return auth.user?.id === id;
});

const unchanged = computed(() => {
    return user.value !== null && roleOf(user.value) === selected.value;
});

watch(user, (value) => {
    if (value) {
        selected.value = roleOf(value);
    }
}, { immediate: true });

onMounted(async () => {
    if (usersStore.users.length === 0) {
        await usersStore.fetchUsers();
    }
});

async function save() {
    error.value = null;
    saving.value = true;

    const result = await usersStore.setRole(
        id,
        selected.value === 'admin',
        selected.value === 'teacher',
    );

    saving.value = false;

    if (result.ok) {
        router.push('/admin/nutzer');
    } else {
        error.value = result.message;
    }
}
</script>

<template>
    <RouterLink to="/admin/nutzer" class="back-link">
        <ChevronLeft />
        Zur Nutzerverwaltung
    </RouterLink>

    <p class="eyebrow">Administration / Nutzer</p>
    <h1>Rolle verwalten</h1>
    <p class="meta">Rolle auswählen und zuweisen</p>

    <p v-if="usersStore.isLoading && !user" class="meta">Lädt ...</p>
    <p v-else-if="!user" class="message-error">Diese Person wurde nicht gefunden.</p>

    <template v-else>
        <div class="card card-row person">
            <div class="avatar" :class="[`avatar-${roleOf(user)}`, { 'avatar-self': isSelf }]">
                {{ initials(user) }}
            </div>
            <div>
                <h3>{{ displayName(user) }}</h3>
                <p class="meta">{{ user.email }}</p>
            </div>
        </div>

        <form class="section-card role-form" @submit.prevent="save">
            <div>
                <h2 id="role-title">Rolle auswählen</h2>
                <p class="meta">
                    Jede Rolle hat festgelegte Rechte. Individuelle Berechtigungen sind nicht vorgesehen.
                </p>
            </div>

            <div class="role-options" role="radiogroup" aria-labelledby="role-title">
                <label
                    v-for="option in options"
                    :key="option.value"
                    class="role-option"
                    :class="{
                        'role-option-selected': selected === option.value,
                        'role-option-disabled': isSelf && option.value !== 'admin',
                    }"
                >
                    <input
                        v-model="selected"
                        type="radio"
                        name="role"
                        :value="option.value"
                        :disabled="isSelf && option.value !== 'admin'"
                    >
                    <span class="role-text">
                        <span class="role-title">{{ option.title }}</span>
                        <span class="meta">{{ option.text }}</span>
                    </span>
                </label>
            </div>

            <p v-if="isSelf" class="message-info">Du kannst deine eigene Admin-Rolle nicht entfernen.</p>
            <p v-if="error" class="message-error">{{ error }}</p>

            <div class="role-actions">
                <RouterLink to="/admin/nutzer" class="btn-secondary">Abbrechen</RouterLink>
                <button type="submit" class="btn-primary" :disabled="saving || unchanged">
                    {{ saving ? 'Speichert ...' : 'Änderungen speichern' }}
                </button>
            </div>
        </form>
    </template>
</template>

<style scoped>
.back-link {
    display: inline-flex;
    align-items: center;
    gap: var(--space-xs);
    min-height: 44px;
    font-size: var(--text-meta-size);
    font-weight: var(--font-bold);
    text-decoration: none;
}

.back-link svg {
    width: 16px;
    height: 16px;
}

.person {
    margin: var(--space-l) 0;
}

.person h3,
.person p {
    margin: 0;
}

.role-form {
    display: flex;
    flex-direction: column;
    gap: var(--space-l);
}

.role-form h2,
.role-form p {
    margin: 0;
}

.role-options {
    display: flex;
    flex-direction: column;
    gap: var(--space-s);
}

.role-option {
    flex-direction: row;
    align-items: center;
    gap: var(--space-m);
    padding: var(--space-m) var(--space-l);
    font-size: var(--text-body-size);
    line-height: var(--text-body-line);
    font-weight: var(--font-regular);
    color: var(--color-text-secondary);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-tab);
    cursor: pointer;
}

.role-option-selected {
    background: var(--color-info);
    border-color: var(--color-navy);
}

.role-option-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.role-option input {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    min-height: 0;
    padding: 0;
    accent-color: var(--color-navy);
}

.role-text {
    display: flex;
    flex-direction: column;
}

.role-title {
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
}

.role-actions {
    display: flex;
    gap: var(--space-s);
}

.role-actions .btn-secondary {
    flex: 1;
}

.role-actions .btn-primary {
    flex: 1.5;
}
</style>
