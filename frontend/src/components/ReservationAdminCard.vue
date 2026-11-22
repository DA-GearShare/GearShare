<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useUsersStore } from '@/stores/UsersStore.ts';
import { useEquipmentStore, type EquipmentPhoto } from "@/stores/EquipmentStore.ts";
import type { Reservation } from '@/types/reservation';

const props = defineProps<{
    reservation: Reservation;
    selected: boolean;
}>();

const emit = defineEmits<{
    open: [id: string];
}>();

const photos = ref<(EquipmentPhoto | null)[]>([]);
const equipmentStore = useEquipmentStore();
const userStore = useUsersStore();
const username = ref('…');

const shownItems = computed(() => {
    return props.reservation.items.slice(0, 3);
});

onMounted(async () => {
    const user = await userStore.fetchUserById(props.reservation.requestedByUserId);
    username.value = user?.name ?? props.reservation.requestedByUserId;
    photos.value = await Promise.all(
        shownItems.value.map(item => {
            if (!item.requestedEquipmentId) return null;
            return equipmentStore.fetchMainPhoto(item.requestedEquipmentId);
        })
    );
});

function pad(n: number) {
    return String(n).padStart(2, '0');
}

function formatRange(startText: string, endText: string) {
    const start = new Date(startText);
    const end = new Date(endText);
    const sameMonth = start.getMonth() === end.getMonth() && start.getFullYear() === end.getFullYear();

    if (sameMonth) {
        const month = pad(start.getMonth() + 1) + '.' + start.getFullYear();
        if (start.getDate() === end.getDate()) {
            return pad(start.getDate()) + '.' + month;
        }
        return pad(start.getDate()) + '.–' + pad(end.getDate()) + '.' + month;
    }
    return start.toLocaleDateString('de-AT') + ' – ' + end.toLocaleDateString('de-AT');
}

function formatTime(text: string) {
    return new Date(text).toLocaleTimeString('de-AT', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <article class="card reservation" :class="{ selected: selected }">
        <div class="top">
            <div class="date-box">
                <strong>{{ formatRange(reservation.startDatetime, reservation.endDatetime) }}</strong>
                <span class="meta">{{ formatTime(reservation.startDatetime) }}–{{ formatTime(reservation.endDatetime) }}</span>
                <span class="user">{{ username }}</span>
            </div>

            <div class="thumbs">
                <div v-for="(item, i) in shownItems" :key="item.id" class="thumb-tile">
                    <img
                        v-if="photos[i]"
                        :src="photos[i]?.url"
                        alt="Gerätefoto"
                        class="thumb-image"
                    />
                    <span v-else>IMG</span>
                </div>
            </div>
        </div>

        <div class="bottom">
            <span class="hint"> </span>
            <button type="button" class="open-button" @click="emit('open', reservation.id)">
                <span>{{ reservation.items.length }} Geräte</span>
                <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </article>
</template>

<style scoped>
.reservation {
    padding: var(--space-m);
}

.reservation.selected {
    background: var(--color-info);
    border-color: var(--color-accent);
}

.top {
    display: flex;
    gap: var(--space-s);
}

.date-box {
    display: flex;
    flex-direction: column;
    width: 150px;
    gap: var(--space-xs);
    flex-shrink: 0;
    padding: var(--space-m);
    color: var(--color-text-primary);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.user {
    font-size: var(--text-label-size);
}

.thumbs {
    display: flex;
    flex: 1;
    gap: var(--space-s);
    min-width: 0;
}

.thumb-tile {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    min-width: 0;
    max-width: 100px;
    font-size: var(--text-help-size);
    color: var(--color-muted);
    background: var(--color-soft-surface);
    border-radius: var(--radius-tab);
}

.thumb-image {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: var(--space-s);
}

.open-button {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
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
    transition: transform 0.15s;
}

.selected .chevron-icon {
    transform: rotate(90deg);
}
</style>
