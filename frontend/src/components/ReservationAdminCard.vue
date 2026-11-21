<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useUsersStore } from '@/stores/UsersStore.ts';
import type { Reservation } from '@/types/reservation';

const props = defineProps<{
    reservation: Reservation;
    selected: boolean;
}>();

const emit = defineEmits<{
    open: [id: string];
}>();

const userStore = useUsersStore();
const username = ref('…');

onMounted(async () => {
    const user = await userStore.fetchUserById(props.reservation.requestedByUserId);
    username.value = user?.name ?? props.reservation.requestedByUserId;
});

function formatDate(text: string) {
    return new Date(text).toLocaleDateString('de-AT');
}

function formatTime(text: string) {
    return new Date(text).toLocaleTimeString('de-AT', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <article class="card reservation" :class="{ selected: selected }">
        <div class="card-row">
            <div class="date-box">
                <strong>{{ formatDate(reservation.startDatetime) }} – {{ formatDate(reservation.endDatetime) }}</strong>
                <span class="meta">{{ formatTime(reservation.startDatetime) }}–{{ formatTime(reservation.endDatetime) }}</span>
                <span>{{ username }}</span>
            </div>

            <div class="thumbs">
                <div v-for="item in reservation.items.slice(0, 3)" :key="item.id" class="thumb thumb-small">IMG</div>
            </div>
        </div>

        <button type="button" class="open-button" @click="emit('open', reservation.id)">
            <span>{{ reservation.items.length }} Geräte</span>
            <svg
                class="chevron-icon"
                :class="{ open: expanded }"
                width="14"
                height="14"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
            >
                <path d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </article>
</template>

<style scoped>
.reservation {
    padding: var(--space-m) var(--space-l);
}

.reservation.selected {
    background: var(--color-info);
    border-color: var(--color-accent);
}

.date-box {
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
    padding: var(--space-m);
    color: var(--color-text-primary);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.thumbs {
    display: flex;
    gap: var(--space-s);
}

.thumb-small {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    font-size: var(--text-help-size);
    color: var(--color-muted);
}

.open-button {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    margin: var(--space-s) 0 0 auto;
    padding: 0;
    font: inherit;
    font-size: var(--text-label-size);
    font-weight: var(--font-bold);
    color: var(--color-accent);
    background: none;
    border: none;
    cursor: pointer;
}

.chevron-icon {
    color: var(--color-accent);
    transition: transform 0.15s;
}
</style>
