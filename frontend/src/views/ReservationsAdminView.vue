<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useReservationStore } from '@/stores/ReservationStore';
import ReservationAdminCard from '@/components/ReservationAdminCard.vue';
import ReservationDetailCard from '@/components/ReservationDetailCard.vue';

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

const reservationStore = useReservationStore();
const selectedId = ref('');

onMounted(() => {
    reservationStore.fetchReservations();
});

const selectedReservation = computed(() => {
    const all: Reservation[] = reservationStore.reservations || [];
    return all.find((r) => {
        return r.id === selectedId.value;
    });
});

function selectReservation(id: string) {
    if (selectedId.value === id) {
        selectedId.value = '';
    } else {
        selectedId.value = id;
    }
}

async function scanGlobal() {
    if (!selectedReservation.value) {
        return;
    }

    const code = prompt('Code scannen:');
    if (!code) {
        return;
    }

    const updated = await reservationStore.fetchIssueGlobal(selectedReservation.value.id, { code });

    if (updated) {
        replaceReservation(updated);
    }
}

function replaceReservation(updated: Reservation) {
    const all: Reservation[] = reservationStore.reservations || [];
    const index = all.findIndex((r) => {
        return r.id === updated.id;
    });

    if (index !== -1) {
        all[index] = updated;
    }
}

async function scanItem(item: ReservationItem) {
    if (!selectedReservation.value) {
        return;
    }

    const code = prompt('Code scannen:');
    if (!code) {
        return;
    }

    const updated = await reservationStore.fetchIssueItem(selectedReservation.value.id, item.id, { code });

    if (updated) {
        replaceReservation(updated);
    }
}
</script>

<template>
    <p class="eyebrow">Verwaltung</p>
    <h1>Admin-Reservierungen</h1>

    <p v-if="reservationStore.error" class="message-error">{{ reservationStore.error }}</p>

    <div class="layout">
        <section class="card-list">
            <template v-for="reservation in reservationStore.reservations" :key="reservation.id">
                <ReservationAdminCard
                    :reservation="reservation"
                    :selected="reservation.id === selectedId"
                    @open="selectReservation"
                />

                <ReservationDetailCard
                    v-if="reservation.id === selectedId"
                    :reservation="reservation"
                    @scan-global="scanGlobal"
                    @scan-item="scanItem"
                />
            </template>
        </section>
    </div>

</template>

<style scoped>

</style>
