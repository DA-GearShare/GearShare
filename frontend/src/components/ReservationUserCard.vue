<script setup lang="ts">
import { ref } from 'vue';
import { useEquipmentStore } from '@/stores/EquipmentStore';

export type ReservationStatus =
    'requested' | 'accepted' | 'denied' | 'cancelled' | 'active' | 'returned';

export interface ReservationItem {
    id: string;
    reservationId: string;
    requestedEquipmentId: string | null;
    requestedPoolId: string | null;
    status: string;
    issuedAt: string | null;
    returnedAt: string | null;
}

export interface Reservation {
    id: string;
    requestedByUserId: string;
    responsibleUserId: string | null;
    status: ReservationStatus;
    startDatetime: string;
    endDatetime: string;
    isOverdue: boolean;
    createdAt: string;
    items: ReservationItem[];
}

const props = defineProps<{
    reservation: Reservation;
}>();

const equipmentStore = useEquipmentStore();
const expanded = ref(false);
const equipmentById = ref<Record<string, { name: string; category: string }>>({});

function formatDate(text: string) {
    return new Date(text).toLocaleDateString('de-AT');
}

function formatTime(text: string) {
    return new Date(text).toLocaleTimeString('de-AT', { hour: '2-digit', minute: '2-digit' });
}

async function toggle() {
    expanded.value = !expanded.value;

    if (!expanded.value) {
        return;
    }

    for (const item of props.reservation.items) {
        const id = item.requestedEquipmentId;

        if (!id || equipmentById.value[id]) {
            continue;
        }

        const equipment = await equipmentStore.fetchEquipmentById(id);
        if (equipment) {
            equipmentById.value[id] = { name: equipment.name, category: equipment.category };
        }
    }
}
</script>

<template>
    <article class="card reservation">
        <div class="card-row">
            <div class="date-box">
                <strong>{{ formatDate(reservation.startDatetime) }} – {{ formatDate(reservation.endDatetime) }}</strong>
                <span class="meta">{{ formatTime(reservation.startDatetime) }}–{{ formatTime(reservation.endDatetime) }}</span>
            </div>

            <div class="thumbs">
                <div v-for="item in reservation.items.slice(0, 3)" :key="item.id" class="thumb thumb-small">IMG</div>
            </div>

            <button v-if="reservation.status === 'active'" type="button" class="btn-secondary photo-button" aria-label="Zustandsfotos">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 8h3l2-3h6l2 3h3v11H4z" />
                    <circle cx="12" cy="13" r="3.5" />
                </svg>
            </button>
        </div>

        <button type="button" class="toggle" :aria-expanded="expanded" @click="toggle">
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

        <ul v-if="expanded" class="item-list">
            <li v-for="item in reservation.items" :key="item.id" class="card-row item-row">
                <div class="thumb thumb-small">IMG</div>
                <div>
                    <strong>
                        {{ (item.requestedEquipmentId && equipmentById[item.requestedEquipmentId]?.name) || 'Wird geladen…' }}
                    </strong>
                    <div class="meta">
                        {{ item.requestedEquipmentId && equipmentById[item.requestedEquipmentId]?.category }}
                    </div>
                </div>
            </li>
        </ul>
    </article>
</template>

<style scoped>
.reservation {
    padding: var(--space-m) var(--space-l);
}

.date-box {
    display: flex;
    flex-direction: column;
    padding: var(--space-s) var(--space-m);
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

.photo-button {
    min-height: 40px;
    margin-left: auto;
    padding: 0 var(--space-m);
}

.toggle {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    margin: var(--space-s) 0 0 auto;
    padding: 0;
    font: inherit;
    font-size: var(--text-label-size);
    font-weight: var(--font-bold);
    color: var(--color-text-primary);
    background: none;
    border: none;
    cursor: pointer;
}

.chevron-icon {
    color: var(--color-accent);
    transition: transform 0.15s;
}

.chevron-icon.open {
    transform: rotate(90deg);
}

.item-list {
    display: flex;
    flex-direction: column;
    gap: var(--space-m);
    margin: var(--space-m) 0 0;
    padding: var(--space-m) 0 0;
    list-style: none;
    border-top: 1px solid var(--color-border);
}
</style>
