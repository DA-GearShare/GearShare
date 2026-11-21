<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useEquipmentStore } from '@/stores/EquipmentStore.ts';

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
    scanGlobal: [];
    scanItem: [item: ReservationItem];
}>();

const equipmentStore = useEquipmentStore();
const equipmentNames = ref<Record<string, string>>({});

onMounted(async () => {
    for (const item of props.reservation.items) {
        const id = item.requestedEquipmentId;
        if (!id) {
            continue;
        }
        const equipment = await equipmentStore.fetchEquipmentById(id);
        if (equipment) {
            equipmentNames.value[id] = equipment.name;
        }
    }
});

function getName(item: ReservationItem) {
    if (!item.requestedEquipmentId) {
        return 'Unbekanntes Gerät';
    }
    return equipmentNames.value[item.requestedEquipmentId] || 'Wird geladen…';
}
</script>

<template>
    <section class="card detail">
        <div class="devices-header">
            <h2>Geräte</h2>
            <button type="button" class="btn-primary" @click="emit('scanGlobal')">Scannen</button>
        </div>

        <div class="card-list">
            <article v-for="item in reservation.items" :key="item.id" class="card card-row">
                <div class="thumb thumb-small">IMG</div>
                <div>
                    <strong>{{ getName(item) }}</strong>
                    <div class="meta">{{ item.status }}</div>
                </div>
                <button type="button" class="btn-secondary scan-button" :disabled="item.status !== 'requested'" @click="emit('scanItem', item)">
                    Scan
                </button>
            </article>
        </div>
    </section>
</template>

<style scoped>
.detail {
    padding: var(--space-l);
}

.devices-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
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

.scan-button {
    margin-left: auto;
    min-height: 40px;
}
</style>
