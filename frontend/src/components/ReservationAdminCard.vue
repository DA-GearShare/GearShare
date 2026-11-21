<script setup lang="ts">
import { useUsersStore } from "@/stores/UsersStore.ts";
import { ref, onMounted } from "vue";
import {useEquipmentStore} from "@/stores/EquipmentStore.ts";

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

const emit = defineEmits<{
    open: [id: string];
}>();

const userStore = useUsersStore();
const username = ref('…');

onMounted(async () => {
    const user = await userStore.fetchUserById(props.reservation.requestedByUserId);
    console.log('user:', user);
    username.value = user?.name ?? props.reservation.requestedByUserId;
});

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
                <p>{{ username }}</p>
            </div>

            <div class="thumbs">
                <div v-for="item in reservation.items.slice(0, 3)" :key="item.id" class="thumb thumb-small">IMG</div>
            </div>
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
.reservation-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: 12px;
}

.reservation-info h2 {
    margin: 0 0 12px;
    font-size: 18px;
}

.reservation-info p {
    margin: 6px 0;
}

@media (max-width: 600px) {
    .reservation-card {
        flex-direction: column;
        align-items: stretch;
    }
}

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
